<?php

namespace App\Controllers;

use App\Libraries\ApiClient;
use App\Requests\FormRequestInterface;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Psr\Log\LoggerInterface;

abstract class BaseWebController extends BaseController
{
    protected ApiClient $apiClient;

    protected \CodeIgniter\Session\Session $session;

    protected array $viewData = [];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->apiClient = service('apiClient');
        $this->session = session();
        helper(['url', 'form']);

        /** @var \Config\ApiClient $apiConfig */
        $apiConfig = config('ApiClient');

        $this->viewData = [
            'appName'          => $apiConfig->appName,
            'user'             => $this->session->get('user'),
            'currentLocale'    => Services::language()->getLocale(),
            'supportedLocales' => config('App')->supportedLocales,
        ];
    }

    protected function render(string $view, array $data = [], string $layout = 'layouts/app'): string
    {
        return view($layout, array_merge($this->viewData, $data, [
            'view' => $view,
        ]));
    }

    protected function renderAuth(string $view, array $data = []): string
    {
        return $this->render($view, $data, 'layouts/auth');
    }

    protected function withSuccess(string $message, string $redirectTo): RedirectResponse
    {
        return redirect()->to($redirectTo)->with('success', $message);
    }

    protected function withError(string $message, string $redirectTo): RedirectResponse
    {
        return redirect()->to($redirectTo)->with('error', $message);
    }

    protected function withFieldErrors(array $errors): RedirectResponse
    {
        return redirect()->back()->withInput()->with('fieldErrors', $errors);
    }

    protected function failValidation(): RedirectResponse
    {
        $errors = [];
        if (isset($this->validator) && $this->validator !== null) {
            $errors = $this->validator->getErrors();
        }

        return $this->withFieldErrors($errors);
    }

    protected function validateRequest(FormRequestInterface $request): ?RedirectResponse
    {
        if ($request->validate()) {
            return null;
        }

        return $this->withFieldErrors($request->errors());
    }

    /**
     * Build a consistent redirect response for failed API calls.
     *
     * @param array<int, string> $allowedFieldErrors
     */
    protected function failApi(
        array $response,
        string $fallbackMessage,
        ?string $redirectTo = null,
        bool $withInput = true,
        array $allowedFieldErrors = [],
    ): RedirectResponse {
        $fieldErrors = $this->getFieldErrors($response);

        if ($allowedFieldErrors !== []) {
            $fieldErrors = array_intersect_key($fieldErrors, array_flip($allowedFieldErrors));
        }

        if ($fieldErrors !== []) {
            return $this->withFieldErrors($fieldErrors);
        }

        $message = $this->firstMessage($response, $fallbackMessage);

        if ($redirectTo !== null && $redirectTo !== '') {
            return $this->withError($message, $redirectTo);
        }

        $redirect = redirect()->back();

        if ($withInput) {
            $redirect = $redirect->withInput();
        }

        return $redirect->with('error', $message);
    }

    /**
     * Resolve the canonical public web URL used in API emails.
     */
    protected function clientBaseUrl(): string
    {
        $configured = trim((string) env('WEBAPP_BASE_URL', ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $appBaseUrl = trim((string) config('App')->baseURL);
        if ($appBaseUrl !== '') {
            return rtrim($appBaseUrl, '/');
        }

        return rtrim(site_url('/'), '/');
    }

    protected function getFieldErrors(array $response): array
    {
        $fieldErrors = $response['fieldErrors'] ?? [];

        if (! is_array($fieldErrors)) {
            return [];
        }

        $normalized = [];

        foreach ($fieldErrors as $key => $value) {
            if (! is_string($key) || ! is_scalar($value)) {
                continue;
            }

            $normalizedKey = $this->normalizeErrorKey($key);
            $normalized[$normalizedKey] = $this->localizeApiMessage((string) $value);
        }

        return $normalized;
    }

    protected function normalizeErrorKey(string $key): string
    {
        $map = [
            'first_name'            => 'firstName',
            'last_name'             => 'lastName',
            'is_active'             => 'isActive',
            'rate_limit_requests'   => 'rateLimitRequests',
            'rate_limit_window'     => 'rateLimitWindow',
            'user_rate_limit'       => 'userRateLimit',
            'ip_rate_limit'         => 'ipRateLimit',
            'password_confirmation' => 'passwordConfirmation',
            'send_invite'           => 'sendInvite',
            'client_base_url'       => 'clientBaseUrl',
            'id_token'              => 'idToken',
            'original_email'        => 'originalEmail',
        ];

        return $map[$key] ?? $key;
    }

    /**
     * Extract the first message from an API response array.
     */
    protected function firstMessage(array $response, string $fallback): string
    {
        $messages = $response['messages'] ?? [];

        if (is_array($messages) && isset($messages[0])) {
            return $this->localizeApiMessage((string) $messages[0]);
        }

        return $fallback;
    }

    protected function localizeApiMessage(string $message): string
    {
        $normalized = trim($message);

        $knownTranslations = [
            'This email is already registered' => lang('Auth.emailAlreadyRegistered'),
        ];

        return $knownTranslations[$normalized] ?? $message;
    }

    /**
     * Extract the nested 'data' items from an API list response.
     */
    protected function extractItems(array $response): array
    {
        $data = $response['data'] ?? [];

        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        return is_array($data) ? $data : [];
    }

    /**
     * Extract the nested 'data' payload from an API single-object response.
     */
    protected function extractData(array $response): array
    {
        $payload = $response['data'] ?? [];

        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }

        return is_array($payload) ? $payload : [];
    }

    /**
     * Wrap an API call in a try/catch, returning a graceful error response on failure.
     *
     * @param callable $callback A closure that performs the API call and returns its result.
     * @return array The API response array, or a synthetic error response on exception.
     */
    protected function safeApiCall(callable $callback): array
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            log_message('error', 'API call failed: ' . $e->getMessage());

            return [
                'ok'          => false,
                'status'      => 0,
                'data'        => [],
                'raw'         => '',
                'headers'     => [],
                'messages'    => [lang('App.connectionError')],
                'fieldErrors' => [],
            ];
        }
    }

    /**
     * Resolve and normalize date range query params.
     *
     * @return array{date_from: string, date_to: string}
     */
    protected function resolveDateRange(int $defaultDays = 30): array
    {
        $dateFrom = trim((string) $this->request->getGet('dateFrom'));
        $dateTo = trim((string) $this->request->getGet('dateTo'));

        $today = new \DateTimeImmutable('today');

        if ($dateTo === '' || ! $this->isValidDate($dateTo)) {
            $dateTo = $today->format('Y-m-d');
        }

        if ($dateFrom === '' || ! $this->isValidDate($dateFrom)) {
            $dateFrom = $today->sub(new \DateInterval('P' . max(1, $defaultDays - 1) . 'D'))->format('Y-m-d');
        }

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
        ];
    }

    protected function positiveIntFromQuery(string $key, int $default, int $max = 200): int
    {
        $value = (int) $this->request->getGet($key);

        if ($value <= 0) {
            $value = $default;
        }

        return min($value, $max);
    }

    protected function isValidDate(string $date): bool
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $dt instanceof \DateTimeImmutable && $dt->format('Y-m-d') === $date;
    }

    protected function passthroughApiJsonResponse(array $apiResponse): ResponseInterface
    {
        $status = (int) ($apiResponse['status'] ?? 500);
        if ($status <= 0) {
            $status = 500;
        }

        $raw = (string) ($apiResponse['raw'] ?? '');
        if ($raw !== '') {
            return $this->response
                ->setStatusCode($status)
                ->setHeader('Content-Type', 'application/json; charset=UTF-8')
                ->setBody($raw);
        }

        $payload = $apiResponse['data'] ?? [];

        return $this->response
            ->setStatusCode($status)
            ->setJSON(is_array($payload) ? $payload : ['message' => lang('App.connectionError')]);
    }

    /**
     * Resolve table state, execute API list request and return passthrough JSON response.
     *
     * @param array<int, string> $allowedFilters
     * @param array<int, string> $allowedSorts
     * @param callable $listRequest Receives normalized API params and returns API response array.
     */
    protected function tableDataResponse(
        array $allowedFilters,
        array $allowedSorts,
        callable $listRequest,
        int $defaultLimit = 25,
        int $maxLimit = 100,
    ): ResponseInterface {
        $tableState = $this->resolveTableState($allowedFilters, $allowedSorts, $defaultLimit, $maxLimit);
        $params = $this->buildTableApiParams($tableState);
        $response = $this->safeApiCall(fn() => $listRequest($params));

        return $this->passthroughApiJsonResponse($response);
    }

    /**
     * Render a resource detail view with a consistent not-found fallback.
     */
    protected function renderResourceShow(
        string $view,
        string $title,
        string $dataKey,
        array $response,
        string $notFoundMessage,
    ): string {
        $data = [
            'title' => $title,
            $dataKey => [],
        ];

        if (! ($response['ok'] ?? false)) {
            $data['error'] = $this->firstMessage($response, $notFoundMessage);

            return $this->render($view, $data);
        }

        $data[$dataKey] = $this->extractData($response);

        return $this->render($view, $data);
    }

    /**
     * Normalize query input for server-driven tables.
     *
     * @param array<int, string> $allowedFilters
     * @param array<int, string> $allowedSorts
     * @return array{
     *   search: string,
     *   filters: array<string, string>,
     *   sort: string,
     *   limit: int,
     *   cursor: string,
     *   page: int
     * }
     */
    protected function resolveTableState(array $allowedFilters = [], array $allowedSorts = [], int $defaultLimit = 25, int $maxLimit = 100): array
    {
        $search = trim((string) ($this->request->getGet('search') ?? ''));

        $filters = [];
        foreach ($allowedFilters as $filter) {
            if (! is_string($filter) || $filter === '') {
                continue;
            }

            $value = $this->request->getGet($filter);
            $value = trim((string) $value);
            if ($value !== '') {
                $filters[$filter] = $value;
            }
        }

        $sort = trim((string) ($this->request->getGet('sort') ?? ''));
        if ($sort !== '') {
            $sortField = ltrim($sort, '-');
            if (! in_array($sortField, $allowedSorts, true)) {
                $sort = '';
            }
        }

        $limit = (int) $this->request->getGet('limit');
        if ($limit <= 0) {
            $limit = $defaultLimit;
        }
        $limit = min($limit, $maxLimit);

        $cursor = trim((string) ($this->request->getGet('cursor') ?? ''));
        $page = $this->positiveIntFromQuery('page', 1);

        return [
            'search'  => $search,
            'filters' => $filters,
            'sort'    => $sort,
            'limit'   => $limit,
            'cursor'  => $cursor,
            'page'    => $page,
        ];
    }

    /**
     * Build API list params for server-driven table queries.
     *
     * @param array{
     *   search?: string,
     *   filters?: array<string, string>,
     *   sort?: string,
     *   limit?: int,
     *   cursor?: string,
     *   page?: int
     * } $state
     * @param array<string, scalar> $extra
     * @return array<string, mixed>
     */
    protected function buildTableApiParams(array $state, array $extra = []): array
    {
        $params = [];

        $search = trim((string) ($state['search'] ?? ''));
        if ($search !== '') {
            $params['search'] = $search;
        }

        $filters = $state['filters'] ?? [];
        if (is_array($filters) && $filters !== []) {
            $params['filter'] = $filters;
        }

        $sort = trim((string) ($state['sort'] ?? ''));
        if ($sort !== '') {
            $params['sort'] = $sort;
        }

        $limit = (int) ($state['limit'] ?? 25);
        if ($limit > 0) {
            $params['limit'] = $limit;
        }

        $cursor = trim((string) ($state['cursor'] ?? ''));
        if ($cursor !== '') {
            $params['cursor'] = $cursor;
        } else {
            $page = (int) ($state['page'] ?? 1);
            $params['page'] = max(1, $page);
        }

        foreach ($extra as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $params[$key] = $value;
        }

        return $params;
    }

    /**
     * @param array<string, mixed> $response
     * @param array<string, mixed> $state
     */
            protected function resolveTablePagination(array $response, array $state, int $visibleCount = 0): array
            {
                $data = $response['data'] ?? [];
                if (! is_array($data)) {
                    $data = [];
                }
        
                $meta = $data['meta'] ?? [];
                if (! is_array($meta)) {
                    $meta = [];
                }
        
                $nextCursor = (string) ($meta['nextCursor'] ?? $data['nextCursor'] ?? '');
                $prevCursor = (string) ($meta['prevCursor'] ?? $data['prevCursor'] ?? '');
                $hasMore = (bool) ($meta['hasMore'] ?? ($nextCursor !== ''));
        
                $currentPage = (int) ($meta['page'] ?? $meta['currentPage'] ?? $data['page'] ?? $data['currentPage'] ?? ($state['page'] ?? 1));
                $lastPage = (int) ($meta['lastPage'] ?? $data['lastPage'] ?? $currentPage);
                $total = (int) ($meta['total'] ?? $data['total'] ?? $meta['totalEstimate'] ?? $visibleCount);
        
                $isCursorMode = $nextCursor !== '' || $prevCursor !== '' || ((string) ($state['cursor'] ?? '')) !== '';
        
                return [
                    'mode'           => $isCursorMode ? 'cursor' : 'page',
                    'current_page'   => max(1, $currentPage),
                    'last_page'      => max(1, $lastPage),
                    'total'          => max(0, $total),
                    'next_cursor'    => $nextCursor,
                    'prev_cursor'    => $prevCursor,
                    'has_more'       => $hasMore,
                    'current_cursor' => (string) ($state['cursor'] ?? ''),
                ];
            }}
