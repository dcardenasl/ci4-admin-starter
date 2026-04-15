<?php

declare(strict_types=1);

namespace App\Traits;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Provides reusable methods for server-driven table data responses.
 *
 * This trait extracts table-related logic from BaseWebController, including:
 * - Query state resolution and normalization
 * - API parameter building for list endpoints
 * - Pagination metadata extraction
 * - JSON response passthrough
 */
trait TableResponseTrait
{
    /**
     * Resolve and normalize date range query params.
     *
     * @return array{date_from: string, date_to: string}
     */
    protected function resolveDateRange(int $defaultDays = 30): array
    {
        $date_from = trim((string) $this->request->getGet('date_from'));
        $date_to = trim((string) $this->request->getGet('date_to'));

        $today = new \DateTimeImmutable('today');

        if ($date_to === '' || ! $this->isValidDate($date_to)) {
            $date_to = $today->format('Y-m-d');
        }

        if ($date_from === '' || ! $this->isValidDate($date_from)) {
            $date_from = $today->sub(new \DateInterval('P' . max(1, $defaultDays - 1) . 'D'))->format('Y-m-d');
        }

        if ($date_from > $date_to) {
            [$date_from, $date_to] = [$date_to, $date_from];
        }

        return [
            'date_from' => $date_from,
            'date_to'   => $date_to,
        ];
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
            ->setJSON(is_array($payload) ? $payload : ['message' => lang('App.connection_error')]);
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
        $response = $this->safeApiCall(fn () => $listRequest($params));

        return $this->passthroughApiJsonResponse($response);
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

        $limit = (int) ($this->request->getGet('limit') ?? 0);
        if ($limit <= 0) {
            $limit = (int) ($this->request->getGet('per_page') ?? 0);
        }
        if ($limit <= 0) {
            $limit = $defaultLimit;
        }
        $limit = min($limit, $maxLimit);

        $cursor = trim((string) ($this->request->getGet('cursor') ?? ''));
        $page = $this->positiveIntFromQuery('page', 1);

        return [
            'search'   => $search,
            'filters'  => $filters,
            'sort'     => $sort,
            'limit'    => $limit,
            'cursor'   => $cursor,
            'page'     => $page,
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
            // Keep nested 'filter' for compatibility with some endpoints
            $params['filter'] = $filters;

            // Also flatten filters for endpoints expecting root-level params (like Swagger docs)
            foreach ($filters as $key => $value) {
                if (! isset($params[$key])) {
                    $params[$key] = $value;
                }
            }
        }

        $sort = trim((string) ($state['sort'] ?? ''));
        if ($sort !== '') {
            $params['sort'] = $sort;

            // Convert '-field' to order_by=field&order_dir=DESC for standard API compliance
            $params['order_by'] = ltrim($sort, '-');
            $params['order_dir'] = str_starts_with($sort, '-') ? 'DESC' : 'ASC';
        }

        $limit = (int) ($state['limit'] ?? 25);
        if ($limit > 0) {
            $params['limit'] = $limit;
            $params['per_page'] = $limit;
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

        $next_cursor = (string) ($meta['next_cursor'] ?? $data['next_cursor'] ?? '');
        $prev_cursor = (string) ($meta['prev_cursor'] ?? $data['prev_cursor'] ?? '');
        $has_more = (bool) ($meta['has_more'] ?? ($next_cursor !== ''));

        $current_page = (int) ($meta['current_page'] ?? $data['current_page'] ?? ($state['page'] ?? 1));
        $last_page = (int) ($meta['last_page'] ?? $data['last_page'] ?? $current_page);
        $per_page = (int) ($meta['per_page'] ?? $meta['limit'] ?? $data['per_page'] ?? $data['limit'] ?? ($state['limit'] ?? 25));
        $total_items = (int) ($meta['total_items'] ?? $meta['total'] ?? $data['total_items'] ?? $data['total'] ?? $visibleCount);

        $is_cursor_mode = $next_cursor !== '' || $prev_cursor !== '' || ((string) ($state['cursor'] ?? '')) !== '';

        return [
            'mode'           => $is_cursor_mode ? 'cursor' : 'page',
            'current_page'   => max(1, $current_page),
            'last_page'      => max(1, $last_page),
            'per_page'       => max(1, $per_page),
            'total_items'    => max(0, $total_items),
            'next_cursor'    => $next_cursor,
            'prev_cursor'    => $prev_cursor,
            'has_more'       => $has_more,
            'current_cursor' => (string) ($state['cursor'] ?? ''),
        ];
    }
}
