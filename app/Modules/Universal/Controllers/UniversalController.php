<?php

declare(strict_types=1);

namespace App\Modules\Universal\Controllers;

use App\Controllers\BaseWebController;
use App\Libraries\DomainApiClientInterface;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class UniversalController extends BaseWebController
{
    protected DomainApiClientInterface $domainClient;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);

        /** @var DomainApiClientInterface $client */
        $client = service('domainApiClient');
        $this->domainClient = $client;
    }

    public function index(string $resource): string
    {
        $schemaInfo = $this->getEntitySchema($resource);
        if ($schemaInfo === null) {
            return $this->render('errors/html/error_404', [
                'title' => 'Resource Not Found',
                'message' => "Zero-Code Admin: Entity or template for resource '{$resource}' could not be mapped."
            ], 'layouts/app');
        }

        $entity = $schemaInfo['entity'];
        $fields = $entity['fields'] ?? [];

        return $this->render('admin/universal/index', [
            'title' => $entity['title'] ?? ucfirst($resource),
            'resource' => $resource,
            'fields' => $fields,
            'schema' => $entity,
            'schemaInfo' => $schemaInfo
        ]);
    }

    public function data(string $resource): ResponseInterface
    {
        $schemaInfo = $this->getEntitySchema($resource);
        if ($schemaInfo === null) {
            return $this->response->setJSON(['error' => 'Resource not found'])->setStatusCode(404);
        }

        $entity = $schemaInfo['entity'];
        $fields = $entity['fields'] ?? [];

        $selectFields = array_map(static fn ($f) => $f['name'], $fields);
        if (!in_array('id', $selectFields, true)) {
            $selectFields[] = 'id';
        }

        $apiPath = $entity['api_path'] ?? '/' . $resource;
        return $this->tableDataResponse(
            [],
            $selectFields,
            fn (array $params) => $this->domainClient->get($apiPath, $params),
        );
    }

    public function create(string $resource): string
    {
        $schemaInfo = $this->getEntitySchema($resource);
        if ($schemaInfo === null) {
            return $this->render('errors/html/error_404', [
                'title' => 'Resource Not Found',
                'message' => "Zero-Code Admin: Entity or template for resource '{$resource}' could not be mapped."
            ]);
        }

        return $this->render('admin/universal/form', [
            'title' => 'Create ' . ($schemaInfo['entity']['title'] ?? ucfirst($resource)),
            'resource' => $resource,
            'mode' => 'create',
            'schema' => $schemaInfo['entity'],
            'record' => []
        ]);
    }

    public function store(string $resource): RedirectResponse
    {
        $schemaInfo = $this->getEntitySchema($resource);
        if ($schemaInfo === null) {
            return redirect()->back()->with('error', 'Resource definition not found.');
        }

        $postPayload = $this->request->getPost();
        $payload = is_array($postPayload) ? $postPayload : [];

        // Convert checkbox/boolean values properly
        foreach ($schemaInfo['entity']['fields'] ?? [] as $field) {
            $fieldName = (string) ($field['name'] ?? '');
            if (($field['type'] ?? '') === 'boolean' && $fieldName !== '') {
                $payload[$fieldName] = isset($payload[$fieldName]) && $payload[$fieldName] !== '0';
            }
        }

        $apiPath = $schemaInfo['entity']['api_path'] ?? '/' . $resource;
        $response = $this->safeApiCall(fn () => $this->domainClient->post($apiPath, $payload));

        if (!$response['ok']) {
            return $this->failApi($response, 'Create operation failed.');
        }

        return redirect()->to(route_to('admin.universal.index', $resource))->with('success', 'Resource created successfully.');
    }

    public function edit(string $resource, string $id): string|RedirectResponse
    {
        $schemaInfo = $this->getEntitySchema($resource);
        if ($schemaInfo === null) {
            return redirect()->to('/admin/dashboard')->with('error', 'Resource definition not found.');
        }

        $apiPath = $schemaInfo['entity']['api_path'] ?? '/' . $resource;
        $response = $this->safeApiCall(fn () => $this->domainClient->get($apiPath . '/' . $id));
        if (!$response['ok']) {
            return redirect()->to(route_to('admin.universal.index', $resource))->with('error', 'Record not found.');
        }

        $record = $this->extractData($response);

        return $this->render('admin/universal/form', [
            'title' => 'Edit ' . ($schemaInfo['entity']['title'] ?? ucfirst($resource)),
            'resource' => $resource,
            'mode' => 'edit',
            'schema' => $schemaInfo['entity'],
            'record' => $record,
            'recordId' => $id
        ]);
    }

    public function update(string $resource, string $id): RedirectResponse
    {
        $schemaInfo = $this->getEntitySchema($resource);
        if ($schemaInfo === null) {
            return redirect()->back()->with('error', 'Resource definition not found.');
        }

        $postPayload = $this->request->getPost();
        $payload = is_array($postPayload) ? $postPayload : [];

        // Convert checkbox/boolean values properly
        foreach ($schemaInfo['entity']['fields'] ?? [] as $field) {
            $fieldName = (string) ($field['name'] ?? '');
            if (($field['type'] ?? '') === 'boolean' && $fieldName !== '') {
                $payload[$fieldName] = isset($payload[$fieldName]) && $payload[$fieldName] !== '0';
            }
        }

        $apiPath = $schemaInfo['entity']['api_path'] ?? '/' . $resource;
        $response = $this->safeApiCall(fn () => $this->domainClient->put($apiPath . '/' . $id, $payload));

        if (!$response['ok']) {
            return $this->failApi($response, 'Update operation failed.');
        }

        return redirect()->to(route_to('admin.universal.index', $resource))->with('success', 'Resource updated successfully.');
    }

    public function delete(string $resource, string $id): RedirectResponse
    {
        $schemaInfo = $this->getEntitySchema($resource);
        if ($schemaInfo === null) {
            return redirect()->back()->with('error', 'Resource definition not found.');
        }

        $apiPath = $schemaInfo['entity']['api_path'] ?? '/' . $resource;
        $response = $this->safeApiCall(fn () => $this->domainClient->delete($apiPath . '/' . $id));

        if (!$response['ok']) {
            return $this->failApi($response, 'Delete operation failed.', route_to('admin.universal.index', $resource), false);
        }

        return redirect()->to(route_to('admin.universal.index', $resource))->with('success', 'Resource deleted successfully.');
    }

    /**
     * Scan template directories to resolve the metadata schema for the target resource.
     *
     * @return array<string, mixed>|null
     */
    private function getEntitySchema(string $resource): ?array
    {
        $basePath = env('templates.basePath', ROOTPATH . '../templates');
        $templateFiles = glob(rtrim($basePath, '/') . '/*/template.json');
        if (! is_array($templateFiles)) {
            return null;
        }

        foreach ($templateFiles as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $json = json_decode($content, true);
            if (! is_array($json)) {
                continue;
            }

            $entities = $json['entities'] ?? [];
            foreach ($entities as $entity) {
                // Check explicit resource field first (most reliable)
                $explicitResource = $entity['resource'] ?? null;
                if ($explicitResource !== null && strtolower($explicitResource) === strtolower($resource)) {
                    return [
                        'template' => $json,
                        'entity' => $entity,
                        'resource_path' => '/' . strtolower($resource)
                    ];
                }

                // Fall back to name-based heuristics for backward compatibility
                $name = $entity['name'] ?? '';
                // Pluralize snake-cased entity name to map typical resource paths
                $snakePlural = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name)) . 's';
                $snakePluralCorrect = str_replace('_entrys', '_entries', $snakePlural);

                if (strtolower($name) === strtolower($resource) ||
                    $snakePlural === strtolower($resource) ||
                    $snakePluralCorrect === strtolower($resource)) {

                    return [
                        'template' => $json,
                        'entity' => $entity,
                        'resource_path' => '/' . strtolower($resource)
                    ];
                }
            }
        }
        return null;
    }
}
