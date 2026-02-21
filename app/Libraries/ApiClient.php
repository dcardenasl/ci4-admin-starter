<?php

namespace App\Libraries;

use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\URI;
use Config\ApiClient as ApiClientConfig;
use Config\App;
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
        $headers = ['Accept' => 'application/json'];
        if (! empty($this->config->appKey)) {
            $headers['X-App-Key'] = $this->config->appKey;
        }
        $options = [
            'baseURI'         => rtrim($this->config->baseUrl, '/'),
            'timeout'         => $this->config->timeout,
            'connect_timeout' => $this->config->connectTimeout,
            'http_errors'     => false,
            'headers'         => $headers,
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
                'contents' => (string) $value,
            ];
        }

        foreach ($files as $name => $file) {
            if (! is_file($file)) {
                throw new RuntimeException("File not found: {$file}");
            }

            $multipart[] = [
                'name'     => (string) $name,
                'contents' => fopen($file, 'rb'),
                'filename' => basename($file),
            ];
        }

        return $this->request('POST', $path, ['multipart' => $multipart], true);
    }

    public function request(string $method, string $path, array $options = [], bool $authenticated = true): array
    {
        $skipPrefix = (bool) ($options['skip_prefix'] ?? false);
        unset($options['skip_prefix']);

        $uri = $this->buildUri($path, $skipPrefix);

        if ($authenticated) {
            $options = $this->withAuthorization($options);
        }

        $response = $this->http->request($method, $uri, $options);
        $status = $response->getStatusCode();

        if ($authenticated && $status === 401 && $this->attemptTokenRefresh()) {
            $options = $this->withAuthorization($options);
            $response = $this->http->request($method, $uri, $options);
            $status = $response->getStatusCode();
        }

        $payload = json_decode($response->getBody(), true);

        return [
            'ok'          => $status >= 200 && $status < 300,
            'status'      => $status,
            'data'        => is_array($payload) ? $payload : [],
            'raw'         => $response->getBody(),
            'headers'     => $this->extractResponseHeaders($response),
            'messages'    => $this->extractMessages($payload, $status),
            'fieldErrors' => $this->extractFieldErrors($payload),
        ];
    }

    public function attemptTokenRefresh(): bool
    {
        $refreshToken = $this->session->get('refresh_token');

        if (! is_string($refreshToken) || $refreshToken === '') {
            return false;
        }

        $response = $this->http->request('POST', $this->buildUri('/auth/refresh'), [
            'json' => ['refresh_token' => $refreshToken],
        ]);

        if ($response->getStatusCode() !== 200) {
            $this->clearSessionAuth();

            return false;
        }

        $payload = json_decode($response->getBody(), true);

        if (! is_array($payload) || empty($payload['access_token'])) {
            $this->clearSessionAuth();

            return false;
        }

        $this->session->set('access_token', $payload['access_token']);

        if (! empty($payload['refresh_token'])) {
            $this->session->set('refresh_token', $payload['refresh_token']);
        }

        if (! empty($payload['expires_in'])) {
            $this->session->set('token_expires_at', time() + (int) $payload['expires_in']);
        }

        if (! empty($payload['user']) && is_array($payload['user'])) {
            $this->session->set('user', $payload['user']);
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
            if (is_string($key) && $key !== 'general' && is_scalar($value)) {
                $fieldErrors[$key] = (string) $value;
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
}
