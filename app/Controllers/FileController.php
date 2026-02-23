<?php

namespace App\Controllers;

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

    public function upload(): RedirectResponse
    {
        if (! $this->validate([
            'file' => 'uploaded[file]|max_size[file,10240]',
        ])) {
            return redirect()->to(site_url('files'))->with('fieldErrors', $this->validator->getErrors());
        }

        $file = $this->request->getFile('file');

        if ($file === null || ! $file->isValid()) {
            return redirect()->to(site_url('files'))->with('error', lang('Files.invalidFile'));
        }

        $uploadDir = WRITEPATH . 'uploads/';
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0o755, true);
        }

        $tempPath = $uploadDir . uniqid('upload_', true) . '_' . $file->getName();
        $file->move(dirname($tempPath), basename($tempPath));

        $response = $this->safeApiCall(fn() => $this->fileService->upload('file', $tempPath, [
            'visibility' => (string) ($this->request->getPost('visibility') ?? 'private'),
        ]));

        @unlink($tempPath);

        if (! $response['ok']) {
            return $this->failApi($response, lang('Files.uploadFailed'), site_url('files'), false);
        }

        return redirect()->to(site_url('files'))->with('success', lang('Files.uploadSuccess'));
    }

    public function download(string $id): ResponseInterface
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
            $contentDisposition = (string) ($headers['content-disposition'] ?? '');
            $contentLength = (string) ($headers['content-length'] ?? '');

            $result = $this->response
                ->setStatusCode((int) ($response['status'] ?? 200))
                ->setHeader('Content-Type', $contentType)
                ->setBody($raw);

            if ($contentDisposition !== '') {
                $result->setHeader('Content-Disposition', $contentDisposition);
            }

            if ($contentLength !== '') {
                $result->setHeader('Content-Length', $contentLength);
            }

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
