<?php

namespace App\Libraries;

use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\URI;
use Config\ApiClient as ApiClientConfig;
use Config\App;
use Config\Services;
use RuntimeException;

class ApiClient implements ApiClientInterface
{
    protected ApiClientConfig $config;

    protected CURLRequest $http;

    protected \CodeIgniter\Session\Session $session;

    public function __construct(?ApiClientConfig $config = null)
    {
        $this->config = $config ?? config('ApiClient');
        $this->session = session();
        $appConfig = config(App::class);
        $options = [
            'baseURI'         => rtrim($this->config->baseUrl, '/'),
            'timeout'         => $this->config->timeout,
            'connect_timeout' => $this->config->connectTimeout,
            'http_errors'     => false,
        ];
        $this->http = new CURLRequest(
            $appConfig,
            new URI($options['baseURI']),
            new Response($appConfig),
            $options,
        );
    }

    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, ['query' => $query], true);
    }

    public function post(string $path, array $data = []): array
    {
        return $this->request('POST', $path, ['json' => $data], true);
    }

    public function put(string $path, array $data = []): array
    {
        return $this->request('PUT', $path, ['json' => $data], true);
    }

    public function delete(string $path): array
    {
        return $this->request('DELETE', $path, [], true);
    }

    public function publicPost(string $path, array $data = []): array
    {
        return $this->request('POST', $path, ['json' => $data], false);
    }

    public function publicGet(string $path, array $query = []): array
    {
        return $this->request('GET', $path, ['query' => $query], false);
    }

    public function upload(string $path, array $files = [], array $fields = []): array
    {
        $multipart = [];

        foreach ($fields as $name => $value) {
            $multipart[] = [
                'name'     => (string) $name,
                'contents' => is_scalar($value) ? (string) $value : json_encode($value),
            ];
        }

        foreach ($files as $name => $file) {
            $filePath = is_array($file) ? ($file['path'] ?? '') : $file;
            if (! is_string($filePath) || $filePath === '' || ! is_file($filePath)) {
                throw new RuntimeException("File not found: {$filePath}");
            }

            $filename = is_array($file) && isset($file['filename']) && is_string($file['filename'])
                ? $file['filename']
                : basename($filePath);

            $part = [
                'name'     => (string) $name,
                'contents' => fopen($filePath, 'rb'),
                'filename' => $filename,
            ];

            if (is_array($file) && isset($file['mimeType']) && is_string($file['mimeType'])) {
                $part['mime'] = $file['mimeType'];
            }

            $multipart[] = $part;
        }

        return $this->request('POST', $path, ['multipart' => $multipart], true);
    }

    public function request(string $method, string $path, array $options = [], bool $authenticated = true): array
    {
        $skipPrefix = (bool) ($options['skip_prefix'] ?? false);
        unset($options['skip_prefix']);

        $uri = $this->buildUri($path, $skipPrefix);
        $options = $this->withBaseHeaders($options);

        if ($authenticated) {
            $options = $this->withAuthorization($options);
        }

        if (isset($options['multipart'])) {
            unset($options['json'], $options['body']);
            // Ensure no Content-Type is set so CURL can set the boundary
            if (isset($options['headers']['Content-Type'])) {
                unset($options['headers']['Content-Type']);
            }
            if (isset($options['headers']['content-type'])) {
                unset($options['headers']['content-type']);
            }
        }

        $startedAt = microtime(true);
        $response = $this->http->request($method, $uri, $options);
        $status = $response->getStatusCode();
        $latency = (int) round((microtime(true) - $startedAt) * 1000);

        if ($authenticated && $status === 401 && $this->attemptTokenRefresh()) {
            // Re-open/rewind streams for retry if needed
            if (isset($options['multipart']) && is_array($options['multipart'])) {
                foreach ($options['multipart'] as $part) {
                    if (isset($part['contents']) && is_resource($part['contents'])) {
                        @rewind($part['contents']);
                    }
                }
            }

            $options = $this->withAuthorization($options);
            $response = $this->http->request($method, $uri, $options);
            $status = $response->getStatusCode();
        }

        $body = $response->getBody();
        $payload = json_decode($body, true);

        if ($this->config->logRequests) {
            $logPayload = is_array($payload) ? $this->redactData($payload) : $this->redactData($body);
            $logMsg = "API Response: {$status} ({$latency}ms)\n"
                . "Body: " . (is_array($logPayload) ? json_encode($logPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $logPayload);
            log_message('info', $logMsg);
        }

        return [
            'ok'          => $status >= 200 && $status < 300,
            'status'      => $status,
            'data'        => is_array($payload) ? $payload : [],
            'raw'         => $body,
            'headers'     => $this->extractResponseHeaders($response),
            'messages'    => $this->extractMessages($payload, $status),
            'fieldErrors' => $this->extractFieldErrors($payload),
        ];
    }

    public function attemptTokenRefresh(): bool
    {
        $refreshToken = $this->session->get('refresh_token');

        if (! is_string($refreshToken) || $refreshToken === '') {
            log_message('debug', 'Token refresh failed: No refresh token in session.');
            return false;
        }

        log_message('debug', 'Attempting Token Refresh...');

        $response = $this->http->request('POST', $this->buildUri('/auth/refresh'), [
            'headers' => $this->baseHeaders(),
            'json' => ['refreshToken' => $refreshToken],
        ]);

        $status = $response->getStatusCode();
        log_message('debug', 'Token Refresh Status: ' . $status);

        if ($status !== 200) {
            log_message('debug', 'Token Refresh FAILED. Clearing session.');
            $this->clearSessionAuth();

            return false;
        }

        $payload = json_decode($response->getBody(), true);
        $data = $payload['data'] ?? $payload;

        $accessToken = $data['accessToken'] ?? $data['access_token'] ?? null;
        if (! is_string($accessToken) || $accessToken === '') {
            $this->clearSessionAuth();

            return false;
        }

        $this->session->set('access_token', $accessToken);

        $refreshTokenResponse = $data['refreshToken'] ?? $data['refresh_token'] ?? null;
        if (! empty($refreshTokenResponse)) {
            $this->session->set('refresh_token', $refreshTokenResponse);
        }

        $expiresIn = $data['expiresIn'] ?? $data['expires_in'] ?? null;
        if (! empty($expiresIn)) {
            $this->session->set('token_expires_at', time() + (int) $expiresIn);
        }

        if (! empty($data['user']) && is_array($data['user'])) {
            $this->session->set('user', $data['user']);
        }

        return true;
    }

    protected function buildUri(string $path, bool $skipPrefix = false): string
    {
        $path = '/' . ltrim($path, '/');

        if ($skipPrefix) {
            return $path;
        }

        if (! str_starts_with($path, $this->config->apiPrefix)) {
            return rtrim($this->config->apiPrefix, '/') . $path;
        }

        return $path;
    }

    protected function withAuthorization(array $options): array
    {
        $headers = $options['headers'] ?? [];
        $token = (string) $this->session->get('access_token');

        if ($token !== '') {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $options['headers'] = $headers;

        return $options;
    }

    protected function withBaseHeaders(array $options): array
    {
        $headers = $options['headers'] ?? [];
        $options['headers'] = array_merge($this->baseHeaders(), $headers);

        return $options;
    }

    /**
     * @return array<string, string>
     */
    protected function baseHeaders(): array
    {
        $headers = [
            'Accept'          => 'application/json',
            'Accept-Language' => $this->resolveLocaleForHeader(),
        ];
        $appKey = trim((string) $this->config->appKey);

        if ($appKey !== '') {
            $headers['X-App-Key'] = $appKey;
        }

        return $headers;
    }

    protected function resolveLocaleForHeader(): string
    {
        $appConfig = config(App::class);
        $supportedLocales = $appConfig->supportedLocales;

        $currentLocale = Services::language()->getLocale();
        $matchedCurrentLocale = $this->matchSupportedLocale($currentLocale, $supportedLocales);
        if ($matchedCurrentLocale !== null) {
            return $matchedCurrentLocale;
        }

        $sessionLocale = $this->session->get('locale');
        if (is_string($sessionLocale)) {
            $matchedSessionLocale = $this->matchSupportedLocale($sessionLocale, $supportedLocales);
            if ($matchedSessionLocale !== null) {
                return $matchedSessionLocale;
            }
        }

        return $appConfig->defaultLocale;
    }

    /**
     * @param list<string> $supportedLocales
     */
    protected function matchSupportedLocale(string $locale, array $supportedLocales): ?string
    {
        $locale = strtolower(trim($locale));
        if ($locale === '') {
            return null;
        }

        if (in_array($locale, $supportedLocales, true)) {
            return $locale;
        }

        $baseLocale = explode('-', $locale)[0];
        if (in_array($baseLocale, $supportedLocales, true)) {
            return $baseLocale;
        }

        return null;
    }

    protected function clearSessionAuth(): void
    {
        $this->session->remove([
            'access_token',
            'refresh_token',
            'token_expires_at',
            'user',
        ]);
        $this->session->regenerate(true);
    }

    protected function extractMessages(mixed $payload, int $status): array
    {
        if (! is_array($payload)) {
            return $status >= 400 ? ['Request failed.'] : [];
        }

        if (isset($payload['message']) && is_scalar($payload['message'])) {
            return [(string) $payload['message']];
        }

        if (isset($payload['messages']) && is_array($payload['messages'])) {
            return array_values(array_filter($payload['messages'], 'is_scalar'));
        }

        if (isset($payload['errors']['general']) && is_scalar($payload['errors']['general'])) {
            return [(string) $payload['errors']['general']];
        }

        return [];
    }

    protected function extractFieldErrors(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $errors = $payload['errors'] ?? [];

        if (! is_array($errors)) {
            return [];
        }

        $fieldErrors = [];

        foreach ($errors as $key => $value) {
            if (! is_string($key) || $key === 'general') {
                continue;
            }

            if (is_scalar($value)) {
                $fieldErrors[$key] = (string) $value;
                continue;
            }

            if (is_array($value)) {
                // If it's an array of errors, take the first one that is a string
                foreach ($value as $entry) {
                    if (is_scalar($entry)) {
                        $fieldErrors[$key] = (string) $entry;
                        break;
                    }
                    if (is_array($entry)) {
                        // Nested array, try one more level or skip
                        foreach ($entry as $subEntry) {
                            if (is_scalar($subEntry)) {
                                $fieldErrors[$key] = (string) $subEntry;
                                break 2;
                            }
                        }
                    }
                }
            }
        }

        return $fieldErrors;
    }

    /**
     * @return array<string, string>
     */
    protected function extractResponseHeaders(\CodeIgniter\HTTP\ResponseInterface $response): array
    {
        return [
            'content-type'        => $response->getHeaderLine('Content-Type'),
            'content-disposition' => $response->getHeaderLine('Content-Disposition'),
            'content-length'      => $response->getHeaderLine('Content-Length'),
        ];
    }

    /**
     * Redacts or truncates data for logging.
     * Prevents large base64 strings or huge response bodies from filling up logs.
     */
    protected function redactData(mixed $data): mixed
    {
        if (is_resource($data)) {
            return '[RESOURCE: ' . get_resource_type($data) . ']';
        }

        if ($data instanceof \CURLFile) {
            return '[CURLFile: ' . $data->getFilename() . ' (' . $data->getMimeType() . ')]';
        }

        if (is_array($data)) {
            $redacted = [];

            foreach ($data as $key => $value) {
                $redacted[$key] = $this->redactData($value);
            }

            return $redacted;
        }

        if (is_string($data)) {
            // Redact base64 Data URIs (common in file uploads)
            if (str_starts_with($data, 'data:') && str_contains($data, ';base64,')) {
                $pos = strpos($data, ';base64,');

                return substr($data, 0, $pos + 8) . '[BASE64_DATA_REDACTED]';
            }

            // Truncate long strings (e.g. over 1000 characters)
            if (strlen($data) > 1000) {
                return substr($data, 0, 100) . '... [TRUNCATED (' . strlen($data) . ' bytes)]';
            }
        }

        return $data;
    }
}
