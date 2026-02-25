<?php

namespace App\Controllers;

use App\Requests\File\FileUploadRequest;
use App\Services\FileApiService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class FileController extends BaseWebController
{
    protected FileApiService $fileService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->fileService = service('fileApiService');
    }

    public function index(): string
    {
        return $this->render('files/index', [
            'title' => lang('Files.title'),
        ]);
    }

    public function data(): ResponseInterface
    {
        return $this->tableDataResponse(
            ['status'],
            ['created_at', 'name', 'status'],
            fn(array $params) => $this->fileService->list($params),
        );
    }

    public function upload(): ResponseInterface
    {
        /** @var FileUploadRequest $request */
        $request = service('formRequest', FileUploadRequest::class, false);
        if (! $request->validate()) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['ok' => false, 'fieldErrors' => $request->errors()]);
            }

            return redirect()->to(site_url('files'))->with('fieldErrors', $request->errors());
        }

        $file = $this->request->getFile('file');

        if ($file === null || ! $file->isValid()) {
            $maxBytes = config('Validation')->maxFileSizeBytes ?? 10485760;
            $maxSizeMb = round($maxBytes / 1024 / 1024, 1);
            $error = ($file && $file->getError() === UPLOAD_ERR_INI_SIZE)
                ? lang('Files.fileTooLarge', [$maxSizeMb])
                : lang('Files.invalidFile');

            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['ok' => false, 'messages' => [$error]]);
            }

            return redirect()->to(site_url('files'))->with('error', $error);
        }

        $uploadDir = WRITEPATH . 'uploads/' . bin2hex(random_bytes(8)) . '/';
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0o755, true);
        }

        $tempPath = $uploadDir . $file->getName();
        $file->move(dirname($tempPath), basename($tempPath));

        $response = $this->safeApiCall(fn() => $this->fileService->upload('file', $tempPath, [
            'visibility'    => (string) ($request->payload()['visibility'] ?? 'private'),
            'filename'      => $file->getName(),
            'name'          => $file->getName(),
            'original_name' => $file->getName(),
        ]));

        @unlink($tempPath);
        @rmdir($uploadDir);

        if (! $response['ok']) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'ok'          => false,
                    'messages'    => $response['messages'] ?? [lang('Files.uploadFailed')],
                    'fieldErrors' => $response['fieldErrors'] ?? [],
                ]);
            }

            return $this->failApi($response, lang('Files.uploadFailed'), site_url('files'), false);
        }

        if ($this->request->isAJAX()) {
            session()->setFlashdata('success', lang('Files.uploadSuccess'));
            return $this->response->setJSON([
                'ok'       => true,
                'message'  => lang('Files.uploadSuccess'),
                'redirect' => site_url('files'),
            ]);
        }

        return redirect()->to(site_url('files'))->with('success', lang('Files.uploadSuccess'));
    }

    public function download(string $id): ResponseInterface
    {
        return $this->serveFile($id, 'attachment');
    }

    public function view(string $id): ResponseInterface
    {
        return $this->serveFile($id, 'inline');
    }

    protected function serveFile(string $id, string $disposition): ResponseInterface
    {
        $response = $this->safeApiCall(fn() => $this->fileService->getDownload($id));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Files.downloadFailed'), site_url('files'), false);
        }

        $data = $this->extractData($response);
        $url = is_array($data) ? ($data['download_url'] ?? $data['url'] ?? null) : null;

        if (! is_string($url) || $url === '') {
            $raw = (string) ($response['raw'] ?? '');
            if ($raw === '') {
                return redirect()->to(site_url('files'))->with('error', lang('Files.downloadInvalid'));
            }

            $headers = is_array($response['headers'] ?? null) ? $response['headers'] : [];
            $contentType = (string) ($headers['content-type'] ?? 'application/octet-stream');
            
            // 1. Intentar obtener el nombre del campo 'original_name' de la API
            $filename = $data['original_name'] ?? $response['original_name'] ?? $data['name'] ?? $data['filename'] ?? null;
            
            // 2. Si no hay nombre en el cuerpo, intentar obtenerlo del Content-Disposition de la propia API
            if (! $filename && isset($headers['content-disposition'])) {
                if (preg_match('/filename="?([^"]+)"?/', $headers['content-disposition'], $matches)) {
                    $filename = $matches[1];
                }
            }

            // 3. Si seguimos sin nombre, generar uno basado en el ID y el tipo de contenido
            if (! $filename) {
                $extension = \Config\Mimes::guessExtensionFromType($contentType) ?? 'bin';
                $filename = "file_{$id}.{$extension}";
            }

            $result = $this->response
                ->setStatusCode((int) ($response['status'] ?? 200))
                ->setHeader('Content-Type', $contentType)
                ->setHeader('Content-Disposition', $disposition . '; filename="' . $filename . '"')
                ->setBody($raw);

            return $result;
        }

        return redirect()->to($url);
    }

    public function delete(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn() => $this->fileService->delete($id));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Files.deleteFailed'), site_url('files'), false);
        }

        return redirect()->to(site_url('files'))->with('success', lang('Files.deleteSuccess'));
    }
}
