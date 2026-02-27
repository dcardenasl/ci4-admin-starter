<?php

namespace App\Controllers;

use App\Requests\ApiKey\ApiKeyStoreRequest;
use App\Requests\ApiKey\ApiKeyUpdateRequest;
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
            ['name', 'isActive'],
            ['id', 'name', 'isActive', 'createdAt'],
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
        /** @var ApiKeyStoreRequest $request */
        $request = service('formRequest', ApiKeyStoreRequest::class, false);
        $invalid = $this->validateRequest($request);
        if ($invalid !== null) {
            return $invalid;
        }

        $payload = $request->payload();
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
        /** @var ApiKeyUpdateRequest $request */
        $request = service('formRequest', ApiKeyUpdateRequest::class, false);
        $invalid = $this->validateRequest($request);
        if ($invalid !== null) {
            return $invalid;
        }

        $payload = $request->payload();

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

}
