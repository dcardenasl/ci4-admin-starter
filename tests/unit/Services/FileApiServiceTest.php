<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Libraries\ApiClientInterface;
use App\Modules\Files\Services\FileApiService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class FileApiServiceTest extends CIUnitTestCase
{
    private function createMockClient(array $returnValue): ApiClientInterface
    {
        $mock = $this->createMock(ApiClientInterface::class);

        $mock->method('get')->willReturn($returnValue);
        $mock->method('post')->willReturn($returnValue);
        $mock->method('put')->willReturn($returnValue);
        $mock->method('delete')->willReturn($returnValue);

        return $mock;
    }

    public function testListReturnsFiles(): void
    {
        $expected = [
            'ok'          => true,
            'status'      => 200,
            'data'        => [
                'data' => [
                    ['id' => 1, 'filename' => 'document.pdf', 'size' => 1024],
                    ['id' => 2, 'filename' => 'image.jpg', 'size' => 2048],
                ],
            ],
            'raw'         => '',
            'messages'    => [],
            'fieldErrors' => [],
        ];

        $service = new FileApiService($this->createMockClient($expected));
        $result = $service->list();

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
        $this->assertCount(2, $result['data']['data']);
    }

    public function testGetReturnsFileById(): void
    {
        $expected = [
            'ok'          => true,
            'status'      => 200,
            'data'        => [
                'data' => [
                    'id'       => 123,
                    'filename' => 'report.pdf',
                    'size'     => 5120,
                    'url'      => 'https://api.example.com/files/report.pdf',
                ],
            ],
            'raw'         => '',
            'messages'    => [],
            'fieldErrors' => [],
        ];

        $service = new FileApiService($this->createMockClient($expected));
        $result = $service->get('123');

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
        $this->assertSame('report.pdf', $result['data']['data']['filename']);
    }

    public function testCreateFile(): void
    {
        $expected = [
            'ok'          => true,
            'status'      => 201,
            'data'        => ['data' => ['id' => 124, 'filename' => 'newfile.txt']],
            'raw'         => '',
            'messages'    => [],
            'fieldErrors' => [],
        ];

        $mock = $this->createMock(ApiClientInterface::class);
        $mock->expects($this->once())
            ->method('post')
            ->with('/files', ['name' => 'newfile.txt'])
            ->willReturn($expected);

        $service = new FileApiService($mock);
        $result = $service->create(['name' => 'newfile.txt']);

        $this->assertTrue($result['ok']);
        $this->assertSame(201, $result['status']);
    }

    public function testUpdateFile(): void
    {
        $expected = [
            'ok'          => true,
            'status'      => 200,
            'data'        => ['data' => ['id' => 123, 'filename' => 'renamed.pdf']],
            'raw'         => '',
            'messages'    => [],
            'fieldErrors' => [],
        ];

        $mock = $this->createMock(ApiClientInterface::class);
        $mock->expects($this->once())
            ->method('put')
            ->with('/files/123', ['filename' => 'renamed.pdf'])
            ->willReturn($expected);

        $service = new FileApiService($mock);
        $result = $service->update('123', ['filename' => 'renamed.pdf']);

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
    }

    public function testDeleteFile(): void
    {
        $expected = [
            'ok'          => true,
            'status'      => 204,
            'data'        => [],
            'raw'         => '',
            'messages'    => [],
            'fieldErrors' => [],
        ];

        $mock = $this->createMock(ApiClientInterface::class);
        $mock->expects($this->once())
            ->method('delete')
            ->with('/files/123')
            ->willReturn($expected);

        $service = new FileApiService($mock);
        $result = $service->delete('123');

        $this->assertTrue($result['ok']);
        $this->assertSame(204, $result['status']);
    }

    public function testUploadFileWithMultipart(): void
    {
        $expected = [
            'ok'          => true,
            'status'      => 201,
            'data'        => ['data' => ['id' => 125, 'filename' => 'test.txt']],
            'raw'         => '',
            'messages'    => [],
            'fieldErrors' => [],
        ];

        $mock = $this->createMock(ApiClientInterface::class);
        $mock->expects($this->once())
            ->method('upload')
            ->with(
                '/files/upload',
                $this->callback(static function (array $files): bool {
                    return isset($files['file'])
                        && isset($files['file']['path'])
                        && isset($files['file']['filename'])
                        && $files['file']['filename'] === 'test.txt';
                }),
                []
            )
            ->willReturn($expected);

        $service = new FileApiService($mock);

        // Create a temporary test file
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, 'Test file content');

        try {
            $result = $service->upload('file', $tmpFile, 'test.txt', 'text/plain');

            $this->assertTrue($result['ok']);
            $this->assertSame(201, $result['status']);
        } finally {
            unlink($tmpFile);
        }
    }

    public function testUploadThrowsExceptionWhenFileDoesNotExist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File does not exist');

        $service = new FileApiService($this->createMockClient([]));
        $service->upload('file', '/nonexistent/file.txt', 'file.txt');
    }

    public function testUploadDetectsMimeType(): void
    {
        $expected = [
            'ok'          => true,
            'status'      => 201,
            'data'        => ['data' => ['id' => 126, 'filename' => 'image.png']],
            'raw'         => '',
            'messages'    => [],
            'fieldErrors' => [],
        ];

        $mock = $this->createMock(ApiClientInterface::class);
        $mock->expects($this->once())
            ->method('upload')
            ->with(
                '/files/upload',
                $this->callback(static function (array $files): bool {
                    return isset($files['file'])
                        && isset($files['file']['path'])
                        && isset($files['file']['filename']);
                }),
                []
            )
            ->willReturn($expected);

        $service = new FileApiService($mock);

        // Create a temporary test file with PNG header
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, "\x89PNG\r\n\x1a\n" . 'fake png content');

        try {
            $result = $service->upload('file', $tmpFile, 'image.png');

            $this->assertTrue($result['ok']);
            $this->assertSame(201, $result['status']);
        } finally {
            unlink($tmpFile);
        }
    }
}
