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
        $payload = $fields;

        foreach ($files as $name => $file) {
            if (! is_file($file)) {
                throw new RuntimeException("File not found: {$file}");
            }

            // Obtenemos el tipo MIME para construir el Data URI
            $mimeType = mime_content_type($file) ?: 'application/octet-stream';
            $base64Data = base64_encode(file_get_contents($file));

            // Enviamos el archivo con el prefijo de tipo para que el backend reconozca la extensión
            $payload[(string) $name] = "data:{$mimeType};base64,{$base64Data}";
            
            // Aseguramos que el nombre original también se envíe
            if (! isset($payload['filename'])) {
                $payload['filename'] = basename($file);
            }
        }

        return $this->post($path, $payload);
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
            'headers' => $this->baseHeaders(),
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
            // Backend compatibility: some gateways use X-API-Key naming.
            $headers['X-API-Key'] = $appKey;
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
