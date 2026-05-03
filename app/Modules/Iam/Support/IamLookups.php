<?php

declare(strict_types=1);

namespace App\Modules\Iam\Support;

/**
 * Per-request cache of IAM lookup data (applications + users) used to
 * populate <select> options and to enrich list rows with human names
 * instead of raw foreign-key IDs.
 *
 * Kept narrow on purpose: only the fields admin views need to render.
 */
final class IamLookups
{
    /** @var list<array{id: int, name: string}>|null */
    private ?array $applications = null;

    /** @var list<array{id: int, email: string, first_name: string, last_name: string, label: string}>|null */
    private ?array $users = null;

    /**
     * @return list<array{id: int, name: string}>
     */
    public function applications(): array
    {
        if ($this->applications !== null) {
            return $this->applications;
        }

        $items = $this->fetchAllPages(
            static fn (array $params): array => service('applicationApiService')->list($params),
            200
        );

        $this->applications = array_values(array_map(
            static fn (array $row): array => [
                'id'   => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
            ],
            $items
        ));

        return $this->applications;
    }

    /**
     * @return array<int, string> id => name
     */
    public function applicationNames(): array
    {
        $map = [];
        foreach ($this->applications() as $app) {
            $map[$app['id']] = $app['name'];
        }

        return $map;
    }

    /**
     * @return list<array{id: int, email: string, first_name: string, last_name: string, label: string}>
     */
    public function users(): array
    {
        if ($this->users !== null) {
            return $this->users;
        }

        $items = $this->fetchAllPages(
            static fn (array $params): array => service('userApiService')->list($params),
            500
        );

        $this->users = array_values(array_map(
            static function (array $row): array {
                $first = trim((string) ($row['first_name'] ?? ''));
                $last  = trim((string) ($row['last_name'] ?? ''));
                $email = (string) ($row['email'] ?? '');
                $name  = trim($first . ' ' . $last);
                $label = $name === '' ? $email : sprintf('%s <%s>', $name, $email);

                return [
                    'id'         => (int) ($row['id'] ?? 0),
                    'email'      => $email,
                    'first_name' => $first,
                    'last_name'  => $last,
                    'label'      => $label,
                ];
            },
            $items
        ));

        return $this->users;
    }

    /**
     * @return array<int, string> id => human label
     */
    public function userLabels(): array
    {
        $map = [];
        foreach ($this->users() as $u) {
            $map[$u['id']] = $u['label'];
        }

        return $map;
    }

    /**
     * Pull all rows from a paginated `list($params)` API service into a flat array.
     *
     * Caps at $hardLimit to protect the dropdown UI; admins with thousands of
     * users should switch this trait to a server-side autocomplete.
     *
     * @param callable(array<string, mixed>): array<string, mixed> $listFn
     * @return list<array<string, mixed>>
     */
    private function fetchAllPages(callable $listFn, int $hardLimit): array
    {
        $perPage = 100;
        $page    = 1;
        $rows    = [];

        do {
            $response = $listFn(['page' => $page, 'per_page' => $perPage]);
            if (! ($response['ok'] ?? false)) {
                break;
            }

            $body  = $response['data'] ?? [];
            $items = is_array($body['data'] ?? null) ? $body['data'] : [];
            foreach ($items as $item) {
                if (is_array($item)) {
                    $rows[] = $item;
                    if (count($rows) >= $hardLimit) {
                        return $rows;
                    }
                }
            }

            $meta    = is_array($body['meta'] ?? null) ? $body['meta'] : [];
            $total   = (int) ($meta['total'] ?? count($rows));
            $perPage = (int) ($meta['per_page'] ?? $perPage);
            $hasMore = $page * max($perPage, 1) < $total && $items !== [];
            $page++;
        } while ($hasMore);

        return $rows;
    }
}
