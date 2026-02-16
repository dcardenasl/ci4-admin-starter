<?php

namespace App\Controllers;

use App\Libraries\ApiClient;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Psr\Log\LoggerInterface;

abstract class BaseWebController extends BaseController
{
    protected ApiClient $apiClient;

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

    protected function getFieldErrors(array $response): array
    {
        return $response['fieldErrors'] ?? [];
    }

    /**
     * Extract the first message from an API response array.
     */
    protected function firstMessage(array $response, string $fallback): string
    {
        $messages = $response['messages'] ?? [];

        if (is_array($messages) && isset($messages[0])) {
            return (string) $messages[0];
        }

        return $fallback;
    }

    /**
     * Extract the nested 'data' items from an API list response.
     */
    protected function extractItems(array $response): array
    {
        $data = $response['data'] ?? [];

        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        return is_array($data) ? $data : [];
    }

    /**
     * Extract the nested 'data' payload from an API single-object response.
     */
    protected function extractData(array $response): array
    {
        $payload = $response['data'] ?? [];

        if (isset($payload['data']) && is_array($payload['data'])) {
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
                'messages'    => [lang('App.connectionError')],
                'fieldErrors' => [],
            ];
        }
    }
}
