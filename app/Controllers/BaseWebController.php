<?php

namespace App\Controllers;

use App\Libraries\ApiClientInterface;
use App\Requests\FormRequestInterface;
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

    protected function getFieldErrors(array $response): array
    {
        $fieldErrors = $response['fieldErrors'] ?? [];

        if (! is_array($fieldErrors)) {
            return [];
        }

        $normalized = [];

        foreach ($fieldErrors as $key => $value) {
            if (! is_string($key) || ! is_scalar($value)) {
                continue;
            }

            $normalizedKey = $this->normalizeErrorKey($key);
            $normalized[$normalizedKey] = $this->localizeApiMessage((string) $value);
        }

        return $normalized;
    }

    protected function normalizeErrorKey(string $key): string
    {
        return $key;
    }

    /**
     * Extract the first message from an API response array.
     */
    protected function firstMessage(array $response, string $fallback): string
    {
        $messages = $response['messages'] ?? [];

        if (is_array($messages) && isset($messages[0])) {
            return $this->localizeApiMessage((string) $messages[0]);
        }

        return $fallback;
    }

    protected function localizeApiMessage(string $message): string
    {
        $normalized = trim($message);

        $knownTranslations = [
            'This email is already registered' => lang('Auth.email_already_registered'),
        ];

        return $knownTranslations[$normalized] ?? $message;
    }

    /**
     * Extract the nested 'data' items from an API list response.
     * Prioritizes the nested 'data' key commonly found in paginated responses.
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
                'messages'    => [lang('App.connection_error')],
                'fieldErrors' => [],
            ];
        }
    }

    /**
     * Resolve catalog data from a service's index() method.
     *
     * @param object $service Service with an index() method that returns an ApiResponse.
     * @return array<string, mixed>
     */
    protected function resolveCatalogs(object $service): array
    {
        $response = $this->safeApiCall(fn() => $service->index());
        $data = $this->extractData($response);

        return is_array($data) ? $data : [];
    }

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

    /**
     * Render a resource detail view with a consistent not-found fallback.
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
