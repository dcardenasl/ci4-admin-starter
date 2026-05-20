<?php

declare(strict_types=1);

namespace App\Modules\Files\Controllers;

use App\Controllers\BaseWebController;
use App\Modules\Files\Requests\FileUploadRequest;
use App\Modules\Files\Services\FileApiServiceInterface;
use App\Support\CatalogOptions;
use App\Support\FileSizeLimits;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class FileController extends BaseWebController
{
    protected FileApiServiceInterface $fileService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->fileService = service('fileApiService');
    }

    public function index(): string
    {
        return $this->render('files/index', [
            'title'             => lang('Files.title'),
            'visibilityOptions' => CatalogOptions::options([], 'files.visibility', [
                ['value' => 'private', 'label' => lang('Files.private')],
                ['value' => 'public',  'label' => lang('Files.public')],
            ]),
            'limitOptions'      => CatalogOptions::limitOptions([]),
            'categoryOptions'   => $this->categoryOptions(),
        ]);
    }

    public function data(): ResponseInterface
    {
        return $this->tableDataResponse(
            ['original_name', 'category', 'date_from', 'date_to', 'size_min', 'size_max'],
            ['uploaded_at', 'original_name', 'size'],
            function (array $params) {
                $params = $this->mapCategoryToMimeFilter($params);

                return $this->fileService->list($this->kbToBytes($params) + ['trashed' => 'without']);
            },
        );
    }

    public function trash(): string
    {
        return $this->render('files/trash', [
            'title'           => lang('Files.trash_title'),
            'limitOptions'    => CatalogOptions::limitOptions([]),
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function trashData(): ResponseInterface
    {
        return $this->tableDataResponse(
            ['original_name', 'category'],
            ['uploaded_at', 'original_name', 'size'],
            function (array $params) {
                $params = $this->mapCategoryToMimeFilter($params);

                return $this->fileService->list($params + ['trashed' => 'only']);
            },
        );
    }

    public function upload(): ResponseInterface
    {
        /** @var FileUploadRequest $request */
        $request = service('formRequest', FileUploadRequest::class, false);
        if (! $request->validate()) {
            if ($this->request instanceof \CodeIgniter\HTTP\IncomingRequest && $this->request->isAJAX()) {
                return $this->response->setJSON(['ok' => false, 'fieldErrors' => $request->errors()]);
            }

            return redirect()->to(route_to('files'))->with('fieldErrors', $request->errors());
        }

        $file = $this->request instanceof \CodeIgniter\HTTP\IncomingRequest
            ? $this->request->getFile('file')
            : null;

        if ($file === null || ! $file->isValid()) {
            $maxSizeMb = FileSizeLimits::bytesToMb(FileSizeLimits::effectiveMaxBytes());
            $error = ($file && $file->getError() === UPLOAD_ERR_INI_SIZE)
                ? lang('Files.file_too_large', [$maxSizeMb])
                : lang('Files.invalid_file');

            if ($this->request instanceof \CodeIgniter\HTTP\IncomingRequest && $this->request->isAJAX()) {
                return $this->response->setJSON(['ok' => false, 'messages' => [$error]]);
            }

            return redirect()->to(route_to('files'))->with('error', $error);
        }

        $tempPath = $file->getTempName();

        $response = $this->safeApiCall(fn () => $this->fileService->upload(
            'file',
            $tempPath,
            $file->getName(),
            $file->getMimeType(),
            $request->payload(),
        ));

        if (! $response['ok']) {
            if ($this->request instanceof \CodeIgniter\HTTP\IncomingRequest && $this->request->isAJAX()) {
                return $this->response->setJSON([
                    'ok'          => false,
                    'messages'    => $response['messages'] ?? [lang('Files.upload_failed')],
                    'fieldErrors' => $response['fieldErrors'] ?? [],
                ]);
            }

            return $this->failApi($response, lang('Files.upload_failed'), route_to('files'), false);
        }

        if ($this->request instanceof \CodeIgniter\HTTP\IncomingRequest && $this->request->isAJAX()) {
            session()->setFlashdata('success', lang('Files.upload_success'));

            return $this->response->setJSON([
                'ok'        => true,
                'message'   => lang('Files.upload_success'),
                'redirect'  => route_to('files'),
                'csrf_name' => csrf_token(),
                'csrf_hash' => csrf_hash(),
                'file'      => $this->extractData($response),
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

    public function show(string $id): string|RedirectResponse
    {
        $info = $this->safeApiCall(fn () => $this->fileService->getInfo($id));
        if (! ($info['ok'] ?? false)) {
            return redirect()->to(route_to('files'))->with('error', lang('Files.file_not_found'));
        }

        $usages    = $this->safeApiCall(fn () => $this->fileService->usages($id));
        $usageData = ($usages['ok'] ?? false) ? $this->extractData($usages) : [];
        if (isset($usageData['data']) && is_array($usageData['data'])) {
            $usageData = $usageData['data'];
        }

        return $this->render('files/show', [
            'title'  => lang('Files.detail_title'),
            'file'   => $this->extractData($info),
            'usages' => array_values($usageData),
        ]);
    }

    public function usagesJson(string $id): ResponseInterface
    {
        $response = $this->safeApiCall(fn () => $this->fileService->usages($id));

        return $this->response->setJSON($response);
    }

    public function updateMeta(string $id): RedirectResponse
    {
        $payload = [];
        foreach (['alt_text', 'caption', 'credit'] as $key) {
            $value = $this->request->getPost($key);
            if (is_string($value)) {
                $payload[$key] = trim($value);
            } elseif ($value !== null) {
                $payload[$key] = '';
            }
        }

        $response = $this->safeApiCall(fn () => $this->fileService->updateMetadata($id, $payload));
        if (! ($response['ok'] ?? false)) {
            return $this->failApi($response, lang('Files.metadata_update_failed'), route_to('files.show', $id), false);
        }

        return redirect()->to(route_to('files.show', $id))->with('success', lang('Files.metadata_update_success'));
    }

    public function restore(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn () => $this->fileService->restore($id));
        if (! ($response['ok'] ?? false)) {
            return $this->failApi($response, lang('Files.restore_failed'), route_to('files.trash'), false);
        }

        return redirect()->to(route_to('files.trash'))->with('success', lang('Files.restore_success'));
    }

    public function forceDelete(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn () => $this->fileService->forceDelete($id));
        if (! ($response['ok'] ?? false)) {
            return $this->failApi($response, lang('Files.force_delete_failed'), route_to('files.trash'), false);
        }

        return redirect()->to(route_to('files.trash'))->with('success', lang('Files.force_delete_success'));
    }

    public function regenerate(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn () => $this->fileService->regenerateVariants($id));
        if (! ($response['ok'] ?? false)) {
            return $this->failApi($response, lang('Files.regenerate_failed'), route_to('files.show', $id), false);
        }

        return redirect()->to(route_to('files.show', $id))->with('success', lang('Files.regenerate_success'));
    }

    public function bulk(): RedirectResponse
    {
        $rawAction = $this->request->getPost('action');
        $action    = is_string($rawAction) ? $rawAction : '';
        $rawIds    = $this->request->getPost('ids');
        $ids       = [];
        if (is_array($rawIds)) {
            foreach ($rawIds as $value) {
                if (is_numeric($value) && (int) $value > 0) {
                    $ids[] = (int) $value;
                }
            }
        }

        $referrer = $this->request instanceof \CodeIgniter\HTTP\IncomingRequest
            ? $this->request->getServer('HTTP_REFERER')
            : null;
        $isTrash = $action === 'restore' || $action === 'force';
        $back    = is_string($referrer) && $referrer !== ''
            ? $referrer
            : ($isTrash ? route_to('files.trash') : route_to('files'));

        if ($ids === []) {
            return redirect()->to($back)->with('error', lang('Files.bulk_no_selection'));
        }

        $response = match ($action) {
            'delete'  => $this->safeApiCall(fn () => $this->fileService->bulkDelete($ids)),
            'restore' => $this->safeApiCall(fn () => $this->fileService->bulkRestore($ids)),
            'force'   => $this->safeApiCall(fn () => $this->fileService->bulkForceDelete($ids)),
            default   => null,
        };

        if ($response === null) {
            return redirect()->to($back)->with('error', lang('Files.bulk_unknown_action'));
        }

        if (! ($response['ok'] ?? false)) {
            return $this->failApi($response, lang('Files.bulk_failed'), $back, false);
        }

        $data    = $this->extractData($response);
        $items   = isset($data['data']) && is_array($data['data']) ? $data['data'] : $data;
        $total   = count($items);
        $okCount = 0;
        foreach ($items as $item) {
            if (is_array($item) && ! empty($item['ok'])) {
                $okCount++;
            }
        }
        $message = lang('Files.bulk_summary', [$okCount, $total]);

        return redirect()->to($back)->with('success', $message);
    }

    public function pickerData(): ResponseInterface
    {
        $rawPage     = $this->request->getGet('page');
        $rawPerPage  = $this->request->getGet('per_page');
        $rawSearch   = $this->request->getGet('search');
        $rawCategory = $this->request->getGet('category');
        $filters     = [
            'page'     => max(1, is_scalar($rawPage) ? (int) $rawPage : 1),
            'per_page' => min(50, max(12, is_scalar($rawPerPage) ? (int) $rawPerPage : 24)),
            'sort'     => '-id',
        ];
        $search = is_scalar($rawSearch) ? (string) $rawSearch : '';
        if ($search !== '') {
            $filters['search'] = $search;
        }
        $category = is_scalar($rawCategory) ? (string) $rawCategory : '';
        if ($category !== '') {
            $filters['category'] = $category;
        }
        $filters = $this->mapCategoryToMimeFilter($filters);

        $response = $this->safeApiCall(fn () => $this->fileService->listForPicker($filters));

        return $this->response->setJSON($response);
    }

    public function pickerInfo(string $id): ResponseInterface
    {
        $response = $this->safeApiCall(fn () => $this->fileService->getInfo($id));

        if (! ($response['ok'] ?? false)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        $data     = $this->extractData($response);
        $variants = is_array($data['variants'] ?? null) ? $data['variants'] : null;

        return $this->response->setJSON([
            'ok'   => true,
            'data' => [
                'id'            => $data['id'] ?? null,
                'original_name' => $data['original_name'] ?? '',
                'mime_type'     => $data['mime_type'] ?? '',
                'category'      => $data['category'] ?? '',
                'human_size'    => $data['human_size'] ?? '',
                'is_image'      => $data['is_image'] ?? false,
                'url'           => $data['url'] ?? '',
                'variants'      => $variants,
                'alt_text'      => $data['alt_text'] ?? '',
            ],
        ]);
    }

    public function delete(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn () => $this->fileService->delete($id));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Files.delete_failed'), route_to('files'), false);
        }

        return redirect()->to(route_to('files'))->with('success', lang('Files.delete_success'));
    }

    protected function serveFile(string $id, string $disposition): ResponseInterface
    {
        $etag        = '"' . sha1($id . '|' . $disposition) . '"';
        $ifNoneMatch = $this->request instanceof \CodeIgniter\HTTP\IncomingRequest
            ? (string) $this->request->getHeaderLine('If-None-Match')
            : '';
        if ($ifNoneMatch !== '' && $ifNoneMatch === $etag) {
            return $this->response
                ->setStatusCode(304)
                ->setHeader('ETag', $etag)
                ->setHeader('Cache-Control', 'private, max-age=3600');
        }

        $response = $this->safeApiCall(fn () => $this->fileService->get($id));

        if (! $response['ok']) {
            return $this->response->setStatusCode(404)->setBody('File not found');
        }

        $data               = $this->extractData($response);
        $url                = $data['download_url'] ?? $data['url'] ?? null;
        $raw                = (string) ($response['raw'] ?? '');
        $headers            = is_array($response['headers'] ?? null) ? $response['headers'] : [];
        $contentType        = (string) ($headers['content-type'] ?? '');
        $contentDisposition = (string) ($headers['content-disposition'] ?? '');

        if (is_string($url) && $url !== '') {
            return redirect()->to($url);
        }

        if ($raw !== '' && str_contains($contentType, '/') && ! str_contains($contentType, 'json')) {
            $headerFilename = '';
            if ($contentDisposition !== '') {
                if (preg_match('/filename\*?=(?:[A-Z0-9-]+\'\')?"?([^";]+)"?/i', $contentDisposition, $matches)) {
                    $headerFilename = rawurldecode($matches[1]);
                }
            }

            $filename = $data['original_name'] ?? $data['name'] ?? $data['filename'] ?? $headerFilename;
            if (empty($filename)) {
                $filename = "file_{$id}";
            }

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
                ->setHeader('Cache-Control', 'private, max-age=3600')
                ->setHeader('ETag', $etag)
                ->setBody($raw);
        }

        return $this->response->setStatusCode(404)->setBody('File content empty or invalid');
    }

    /**
     * Convert a category filter value to a mime_type LIKE filter the API understands.
     * The API has no category column — categories are derived from mime_type prefixes.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function mapCategoryToMimeFilter(array $params): array
    {
        $filterArr = isset($params['filter']) && is_array($params['filter']) ? $params['filter'] : [];
        $category  = (string) ($params['category'] ?? $filterArr['category'] ?? '');
        unset($params['category'], $filterArr['category']);
        $params['filter'] = $filterArr;

        $mimePrefix = match ($category) {
            'image'    => 'image/',
            'video'    => 'video/',
            'audio'    => 'audio/',
            'document' => 'application/',
            default    => null,
        };

        if ($mimePrefix !== null) {
            $params['filter']['mime_type'] = ['like' => $mimePrefix];
        }

        return $params;
    }

    /**
     * @return array<int, array{value:string, label:string}>
     */
    private function categoryOptions(): array
    {
        return [
            ['value' => '',         'label' => lang('Files.category_all')],
            ['value' => 'image',    'label' => lang('Files.category_image')],
            ['value' => 'document', 'label' => lang('Files.category_document')],
            ['value' => 'video',    'label' => lang('Files.category_video')],
            ['value' => 'audio',    'label' => lang('Files.category_audio')],
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function kbToBytes(array $params): array
    {
        foreach (['size_min', 'size_max'] as $key) {
            if (isset($params[$key]) && is_numeric($params[$key])) {
                $params[$key] = (int) $params[$key] * 1024;
                if (isset($params['filter']) && is_array($params['filter']) && isset($params['filter'][$key])) {
                    $params['filter'][$key] = $params[$key];
                }
            }
        }

        return $params;
    }
}
