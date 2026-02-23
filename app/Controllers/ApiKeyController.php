<?php

namespace App\Controllers;

use App\Services\ApiKeyApiService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class ApiKeyController extends BaseWebController
{
    protected ApiKeyApiService $apiKeyService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->apiKeyService = service('apiKeyApiService');
    }

    public function index(): string
    {
        return $this->render('api_keys/index', [
            'title' => lang('ApiKeys.title'),
        ]);
    }

    public function data(): ResponseInterface
    {
        return $this->tableDataResponse(
            ['name', 'is_active'],
            ['id', 'name', 'is_active', 'created_at'],
            fn(array $params) => $this->apiKeyService->list($params),
        );
    }

    public function show(string $id): string
    {
        $response = $this->safeApiCall(fn() => $this->apiKeyService->get($id));

        return $this->renderResourceShow('api_keys/show', lang('ApiKeys.details'), 'apiKey', $response, lang('ApiKeys.notFound'));
    }

    public function create(): string
    {
        return $this->render('api_keys/create', [
            'title' => lang('ApiKeys.create'),
        ]);
    }

    public function store(): RedirectResponse
    {
        if (! $this->validate($this->rulesForCreate())) {
            return $this->failValidation();
        }

        $payload = $this->payloadFromRequest();
        $response = $this->safeApiCall(fn() => $this->apiKeyService->create($payload));

        if (! $response['ok']) {
            return $this->failApi($response, lang('ApiKeys.createFailed'));
        }

        $created = $this->extractData($response);
        $id = (string) ($created['id'] ?? '');
        $redirectTo = $id !== ''
            ? site_url('admin/api-keys/' . rawurlencode($id))
            : site_url('admin/api-keys');

        $redirect = redirect()->to($redirectTo)->with('success', lang('ApiKeys.createSuccess'));

        $rawKey = (string) ($created['key'] ?? '');
        if ($rawKey !== '') {
            $redirect
                ->with('generatedApiKey', $rawKey)
                ->with('generatedApiKeyName', (string) ($created['name'] ?? ''));
        }

        return $redirect;
    }

    public function edit(string $id): string
    {
        $response = $this->safeApiCall(fn() => $this->apiKeyService->get($id));

        return $this->render('api_keys/edit', [
            'title'  => lang('ApiKeys.edit'),
            'apiKey' => $this->extractData($response),
        ]);
    }

    public function update(string $id): RedirectResponse
    {
        if (! $this->validate($this->rulesForUpdate())) {
            return $this->failValidation();
        }

        $payload = $this->payloadFromRequest();

        if ($payload === []) {
            return redirect()->back()->withInput()->with('error', lang('ApiKeys.atLeastOneField'));
        }

        $response = $this->safeApiCall(fn() => $this->apiKeyService->update($id, $payload));

        if (! $response['ok']) {
            return $this->failApi($response, lang('ApiKeys.updateFailed'));
        }

        return redirect()->to(site_url('admin/api-keys/' . rawurlencode($id)))->with('success', lang('ApiKeys.updateSuccess'));
    }

    public function delete(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn() => $this->apiKeyService->delete($id));

        if (! $response['ok']) {
            return $this->failApi($response, lang('ApiKeys.deleteFailed'), site_url('admin/api-keys'), false);
        }

        return redirect()->to(site_url('admin/api-keys'))->with('success', lang('ApiKeys.deleteSuccess'));
    }

    /**
     * @return array<string, string>
     */
    protected function rulesForCreate(): array
    {
        return [
            'name'                => 'required|max_length[100]',
            'rate_limit_requests' => 'permit_empty|is_natural_no_zero',
            'rate_limit_window'   => 'permit_empty|is_natural_no_zero',
            'user_rate_limit'     => 'permit_empty|is_natural_no_zero',
            'ip_rate_limit'       => 'permit_empty|is_natural_no_zero',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function rulesForUpdate(): array
    {
        return [
            'name'                => 'permit_empty|max_length[100]',
            'is_active'           => 'permit_empty|in_list[0,1]',
            'rate_limit_requests' => 'permit_empty|is_natural_no_zero',
            'rate_limit_window'   => 'permit_empty|is_natural_no_zero',
            'user_rate_limit'     => 'permit_empty|is_natural_no_zero',
            'ip_rate_limit'       => 'permit_empty|is_natural_no_zero',
        ];
    }

    /**
     * @return array<string, int|string|bool>
     */
    protected function payloadFromRequest(): array
    {
        $payload = [];

        $name = trim((string) $this->request->getPost('name'));
        if ($name !== '') {
            $payload['name'] = $name;
        }

        $isActive = $this->request->getPost('is_active');
        if ($isActive !== null && $isActive !== '') {
            $payload['is_active'] = $isActive === '1' || $isActive === 1 || $isActive === true || $isActive === 'true';
        }

        $numericFields = [
            'rate_limit_requests',
            'rate_limit_window',
            'user_rate_limit',
            'ip_rate_limit',
        ];

        foreach ($numericFields as $field) {
            $value = trim((string) $this->request->getPost($field));
            if ($value !== '') {
                $payload[$field] = (int) $value;
            }
        }

        return $payload;
    }
}
