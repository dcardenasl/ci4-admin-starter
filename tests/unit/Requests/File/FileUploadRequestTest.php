<?php

namespace Tests\Unit\Requests\File;

use App\Requests\File\FileUploadRequest;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Validation\ValidationInterface;
use Config\Services;

/**
 * @internal
 */
final class FileUploadRequestTest extends CIUnitTestCase
{
    public function testDataReturnsFilenameWhenFileIsPresent(): void
    {
        $request = service('request');
        
        $mockFile = $this->createMock(\CodeIgniter\HTTP\Files\UploadedFile::class);
        $mockFile->method('isValid')->willReturn(true);
        $mockFile->method('getName')->willReturn('test.png');
        
        // Mock getFile to return our file
        $request = $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);
        $request->method('getFile')->with('file')->willReturn($mockFile);
        $request->method('getPost')->willReturn([]);

        $formRequest = new FileUploadRequest($request, $this->createValidationMock());
        $data = $formRequest->data();

        $this->assertSame('test.png', $data['file']);
    }

    public function testPayloadDefaultsVisibilityToPrivateWhenEmpty(): void
    {
        $request = service('request');
        $request->setGlobal('post', []);

        $formRequest = new FileUploadRequest($request, $this->createValidationMock());
        $payload = $formRequest->payload();

        $this->assertSame('private', $payload['visibility']);
    }

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    private function createValidationMock(): ValidationInterface
    {
        return $this->createMock(ValidationInterface::class);
    }
}
