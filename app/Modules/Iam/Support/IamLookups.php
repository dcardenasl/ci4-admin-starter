<?php

declare(strict_types=1);

namespace App\Modules\Iam\Support;

/**
 * Catalogue cache for IAM `<select>` dropdowns (applications + users).
 *
 * Combines a per-request memo with a cross-request shared cache to keep the
 * admin from paginating the full catalogues on every form render. The cache
 * is invalidated by callers that mutate users/applications via
 * {@see invalidateUsers()} / {@see invalidateApplications()}.
 *
 * Kept narrow on purpose: only the fields admin views need to render.
 */
final class IamLookups
{
    private const CACHE_KEY_APPS  = 'iam_lookups_apps_v1';
    private const CACHE_KEY_USERS = 'iam_lookups_users_v1';
    private const CACHE_TTL       = 120; // seconds

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

        $cache  = service('cache');
        $cached = $cache->get(self::CACHE_KEY_APPS);
        if (is_array($cached)) {
            return $this->applications = self::normalizeApplications($cached);
        }

        $items              = $this->fetchAllPages(
            static fn (array $params): array => service('applicationApiService')->list($params),
            200
        );
        $this->applications = self::normalizeApplications($items);

        $cache->save(self::CACHE_KEY_APPS, $this->applications, self::CACHE_TTL);

        return $this->applications;
    }

    /**
     * @param  array<mixed, mixed>  $rows
     * @return list<array{id: int, name: string}>
     */
    private static function normalizeApplications(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $result[] = [
                'id'   => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
            ];
        }

        return $result;
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

        $cache  = service('cache');
        $cached = $cache->get(self::CACHE_KEY_USERS);
        if (is_array($cached)) {
            return $this->users = self::normalizeUsers($cached);
        }

        $items       = $this->fetchAllPages(
            static fn (array $params): array => service('userApiService')->list($params),
            500
        );
        $this->users = self::normalizeUsers($items);

        $cache->save(self::CACHE_KEY_USERS, $this->users, self::CACHE_TTL);

        return $this->users;
    }

    /**
     * @param  array<mixed, mixed>  $rows
     * @return list<array{id: int, email: string, first_name: string, last_name: string, label: string}>
     */
    private static function normalizeUsers(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $first = trim((string) ($row['first_name'] ?? ''));
            $last  = trim((string) ($row['last_name'] ?? ''));
            $email = (string) ($row['email'] ?? '');
            $name  = trim($first . ' ' . $last);
            $label = $name === '' ? $email : sprintf('%s <%s>', $name, $email);

            $result[] = [
                'id'         => (int) ($row['id'] ?? 0),
                'email'      => $email,
                'first_name' => $first,
                'last_name'  => $last,
                'label'      => $label,
            ];
        }

        return $result;
    }

    /**
     * Drop the cached user catalogue. Call after any successful create/update/
     * delete on a user to keep dropdowns in sync within {@see CACHE_TTL}.
     */
    public static function invalidateUsers(): void
    {
        service('cache')->delete(self::CACHE_KEY_USERS);
    }

    /**
     * Drop the cached application catalogue. Apps rarely change, but call this
     * if an application is created/renamed.
     */
    public static function invalidateApplications(): void
    {
        service('cache')->delete(self::CACHE_KEY_APPS);
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
