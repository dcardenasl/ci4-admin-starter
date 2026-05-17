<?php

declare(strict_types=1);

namespace App\Modules\ApiKeys\Controllers;

use App\Controllers\BaseWebController;
use App\Modules\ApiKeys\Requests\ApiKeyStoreRequest;
use App\Modules\ApiKeys\Requests\ApiKeyUpdateRequest;
use App\Modules\ApiKeys\Services\ApiKeyApiServiceInterface;
use App\Support\CatalogOptions;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class ApiKeyController extends BaseWebController
{
    protected ApiKeyApiServiceInterface $apiKeyService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->apiKeyService = service('apiKeyApiService');
    }

    private function requireRead(): ?RedirectResponse
    {
        if (! has_permission('apikeys.read')) {
            return redirect()->to(route_to('dashboard'))->with('error', lang('App.access_denied'));
        }
        return null;
    }

    private function requireWrite(): ?RedirectResponse
    {
        if (! has_permission('apikeys.write')) {
            return redirect()->to(route_to('admin.api_keys'))->with('error', lang('App.access_denied'));
        }
        return null;
    }

    public function index(): string|RedirectResponse
    {
        $deny = $this->requireRead();
        if ($deny !== null) {
            return $deny;
        }
        return $this->render('api_keys/index', [
            'title'         => lang('ApiKeys.title'),
            'statusOptions' => CatalogOptions::options([], 'api_keys.statuses', $this->defaultStatusOptions()),
            'limitOptions'  => CatalogOptions::limitOptions([]),
        ]);
    }

    public function data(): ResponseInterface|RedirectResponse
    {
        $deny = $this->requireRead();
        if ($deny !== null) {
            return $deny;
        }
        return $this->tableDataResponse(
            ['name', 'is_active'],
            ['id', 'name', 'is_active', 'created_at', 'rate_limit_requests', 'rate_limit_window'],
            fn (array $params) => $this->apiKeyService->list($params),
        );
    }

    public function show(string $id): string|RedirectResponse
    {
        $deny = $this->requireRead();
        if ($deny !== null) {
            return $deny;
        }
        $response = $this->safeApiCall(fn () => $this->apiKeyService->get($id));

        return $this->renderResourceShow('api_keys/show', lang('ApiKeys.details'), 'apiKey', $response, lang('ApiKeys.not_found'));
    }

    public function create(): string|RedirectResponse
    {
        $deny = $this->requireWrite();
        if ($deny !== null) {
            return $deny;
        }
        return $this->render('api_keys/create', [
            'title' => lang('ApiKeys.create'),
        ]);
    }

    public function store(): RedirectResponse
    {
        $deny = $this->requireWrite();
        if ($deny !== null) {
            return $deny;
        }
        /** @var ApiKeyStoreRequest $request */
        $request = service('formRequest', ApiKeyStoreRequest::class, false);
        $invalid = $this->validateRequest($request);
        if ($invalid !== null) {
            return $invalid;
        }

        $payload = $request->payload();
        $response = $this->safeApiCall(fn () => $this->apiKeyService->create($payload));

        if (! $response['ok']) {
            return $this->failApi($response, lang('ApiKeys.create_failed'));
        }

        $created = $this->extractData($response);
        $id = (string) ($created['id'] ?? '');
        $redirectTo = $id !== ''
            ? route_to('admin.api_keys.show', $id)
            : route_to('admin.api_keys');

        $redirect = redirect()->to($redirectTo)->with('success', lang('ApiKeys.created_success'));

        $rawKey = (string) ($created['key'] ?? '');
        if ($rawKey !== '') {
            $redirect
                ->with('generatedApiKey', $rawKey)
                ->with('generatedApiKeyName', (string) ($created['name'] ?? ''));
        }

        return $redirect;
    }

    public function edit(string $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $deny = $this->requireWrite();
        if ($deny !== null) {
            return $deny;
        }
        $response = $this->safeApiCall(fn () => $this->apiKeyService->get($id));

        if (! $response['ok']) {
            return redirect()->to(route_to('admin.api_keys'))->with('error', lang('ApiKeys.not_found'));
        }

        return $this->render('api_keys/edit', [
            'title'         => lang('ApiKeys.edit'),
            'apiKey'        => $this->extractData($response),
            'statusOptions' => CatalogOptions::options([], 'api_keys.statuses', $this->defaultStatusOptions()),
        ]);
    }

    public function update(string $id): RedirectResponse
    {
        $deny = $this->requireWrite();
        if ($deny !== null) {
            return $deny;
        }
        /** @var ApiKeyUpdateRequest $request */
        $request = service('formRequest', ApiKeyUpdateRequest::class, false);
        $invalid = $this->validateRequest($request);
        if ($invalid !== null) {
            return $invalid;
        }

        $payload = $request->payload();

        if ($payload === []) {
            return redirect()->back()->withInput()->with('error', lang('ApiKeys.at_least_one_field'));
        }

        $response = $this->safeApiCall(fn () => $this->apiKeyService->update($id, $payload));

        if (! $response['ok']) {
            return $this->failApi($response, lang('ApiKeys.update_failed'));
        }

        return redirect()->to(route_to('admin.api_keys.show', $id))->with('success', lang('ApiKeys.updated_success'));
    }

    public function delete(string $id): RedirectResponse
    {
        $deny = $this->requireWrite();
        if ($deny !== null) {
            return $deny;
        }
        $response = $this->safeApiCall(fn () => $this->apiKeyService->delete($id));

        if (! $response['ok']) {
            return $this->failApi($response, lang('ApiKeys.delete_failed'), route_to('admin.api_keys'), false);
        }

        return redirect()->to(route_to('admin.api_keys'))->with('success', lang('ApiKeys.deleted_success'));
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function defaultStatusOptions(): array
    {
        return [
            ['value' => '1', 'label' => lang('ApiKeys.active')],
            ['value' => '0', 'label' => lang('ApiKeys.inactive')],
        ];
    }

}
