<?php

namespace Tests\Feature;

use App\Services\FileApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * Tests for the complete file upload flow.
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
        $this->assertStringContainsString('name="file"', $body);
        $this->assertStringContainsString('onFileChange(event)', $body);
        $this->assertStringContainsString(lang('Files.fileReady'), $body);
    }

    public function testIndexRedirectsToLoginWithoutSession(): void
    {
        $result = $this->get('/files');
        $result->assertRedirectTo(site_url('login'));
    }

    // ─── Download ─────────────────────────────────────────────────

    public function testDownloadSuccessReturnsBinaryResponse(): void
    {
        $mock = $this->createMock(FileApiService::class);
        $mock->expects($this->once())
            ->method('getDownload')
            ->with('abc-123')
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => ['originalName' => 'test.pdf'],
                'raw'         => '%PDF-1.7 content',
                'headers'     => ['content-type' => 'application/pdf'],
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('fileApiService', $mock);

        $result = $this->withSession($this->authSession)->get('/files/abc-123/download');

        $result->assertStatus(200);
        $this->assertStringContainsString('%PDF-1.7 content', $result->getBody());
        $result->assertHeader('Content-Type', 'application/pdf');
    }

    public function testDownloadApiFailureReturnsNotFound(): void
    {
        $mock = $this->createMock(FileApiService::class);
        $mock->method('getDownload')->willReturn([
            'ok' => false,
            'status' => 404,
            'data' => [],
            'raw' => '',
            'headers' => [],
            'messages' => [],
            'fieldErrors' => []
        ]);

        Services::injectMock('fileApiService', $mock);

        $result = $this->withSession($this->authSession)->get('/files/not-found/download');

        $result->assertStatus(404);
        $this->assertStringContainsString('File not found', $result->getBody());
    }

    // ─── FileApiService unit tests ────────────────────────────────

    public function testFileApiServiceUploadConvertsToBase64(): void
    {
        $tmpFile = $this->createTempFile('hello', 'test.txt');
        $expectedBase64 = 'data:text/plain;base64,' . base64_encode('hello');

        $mockClient = $this->createMock(\App\Libraries\ApiClientInterface::class);
        $mockClient->expects($this->once())
            ->method('post')
            ->with('/files/upload', $this->callback(function($data) use ($expectedBase64) {
                return $data['file'] === $expectedBase64 && $data['filename'] === 'test.txt';
            }))
            ->willReturn($this->apiOkResponse(['id' => 1], 201));

        $service = new FileApiService($mockClient);
        $result = $service->upload('file', $tmpFile, 'test.txt', 'text/plain', ['visibility' => 'private']);

        $this->assertTrue($result['ok']);
        @unlink($tmpFile);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function createTempFile(string $content, string $name): string
    {
        $path = WRITEPATH . $name;
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
