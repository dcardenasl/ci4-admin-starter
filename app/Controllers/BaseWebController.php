<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\ApiClientInterface;
use App\Support\Requests\FormRequestInterface;
use App\Support\SessionKeys;
use App\Traits\TableResponseTrait;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Psr\Log\LoggerInterface;

abstract class BaseWebController extends BaseController
{
    use TableResponseTrait;

    protected ApiClientInterface $apiClient;

    protected \CodeIgniter\Session\Session $session;

    /** @var array<string, mixed> */
    protected array $viewData = [];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);

        $this->apiClient = service('apiClient');
        $this->session = session();
        helper(['url', 'form']);

        /** @var \Config\ApiClient $apiConfig */
        $apiConfig = config('ApiClient');

        $this->viewData = [
            'appName'             => $apiConfig->appName,
            'user'                => $this->session->get(SessionKeys::USER->value),
            'currentLocale'       => Services::language()->getLocale(),
            'supportedLocales'    => config('App')->supportedLocales,
            // Absolute expiration timestamp (UTC seconds) for the current
            // access token. The layout publishes it as a <meta> tag so
            // client-side JS can show a warning before the session lapses,
            // instead of users getting a confusing 401 mid-action.
            'sessionExpiresAt'    => $this->session->get(SessionKeys::EXPIRES_AT->value),
        ];
    }

    /** @param array<string, mixed> $data */
    protected function render(string $view, array $data = [], string $layout = 'layouts/app'): string
    {
        return view($layout, array_merge($this->viewData, $data, [
            'view' => $view,
        ]));
    }

    /** @param array<string, mixed> $data */
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

    /** @param array<string, mixed> $errors */
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
     * @param array<string, mixed> $response
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

    /**
     * @param array<string, mixed> $response
     * @return array<string, string>
     */
    protected function getFieldErrors(array $response): array
    {
        if (! isset($response['fieldErrors'])) {
            return [];
        }

        $fieldErrors = $response['fieldErrors'];

        if (! is_array($fieldErrors)) {
            log_message('warning', '[BaseWebController] Unexpected fieldErrors type: ' . gettype($fieldErrors));

            return [];
        }

        $normalized = [];

        foreach ($fieldErrors as $key => $value) {
            if (! is_string($key) || ! is_scalar($value)) {
                continue;
            }

            $normalized[$key] = $this->localizeApiMessage((string) $value);
        }

        return $normalized;
    }

    /**
     * Extract the first message from an API response array.
     *
     * @param array<string, mixed> $response
     */
    protected function firstMessage(array $response, string $fallback): string
    {
        $messages = $response['messages'] ?? [];

        if (is_array($messages) && isset($messages[0])) {
            return $this->localizeApiMessage((string) $messages[0]);
        }

        return $fallback;
    }

    /**
     * Map known API error codes to localized strings.
     *
     * The API (ci4-api-starter) returns snake_case error codes as message strings.
     * Translations live in app/Language/{locale}/ApiErrors.php so they stay in sync
     * with both supported locales. Add entries there when you discover new API codes.
     */
    protected function localizeApiMessage(string $message): string
    {
        $normalized = strtolower(trim($message));
        $localized  = lang('ApiErrors.' . $normalized);

        // lang() returns the key string (e.g. "ApiErrors.some_code") when not found.
        // Fall back to the original message to avoid showing raw key strings.
        if (is_string($localized) && ! str_starts_with($localized, 'ApiErrors.')) {
            return $localized;
        }

        return $message;
    }

    /**
     * Extract the nested 'data' items from an API list response.
     * Prioritizes the nested 'data' key commonly found in paginated responses.
     *
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    protected function extractItems(array $response): array
    {
        $payload = $response['data'] ?? [];

        // In paginated responses: { data: { data: [...], meta: {...} } }
        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }

        // In simple list responses: { data: [...] }
        return is_array($payload) ? $payload : [];
    }

    /**
     * Extract the nested 'data' payload from an API response.
     * Supports both single object and paginated list responses.
     *
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    protected function extractData(array $response): array
    {
        $payload = $response['data'] ?? [];

        // Avoid returning the nested 'data' array if the payload is a pagination wrapper,
        // unless it's a simple wrapped object.
        if (isset($payload['data']) && is_array($payload['data']) && ! isset($payload['meta']) && ! isset($payload['current_page'])) {
            return $payload['data'];
        }

        return is_array($payload) ? $payload : [];
    }

    /**
     * Wrap an API call in a try/catch, returning a graceful error response on failure.
     *
     * @param callable $callback A closure that performs the API call and returns its result.
     * @return array<string, mixed> The API response array, or a synthetic error response on exception.
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
                'messages'    => [lang('App.connection_error')],
                'fieldErrors' => [],
            ];
        }
    }

    protected function positiveIntFromQuery(string $key, int $default, int $max = 200): int
    {
        $raw = $this->request->getGet($key);
        $value = is_numeric($raw) ? (int) $raw : $default;

        if ($value <= 0) {
            $value = $default;
        }

        return min($value, $max);
    }

    /**
     * Render a resource detail view with a consistent not-found fallback.
     *
     * @param array<string, mixed> $response
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
}
