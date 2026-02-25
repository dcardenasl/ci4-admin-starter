<?php

namespace Tests\Feature;

use App\Services\FileApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * Tests for the complete file upload flow:
 *
 * Feature tests (controller + routes + filters):
 *   - Index, data, download, delete endpoints
 *   - Upload validation rejection (no file)
 *   - Auth filter on every file route
 *
 * Unit tests (service layer):
 *   - FileApiService delegates correctly to ApiClient
 *   - FileApiService throws on missing file
 *
 * Note: CI4's `uploaded` validation rule calls PHP's is_uploaded_file()
 * which always returns false in tests. Upload success/failure with real
 * files is tested at the service layer instead.
 *
 * @internal
 */
final class FileUploadFlowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private array $authSession = [
        'access_token' => 'test-token',
        'user'         => ['id' => 1, 'email' => 'user@test.com', 'role' => 'user'],
    ];

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    // ─── Index ────────────────────────────────────────────────────

    public function testIndexRendersUploadFormForAuthenticatedUser(): void
    {
        $result = $this->withSession($this->authSession)->get('/files');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('multipart/form-data', $body);
        $this->assertStringContainsString('name="file"', $body);
        $this->assertStringContainsString('onFileChange(event)', $body);
        $this->assertStringContainsString(lang('Files.fileReady'), $body);
        $this->assertStringContainsString(lang('Files.fileTooLarge'), $body);
        $this->assertStringContainsString('name="visibility"', $body);
    }

    public function testIndexRendersFileFieldErrorWhenPresentInSession(): void
    {
        $result = $this->withSession($this->authSession + [
            'fieldErrors' => ['file' => 'Mock file error'],
        ])->get('/files');

        $result->assertStatus(200);
        $this->assertStringContainsString('Mock file error', $result->getBody());
    }

    public function testIndexRedirectsToLoginWithoutSession(): void
    {
        $result = $this->get('/files');

        $result->assertRedirectTo(site_url('login'));
    }

    // ─── Upload – validation ──────────────────────────────────────

    public function testUploadWithoutFileReturnsValidationError(): void
    {
        $result = $this->withSession($this->authSession)->post('/files/upload', [
            'csrf_test_name' => csrf_hash(),
        ]);

        $result->assertRedirectTo(site_url('files'));
        $result->assertSessionHas('fieldErrors');
    }

    public function testUploadRouteRedirectsToLoginWithoutSession(): void
    {
        $result = $this->post('/files/upload', [
            'csrf_test_name' => csrf_hash(),
        ]);

        $result->assertRedirectTo(site_url('login'));
    }

    // ─── Download ─────────────────────────────────────────────────

    public function testDownloadSuccessRedirectsToDownloadUrl(): void
    {
        $mock = $this->createMock(FileApiService::class);
        $mock->expects($this->once())
            ->method('getDownload')
            ->with('abc-123')
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => ['data' => ['url' => 'https://cdn.example.com/files/abc-123.pdf']],
                'raw'         => '',
                'headers'     => [],
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('fileApiService', $mock);

        $result = $this->withSession($this->authSession)->get('/files/abc-123/download');

        $result->assertRedirectTo('https://cdn.example.com/files/abc-123.pdf');
    }

    public function testDownloadFallsBackToUrlFieldWhenDownloadUrlMissing(): void
    {
        $mock = $this->createMock(FileApiService::class);
        $mock->expects($this->once())
            ->method('getDownload')
            ->with('xyz-789')
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => ['data' => ['url' => 'https://cdn.example.com/files/xyz-789.pdf']],
                'raw'         => '',
                'headers'     => [],
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('fileApiService', $mock);

        $result = $this->withSession($this->authSession)->get('/files/xyz-789/download');

        $result->assertRedirectTo('https://cdn.example.com/files/xyz-789.pdf');
    }

    public function testDownloadApiFailureRedirectsWithError(): void
    {
        $mock = $this->createMock(FileApiService::class);
        $mock->expects($this->once())
            ->method('getDownload')
            ->with('not-found')
            ->willReturn([
                'ok'          => false,
                'status'      => 404,
                'data'        => [],
                'raw'         => '',
                'headers'     => [],
                'messages'    => ['File not found.'],
                'fieldErrors' => [],
            ]);

        Services::injectMock('fileApiService', $mock);

        $result = $this->withSession($this->authSession)->get('/files/not-found/download');

        $result->assertRedirectTo(site_url('files'));
        $result->assertSessionHas('error');
    }

    public function testDownloadWithEmptyUrlRedirectsWithError(): void
    {
        $mock = $this->createMock(FileApiService::class);
        $mock->expects($this->once())
            ->method('getDownload')
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => ['data' => []],
                'raw'         => '',
                'headers'     => [],
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('fileApiService', $mock);

        $result = $this->withSession($this->authSession)->get('/files/empty-url/download');

        $result->assertRedirectTo(site_url('files'));
        $result->assertSessionHas('error');
    }

    public function testDownloadReturnsBinaryResponseWhenApiReturnsRawFile(): void
    {
        $mock = $this->createMock(FileApiService::class);
        $mock->expects($this->once())
            ->method('getDownload')
            ->with('binary-file')
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [],
                'raw'         => '%PDF-1.7',
                'headers'     => [
                    'content-type'        => 'application/pdf',
                    'content-disposition' => 'attachment; filename="invoice.pdf"',
                ],
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('fileApiService', $mock);

        $result = $this->withSession($this->authSession)->get('/files/binary-file/download');

        $result->assertStatus(200);
        $this->assertStringContainsString('%PDF-1.7', $result->getBody());
    }

    public function testDownloadRedirectsToLoginWithoutSession(): void
    {
        $result = $this->get('/files/some-id/download');

        $result->assertRedirectTo(site_url('login'));
    }

    // ─── Delete ───────────────────────────────────────────────────

    public function testDeleteSuccessRedirectsWithSuccessMessage(): void
    {
        $mock = $this->createMock(FileApiService::class);
        $mock->expects($this->once())
            ->method('delete')
            ->with('file-456')
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [],
                'raw'         => '',
                'messages'    => ['File deleted.'],
                'fieldErrors' => [],
            ]);

        Services::injectMock('fileApiService', $mock);

        $result = $this->withSession($this->authSession)->post('/files/file-456/delete', [
            'csrf_test_name' => csrf_hash(),
        ]);

        $result->assertRedirectTo(site_url('files'));
        $result->assertSessionHas('success');
    }

    public function testDeleteFailureRedirectsWithError(): void
    {
        $mock = $this->createMock(FileApiService::class);
        $mock->expects($this->once())
            ->method('delete')
            ->with('forbidden-file')
            ->willReturn([
                'ok'          => false,
                'status'      => 403,
                'data'        => [],
                'raw'         => '',
                'messages'    => ['You do not own this file.'],
                'fieldErrors' => [],
            ]);

        Services::injectMock('fileApiService', $mock);

        $result = $this->withSession($this->authSession)->post('/files/forbidden-file/delete', [
            'csrf_test_name' => csrf_hash(),
        ]);

        $result->assertRedirectTo(site_url('files'));
        $result->assertSessionHas('error');
    }

    public function testDeleteRedirectsToLoginWithoutSession(): void
    {
        $result = $this->post('/files/some-id/delete', [
            'csrf_test_name' => csrf_hash(),
        ]);

        $result->assertRedirectTo(site_url('login'));
    }

    // ─── Data endpoint ────────────────────────────────────────────

    public function testDataEndpointReturnsJsonForAuthenticatedUser(): void
    {
        $mock = $this->createMock(FileApiService::class);
        $mock->expects($this->once())
            ->method('list')
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [
                    'data' => [
                        ['id' => 1, 'name' => 'doc.pdf', 'status' => 'active', 'created_at' => '2026-02-01'],
                    ],
                    'current_page' => 1,
                    'last_page'    => 1,
                    'total'        => 1,
                ],
                'raw'         => '',
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('fileApiService', $mock);

        $result = $this->withSession($this->authSession)->get('/files/data');

        $result->assertStatus(200);
        $this->assertStringContainsString('doc.pdf', $result->getBody());
    }

    public function testDataEndpointRedirectsToLoginWithoutSession(): void
    {
        $result = $this->get('/files/data');

        $result->assertRedirectTo(site_url('login'));
    }

    // ─── FileApiService unit tests ────────────────────────────────

    public function testFileApiServiceListCallsApiClientGet(): void
    {
        $expected = $this->apiOkResponse(['data' => []]);

        $mockClient = $this->createMock(\App\Libraries\ApiClientInterface::class);
        $mockClient->expects($this->once())
            ->method('get')
            ->with('/files', ['page' => 1])
            ->willReturn($expected);

        $service = new FileApiService($mockClient);
        $result = $service->list(['page' => 1]);

        $this->assertTrue($result['ok']);
    }

    public function testFileApiServiceUploadCallsApiClientUpload(): void
    {
        $tmpFile = $this->createTempFile('content', 'svc_upload.txt');

        $expected = $this->apiOkResponse(['data' => ['id' => 1]], 201);

        $mockClient = $this->createMock(\App\Libraries\ApiClientInterface::class);
        $mockClient->expects($this->once())
            ->method('upload')
            ->with(
                '/files/upload',
                ['file' => $tmpFile],
                ['visibility' => 'private'],
            )
            ->willReturn($expected);

        $service = new FileApiService($mockClient);
        $result = $service->upload('file', $tmpFile, ['visibility' => 'private']);

        $this->assertTrue($result['ok']);
        $this->assertSame(201, $result['status']);

        @unlink($tmpFile);
    }

    public function testFileApiServiceUploadThrowsWhenFileNotFound(): void
    {
        $mockClient = $this->createMock(\App\Libraries\ApiClientInterface::class);
        $service = new FileApiService($mockClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File does not exist');
        $service->upload('file', '/nonexistent/path/file.txt');
    }

    public function testFileApiServiceUploadWithMetadataPassesFieldsToApiClient(): void
    {
        $tmpFile = $this->createTempFile('meta test', 'svc_meta.txt');

        $mockClient = $this->createMock(\App\Libraries\ApiClientInterface::class);
        $mockClient->expects($this->once())
            ->method('upload')
            ->with(
                '/files/upload',
                ['document' => $tmpFile],
                ['visibility' => 'public', 'description' => 'My report'],
            )
            ->willReturn($this->apiOkResponse(['data' => ['id' => 5]], 201));

        $service = new FileApiService($mockClient);
        $result = $service->upload('document', $tmpFile, [
            'visibility'  => 'public',
            'description' => 'My report',
        ]);

        $this->assertTrue($result['ok']);

        @unlink($tmpFile);
    }

    public function testFileApiServiceDeleteCallsApiClientDelete(): void
    {
        $expected = $this->apiOkResponse([]);

        $mockClient = $this->createMock(\App\Libraries\ApiClientInterface::class);
        $mockClient->expects($this->once())
            ->method('delete')
            ->with('/files/77')
            ->willReturn($expected);

        $service = new FileApiService($mockClient);
        $result = $service->delete(77);

        $this->assertTrue($result['ok']);
    }

    public function testFileApiServiceGetDownloadCallsApiClientGet(): void
    {
        $expected = $this->apiOkResponse(['data' => ['url' => 'https://cdn.example.com/f.pdf']]);

        $mockClient = $this->createMock(\App\Libraries\ApiClientInterface::class);
        $mockClient->expects($this->once())
            ->method('get')
            ->with('/files/55')
            ->willReturn($expected);

        $service = new FileApiService($mockClient);
        $result = $service->getDownload(55);

        $this->assertTrue($result['ok']);
        $this->assertSame('https://cdn.example.com/f.pdf', $result['data']['data']['url']);
    }

    public function testFileApiServiceGetDownloadWithStringId(): void
    {
        $expected = $this->apiOkResponse(['data' => ['url' => 'https://cdn.example.com/uuid.pdf']]);

        $mockClient = $this->createMock(\App\Libraries\ApiClientInterface::class);
        $mockClient->expects($this->once())
            ->method('get')
            ->with('/files/a1b2c3')
            ->willReturn($expected);

        $service = new FileApiService($mockClient);
        $result = $service->getDownload('a1b2c3');

        $this->assertTrue($result['ok']);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function createTempFile(string $content, string $name): string
    {
        $dir = WRITEPATH . 'uploads/';
        if (! is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        $path = $dir . $name;
        file_put_contents($path, $content);

        return $path;
    }

    private function apiOkResponse(array $data, int $status = 200): array
    {
        return [
            'ok'          => true,
            'status'      => $status,
            'data'        => $data,
            'raw'         => '',
            'headers'     => [],
            'messages'    => [],
            'fieldErrors' => [],
        ];
    }
}
