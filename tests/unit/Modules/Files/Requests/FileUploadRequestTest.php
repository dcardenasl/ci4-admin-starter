<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Files\Requests;

use App\Modules\Files\Requests\FileUploadRequest;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Validation\Validation;
use Config\Services;

/**
 * Verifies the two-stage MIME validation contract:
 *  - rules() emits both ext_in[] and mime_in[] (defense against client-reported MIME).
 *  - validate() additionally checks the real MIME via fileinfo, so a renamed
 *    .php → .jpg cannot pass even with a forged Content-Type.
 */
final class FileUploadRequestTest extends CIUnitTestCase
{
    private function createRequestWithFile(?UploadedFile $file): IncomingRequest
    {
        $request = $this->createMock(IncomingRequest::class);
        $request->method('getFile')->with('file')->willReturn($file);
        $request->method('getPost')->willReturn(null);

        return $request;
    }

    private function buildUpload(string $filename, string $mimeType, string $contents): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'fureq_');
        $this->assertNotFalse($tmp);
        file_put_contents($tmp, $contents);

        // CI4 UploadedFile constructor: (path, originalName, mimeType, size, error, clientPath)
        return new UploadedFile($tmp, $filename, $mimeType, strlen($contents), UPLOAD_ERR_OK);
    }

    public function testRulesEmitMimeInWhitelist(): void
    {
        $req      = new FileUploadRequest($this->createRequestWithFile(null), Services::validation(null, false));
        $rulesArr = $req->rules()['file']['rules'];
        $rules    = implode(' ', $rulesArr);

        $this->assertStringContainsString('ext_in[file,', $rules);
        $this->assertStringContainsString('mime_in[file,', $rules);
        $this->assertStringContainsString('image/jpeg', $rules);
        $this->assertStringContainsString('application/pdf', $rules);
    }

    public function testCheckRealMimeAcceptsConsistentPng(): void
    {
        // 1x1 PNG bytes
        $pngBytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkAAIAAAoAAv/lxKUAAAAASUVORK5CYII='
        );

        $upload = $this->buildUpload('avatar.png', 'image/png', $pngBytes);
        $req    = new FileUploadRequest($this->createRequestWithFile(null), Services::validation(null, false));

        $this->assertNull($req->checkRealMime($upload));
    }

    public function testCheckRealMimeRejectsPhpDisguisedAsJpg(): void
    {
        $phpScript = "<?php\n echo 'pwned'; \n";
        $upload    = $this->buildUpload('evil.jpg', 'image/jpeg', $phpScript);
        $req       = new FileUploadRequest($this->createRequestWithFile(null), Services::validation(null, false));

        $error = $req->checkRealMime($upload);
        $this->assertNotNull($error, 'Renamed .php must trigger a MIME mismatch error.');
        $this->assertSame(lang('Files.file_mime_mismatch'), $error);
    }

    public function testCheckRealMimeRejectsZipDisguisedAsPdf(): void
    {
        // ZIP magic bytes (PK\x03\x04). Renamed to .pdf, client says PDF.
        $zipBytes = "PK\x03\x04" . str_repeat("\0", 26);
        $upload   = $this->buildUpload('report.pdf', 'application/pdf', $zipBytes);
        $req      = new FileUploadRequest($this->createRequestWithFile(null), Services::validation(null, false));

        $error = $req->checkRealMime($upload);
        $this->assertNotNull($error);
    }

    public function testCheckRealMimeRejectsUnknownExtension(): void
    {
        $upload = $this->buildUpload('archive.exe', 'application/octet-stream', "MZ\0\0");
        $req    = new FileUploadRequest($this->createRequestWithFile(null), Services::validation(null, false));

        $error = $req->checkRealMime($upload);
        $this->assertSame(lang('Files.file_extension_not_allowed'), $error);
    }
}
