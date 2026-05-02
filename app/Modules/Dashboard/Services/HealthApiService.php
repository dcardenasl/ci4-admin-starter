<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Libraries\ApiClientInterface;
use App\Services\BaseApiService;

class HealthApiService extends BaseApiService implements HealthApiServiceInterface
{
    /**
     * @param list<string>|null $healthPaths
     */
    public function __construct(ApiClientInterface $apiClient, private ?array $healthPaths = null)
    {
        parent::__construct($apiClient);
    }

    /**
     * @return array<string, mixed>
     */
    public function check(): array
    {
        $paths = $this->resolveHealthPaths();
        $firstFailure = null;

        try {
            foreach ($paths as $path) {
                $startedAt = microtime(true);
                $response = $this->apiClient->request('GET', $path, ['skip_prefix' => true], false);
                $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

                if ($response['ok'] ?? false) {
                    return $this->normalizeResponse($response, $path, 'up', $latencyMs);
                }

                $state = ($response['status'] ?? 0) > 0 ? 'degraded' : 'down';
                $candidate = $this->normalizeResponse($response, $path, $state, $latencyMs);

                if ($firstFailure === null) {
                    $firstFailure = $candidate;
                }
            }
        } catch (\Throwable) {
            return [
                'ok' => false,
                'status' => 0,
                'state' => 'down',
                'path' => $paths[0] ?? '/health',
                'latency_ms' => 0,
                'data' => ['state' => 'down'],
                'raw' => '',
                'headers' => [],
                'messages' => ['API is unavailable'],
                'fieldErrors' => [],
            ];
        }

        return $firstFailure ?? [
            'ok' => false,
            'status' => 0,
            'state' => 'down',
            'path' => '/health',
            'latency_ms' => 0,
            'data' => ['state' => 'down'],
            'raw' => '',
            'headers' => [],
            'messages' => ['API is unavailable'],
            'fieldErrors' => [],
        ];
    }

    /**
     * @return list<string>
     */
    private function resolveHealthPaths(): array
    {
        $paths = $this->healthPaths ?? config('ApiClient')->healthPaths ?? ['/health'];
        $resolved = array_values(array_filter(
            $paths,
            static fn (mixed $path): bool => is_string($path) && trim($path) !== ''
        ));

        return $resolved !== [] ? $resolved : ['/health'];
    }

    /**
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    private function normalizeResponse(array $response, string $path, string $defaultState, int $latencyMs): array
    {
        $data = $response['data'] ?? [];
        $state = $data['state'] ?? $response['state'] ?? $defaultState;

        $response['state'] = is_string($state) ? $state : $defaultState;
        $response['path'] = $path;
        $response['latency_ms'] = $response['latency_ms'] ?? $latencyMs;
        $response['data'] = is_array($data)
            ? array_merge($data, ['state' => $response['state']])
            : ['state' => $response['state']];

        return $response;
    }
}
