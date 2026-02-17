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
        $tableState = $this->resolveTableState(['status'], ['created_at', 'name', 'status']);
        $response = $this->safeApiCall(fn() => $this->fileService->list($this->buildTableApiParams($tableState)));

        return $this->passthroughApiJsonResponse($response);
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
            return redirect()->to(site_url('files'))->with('error', $this->firstMessage($response, lang('Files.uploadFailed')));
        }

        return redirect()->to(site_url('files'))->with('success', lang('Files.uploadSuccess'));
    }

    public function download(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn() => $this->fileService->getDownload($id));

        if (! $response['ok']) {
            return redirect()->to(site_url('files'))->with('error', $this->firstMessage($response, lang('Files.downloadFailed')));
        }

        $data = $this->extractData($response);
        $url = is_array($data) ? ($data['download_url'] ?? $data['url'] ?? null) : null;

        if (! is_string($url) || $url === '') {
            return redirect()->to(site_url('files'))->with('error', lang('Files.downloadInvalid'));
        }

        return redirect()->to($url);
    }

    public function delete(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn() => $this->fileService->delete($id));

        if (! $response['ok']) {
            return redirect()->to(site_url('files'))->with('error', $this->firstMessage($response, lang('Files.deleteFailed')));
        }

        return redirect()->to(site_url('files'))->with('success', lang('Files.deleteSuccess'));
    }
}
