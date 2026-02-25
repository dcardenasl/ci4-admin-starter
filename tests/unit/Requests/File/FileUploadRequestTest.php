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
    public function testDataKeepsFileFieldNullWhenNotString(): void
    {
        $request = service('request');
        $request->setGlobal('post', ['file' => ['unexpected'], 'visibility' => 'public']);

        $formRequest = new FileUploadRequest($request, $this->createValidationMock());
        $data = $formRequest->data();

        $this->assertNull($data['file']);
        $this->assertSame('public', $data['visibility']);
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
