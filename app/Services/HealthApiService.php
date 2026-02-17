<?php

namespace App\Services;

use App\Libraries\ApiClientInterface;

class HealthApiService extends BaseApiService
{
    /**
     * @param list<string> $healthPaths
     */
    public function __construct(ApiClientInterface $apiClient, private array $healthPaths = [])
    {
        parent::__construct($apiClient);
    }

    /**
     * @return array{ok: bool, state: string, status: int, path: string, latency_ms: int, message: string}
     */
    public function check(): array
    {
        $paths = $this->healthPaths;
        if ($paths === []) {
            /** @var \Config\ApiClient $config */
            $config = config('ApiClient');
            $paths = $config->healthPaths;
        }

        $degradedResult = null;
        $lastDownResult = [
            'ok'         => false,
            'state'      => 'down',
            'status'     => 0,
            'path'       => $paths[0] ?? '/health',
            'latency_ms' => 0,
            'message'    => lang('Dashboard.apiUnavailable'),
        ];

        foreach ($paths as $path) {
            $startedAt = microtime(true);
            $response = $this->apiClient->request('GET', $path, ['skip_prefix' => true], false);
            $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);
            $status = (int) ($response['status'] ?? 0);
            $message = $this->resolveMessage($response);

            if (($response['ok'] ?? false) === true) {
                return [
                    'ok'         => true,
                    'state'      => 'up',
                    'status'     => $status > 0 ? $status : 200,
                    'path'       => $path,
                    'latency_ms' => $latencyMs,
                    'message'    => lang('Dashboard.apiAvailable'),
                ];
            }

            if ($status > 0) {
                $degradedResult ??= [
                    'ok'         => false,
                    'state'      => 'degraded',
                    'status'     => $status,
                    'path'       => $path,
                    'latency_ms' => $latencyMs,
                    'message'    => $message,
                ];

                continue;
            }

            $lastDownResult = [
                'ok'         => false,
                'state'      => 'down',
                'status'     => 0,
                'path'       => $path,
                'latency_ms' => 0,
                'message'    => $message,
            ];
        }

        if (is_array($degradedResult)) {
            return $degradedResult;
        }

        return $lastDownResult;
    }

    private function resolveMessage(array $response): string
    {
        $payload = $response['data'] ?? [];
        if (! is_array($payload)) {
            return (string) ($response['messages'][0] ?? lang('Dashboard.apiUnavailable'));
        }

        if (isset($payload['status']) && $payload['status'] === 'unhealthy') {
            $criticalMessages = [];
            $checks = $payload['checks'] ?? [];

            if (is_array($checks)) {
                foreach ($checks as $checkName => $checkData) {
                    if (! is_array($checkData)) {
                        continue;
                    }

                    $status = (string) ($checkData['status'] ?? '');
                    if (in_array($status, ['critical', 'unhealthy'], true)) {
                        $checkMessage = (string) ($checkData['message'] ?? '');
                        $criticalMessages[] = $checkName . ($checkMessage !== '' ? ': ' . $checkMessage : '');
                    }
                }
            }

            if ($criticalMessages !== []) {
                return implode(' | ', $criticalMessages);
            }
        }

        return (string) ($response['messages'][0] ?? lang('Dashboard.apiUnavailable'));
    }
}
