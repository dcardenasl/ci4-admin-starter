<?php

namespace App\Controllers;

use App\Libraries\ApiClient;
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
        return $response['fieldErrors'] ?? [];
    }

    /**
     * Extract the first message from an API response array.
     */
    protected function firstMessage(array $response, string $fallback): string
    {
        $messages = $response['messages'] ?? [];

        if (is_array($messages) && isset($messages[0])) {
            return (string) $messages[0];
        }

        return $fallback;
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
        $dateFrom = trim((string) $this->request->getGet('date_from'));
        $dateTo = trim((string) $this->request->getGet('date_to'));

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
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
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

        $nextCursor = (string) ($meta['next_cursor'] ?? $data['next_cursor'] ?? '');
        $prevCursor = (string) ($meta['prev_cursor'] ?? $data['prev_cursor'] ?? '');
        $hasMore = (bool) ($meta['has_more'] ?? ($nextCursor !== ''));

        $currentPage = (int) ($data['current_page'] ?? $meta['current_page'] ?? ($state['page'] ?? 1));
        $lastPage = (int) ($data['last_page'] ?? $meta['last_page'] ?? $currentPage);
        $total = (int) ($data['total'] ?? $meta['total'] ?? $meta['total_estimate'] ?? $visibleCount);

        $isCursorMode = $nextCursor !== '' || $prevCursor !== '' || ((string) ($state['cursor'] ?? '')) !== '';

        return [
            'mode'          => $isCursorMode ? 'cursor' : 'page',
            'current_page'  => max(1, $currentPage),
            'last_page'     => max(1, $lastPage),
            'total'         => max(0, $total),
            'next_cursor'   => $nextCursor,
            'prev_cursor'   => $prevCursor,
            'has_more'      => $hasMore,
            'current_cursor'=> (string) ($state['cursor'] ?? ''),
        ];
    }
}
