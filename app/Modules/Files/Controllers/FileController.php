<?php

declare(strict_types=1);

namespace App\Modules\Files\Controllers;

use App\Controllers\BaseWebController;
use App\Modules\Files\Requests\FileUploadRequest;
use App\Services\CatalogApiService;
use App\Modules\Files\Services\FileApiServiceInterface;
use App\Support\FileSizeLimits;
use App\Support\CatalogOptions;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class FileController extends BaseWebController
{
    protected FileApiServiceInterface $fileService;
    protected CatalogApiService $catalogService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->fileService = service('fileApiService');
        $this->catalogService = service('catalogApiService');
    }

    public function index(): string
    {
        $catalogs = $this->resolveCatalogs($this->catalogService);

        return $this->render('files/index', [
            'title'             => lang('Files.title'),
            'visibilityOptions' => CatalogOptions::options($catalogs, 'files.visibility', [
                ['value' => 'private', 'label' => lang('Files.private')],
                ['value' => 'public', 'label' => lang('Files.public')],
            ]),
            'limitOptions'      => CatalogOptions::limitOptions($catalogs),
        ]);
    }

    public function data(): ResponseInterface
    {
        return $this->tableDataResponse(
            ['original_name', 'mime_type'],
            ['uploaded_at', 'original_name', 'mime_type', 'size'],
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

            return redirect()->to(route_to('files'))->with('fieldErrors', $request->errors());
        }

        $file = $this->request->getFile('file');

        if ($file === null || ! $file->isValid()) {
            $maxSizeMb = FileSizeLimits::bytesToMb(FileSizeLimits::effectiveMaxBytes());
            $error = ($file && $file->getError() === UPLOAD_ERR_INI_SIZE)
                ? lang('Files.file_too_large', [$maxSizeMb])
                : lang('Files.invalid_file');

            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['ok' => false, 'messages' => [$error]]);
            }

            return redirect()->to(route_to('files'))->with('error', $error);
        }

        $tempPath = $file->getTempName();

        $response = $this->safeApiCall(fn() => $this->fileService->upload(
            'file',
            $tempPath,
            $file->getName(),
            $file->getMimeType(),
            $request->payload(),
        ));

        if (! $response['ok']) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'ok'          => false,
                    'messages'    => $response['messages'] ?? [lang('Files.upload_failed')],
                    'fieldErrors' => $response['fieldErrors'] ?? [],
                ]);
            }

            return $this->failApi($response, lang('Files.upload_failed'), route_to('files'), false);
        }

        if ($this->request->isAJAX()) {
            session()->setFlashdata('success', lang('Files.upload_success'));
            return $this->response->setJSON([
                'ok'       => true,
                'message'  => lang('Files.upload_success'),
                'redirect' => route_to('files'),
            ]);
        }

        return redirect()->to(route_to('files'))->with('success', lang('Files.upload_success'));
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
        $response = $this->safeApiCall(fn() => $this->fileService->get($id));

        if (! $response['ok']) {
            return $this->response->setStatusCode(404)->setBody('File not found');
        }

        $data = $this->extractData($response);
        $url = is_array($data) ? ($data['download_url'] ?? $data['url'] ?? null) : null;

        // If API returned binary data directly
        $raw = (string) ($response['raw'] ?? '');
        $headers = is_array($response['headers'] ?? null) ? $response['headers'] : [];
        $contentType = (string) ($headers['content-type'] ?? '');
        $contentDisposition = (string) ($headers['content-disposition'] ?? '');

        // If the API returned a redirect URL, use it
        if (is_string($url) && $url !== '') {
            return redirect()->to($url);
        }

        // Handle direct binary response (avoid serving JSON metadata as a file)
        if ($raw !== '' && str_contains($contentType, '/') && ! str_contains($contentType, 'json')) {
            // 1. Try to get filename from Content-Disposition header from API
            $headerFilename = '';
            if ($contentDisposition !== '') {
                if (preg_match('/filename\*?=(?:[A-Z0-9-]+\'\')?"?([^";]+)"?/i', $contentDisposition, $matches)) {
                    $headerFilename = rawurldecode($matches[1]);
                }
            }

            // 2. Resolve final filename (metadata > header > default)
            $filename = $data['original_name'] ?? $data['name'] ?? $data['filename'] ?? $headerFilename;
            if (empty($filename)) {
                $filename = "file_{$id}";
            }

            // 3. Ensure it has an extension if it doesn't
            if (! str_contains($filename, '.')) {
                $extension = \Config\Mimes::guessExtensionFromType($contentType);
                if ($extension) {
                    $filename .= '.' . $extension;
                }
            }

            $safeFilename = str_replace(['"', "\r", "\n", "\0"], '', basename((string) $filename));

            return $this->response
                ->setStatusCode(200)
                ->setHeader('Content-Type', $contentType)
                ->setHeader('Content-Disposition', $disposition . '; filename="' . $safeFilename . '"')
                ->setBody($raw);
        }

        return $this->response->setStatusCode(404)->setBody('File content empty or invalid');
    }

    public function delete(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn() => $this->fileService->delete($id));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Files.delete_failed'), route_to('files'), false);
        }

        return redirect()->to(route_to('files'))->with('success', lang('Files.delete_success'));
    }
}
