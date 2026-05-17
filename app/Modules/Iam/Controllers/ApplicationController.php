<?php

declare(strict_types=1);

namespace App\Modules\Iam\Controllers;

use App\Controllers\BaseWebController;
use App\Modules\Iam\Services\ApplicationApiServiceInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Read-only browser for applications registered in the hub.
 *
 * Applications are created server-side via `php spark apps:bootstrap` (on
 * the hub) — there is no UI to create or edit them here. This controller
 * exposes the list and per-row detail so superadmins can confirm which
 * domain apps are registered and inspect their api_key bindings.
 */
class ApplicationController extends BaseWebController
{
    protected ApplicationApiServiceInterface $applicationService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->applicationService = service('applicationApiService');
    }

    public function index(): string
    {
        return $this->render('iam/applications/index', [
            'title'        => lang('Iam.applications_title'),
            'limitOptions' => [10, 25, 50, 100],
        ]);
    }

    public function data(): ResponseInterface
    {
        $tableState = $this->resolveTableState([], ['code', 'name', 'created_at']);
        $params     = $this->buildTableApiParams($tableState);
        $response   = $this->safeApiCall(fn () => $this->applicationService->list($params));

        unset($response['raw']);

        return $this->passthroughApiJsonResponse($response);
    }

    public function show(string $id): string
    {
        $response = $this->safeApiCall(fn () => $this->applicationService->get($id));

        if (! ($response['ok'] ?? false)) {
            return $this->render('iam/applications/show', [
                'title'       => lang('Iam.applications_details'),
                'application' => [],
                'error'       => $this->firstMessage($response, lang('Iam.applications_not_found')),
            ]);
        }

        return $this->render('iam/applications/show', [
            'title'       => lang('Iam.applications_details'),
            'application' => $this->extractData($response),
        ]);
    }
}
