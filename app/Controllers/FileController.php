<?php

namespace App\Controllers;

use App\Services\FileApiService;
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
        $response = $this->fileService->list([
            'search' => (string) $this->request->getGet('search'),
        ]);

        return $this->render('files/index', [
            'title' => 'Archivos',
            'files' => $this->extractItems($response),
        ]);
    }

    public function upload()
    {
        $file = $this->request->getFile('file');

        if ($file === null || ! $file->isValid()) {
            return redirect()->to('/files')->with('error', 'Selecciona un archivo valido.');
        }

        $tempPath = WRITEPATH . 'uploads/' . uniqid('upload_', true) . '_' . $file->getName();
        $file->move(dirname($tempPath), basename($tempPath));

        $response = $this->fileService->upload('file', $tempPath, [
            'visibility' => (string) ($this->request->getPost('visibility') ?? 'private'),
        ]);

        @unlink($tempPath);

        if (! $response['ok']) {
            return redirect()->to('/files')->with('error', $this->firstMessage($response, 'No fue posible subir el archivo.'));
        }

        return redirect()->to('/files')->with('success', 'Archivo subido correctamente.');
    }

    public function download(string $id)
    {
        $response = $this->fileService->getDownload($id);

        if (! $response['ok']) {
            return redirect()->to('/files')->with('error', $this->firstMessage($response, 'No fue posible obtener el enlace de descarga.'));
        }

        $payload = $response['data'] ?? [];
        $data = $payload['data'] ?? $payload;
        $url = is_array($data) ? ($data['download_url'] ?? $data['url'] ?? null) : null;

        if (! is_string($url) || $url === '') {
            return redirect()->to('/files')->with('error', 'No se recibio un enlace valido de descarga.');
        }

        return redirect()->to($url);
    }

    public function delete(string $id)
    {
        $response = $this->fileService->delete($id);

        if (! $response['ok']) {
            return redirect()->to('/files')->with('error', $this->firstMessage($response, 'No fue posible eliminar el archivo.'));
        }

        return redirect()->to('/files')->with('success', 'Archivo eliminado.');
    }

    protected function extractItems(array $response): array
    {
        $data = $response['data'] ?? [];

        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        return is_array($data) ? $data : [];
    }

    protected function firstMessage(array $response, string $fallback): string
    {
        $messages = $response['messages'] ?? [];

        if (is_array($messages) && isset($messages[0])) {
            return (string) $messages[0];
        }

        return $fallback;
    }
}
