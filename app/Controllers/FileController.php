<?php

namespace App\Controllers;

use App\Requests\File\FileUploadRequest;
use App\Services\CatalogApiService;
use App\Services\FileApiService;
use App\Support\FileSizeLimits;
use App\Support\CatalogOptions;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class FileController extends BaseWebController
{
    protected FileApiService $fileService;
    protected CatalogApiService $catalogService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->fileService = service('fileApiService');
        $this->catalogService = service('catalogApiService');
    }

    public function index(): string
    {
        $catalogsResponse = $this->safeApiCall(fn() => $this->catalogService->index());
        $catalogs = $this->extractData($catalogsResponse);
        if (! is_array($catalogs)) {
            $catalogs = [];
        }

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

            return redirect()->to(site_url('files'))->with('fieldErrors', $request->errors());
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

            return redirect()->to(site_url('files'))->with('error', $error);
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

            return $this->failApi($response, lang('Files.upload_failed'), site_url('files'), false);
        }

        if ($this->request->isAJAX()) {
            session()->setFlashdata('success', lang('Files.upload_success'));
            return $this->response->setJSON([
                'ok'       => true,
                'message'  => lang('Files.upload_success'),
                'redirect' => site_url('files'),
            ]);
        }

        return redirect()->to(site_url('files'))->with('success', lang('Files.upload_success'));
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
            return $this->response->setStatusCode(404)->setBody('File not found');
        }

        $data = $this->extractData($response);
        $url = is_array($data) ? ($data['download_url'] ?? $data['url'] ?? null) : null;

        // If API returned binary data directly
        $raw = (string) ($response['raw'] ?? '');
        $headers = is_array($response['headers'] ?? null) ? $response['headers'] : [];
        $contentType = (string) ($headers['content-type'] ?? '');

        if ($raw !== '' && str_contains($contentType, '/')) {
            $filename = $data['original_name'] ?? $data['name'] ?? $data['filename'] ?? "file_{$id}";
            $safeFilename = str_replace(['"', "\r", "\n", "\0"], '', basename((string) $filename));

            return $this->response
                ->setStatusCode(200)
                ->setHeader('Content-Type', $contentType)
                ->setHeader('Content-Disposition', $disposition . '; filename="' . $safeFilename . '"')
                ->setBody($raw);
        }
        if (is_string($url) && $url !== '') {
            return redirect()->to($url);
        }

        return $this->response->setStatusCode(404)->setBody('File content empty');
    }

    public function delete(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn() => $this->fileService->delete($id));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Files.delete_failed'), site_url('files'), false);
        }

        return redirect()->to(site_url('files'))->with('success', lang('Files.delete_success'));
    }
}
