<?php

declare(strict_types=1);

namespace App\Modules\Files\Requests;

use App\Support\FileSizeLimits;
use App\Support\Requests\BaseFormRequest;

class FileUploadRequest extends BaseFormRequest
{
    /**
     * Whitelisted (extension => allowed real MIME types) pairs.
     * The CI4 `mime_in[]` rule validates against the *client-reported* MIME
     * ($_FILES[*]['type']), which is attacker-controllable. We additionally
     * verify the real MIME via UploadedFile::getMimeType() (uses fileinfo) in
     * validate(). Both lists must agree for the upload to pass.
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED_EXTENSION_MIMES = [
        'png'  => ['image/png'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls'  => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'txt'  => ['text/plain'],
        'zip'  => ['application/zip', 'application/x-zip-compressed'],
    ];

    protected function fields(): array
    {
        return ['file'];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function rules(): array
    {
        $maxBytes = FileSizeLimits::effectiveMaxBytes();
        $maxKb = max(1, (int) floor($maxBytes / 1024));

        $extensions = array_keys(self::ALLOWED_EXTENSION_MIMES);
        $mimes      = array_values(array_unique(array_merge(...array_values(self::ALLOWED_EXTENSION_MIMES))));

        return [
            'file' => [
                'label' => lang('Files.file_name'),
                'rules' => [
                    "max_size[file,{$maxKb}]",
                    'ext_in[file,' . implode(',', $extensions) . ']',
                    'mime_in[file,' . implode(',', $mimes) . ']',
                ],
            ],
        ];
    }

    public function messages(): array
    {
        $maxMb = FileSizeLimits::bytesToMb(FileSizeLimits::effectiveMaxBytes());

        return [
            'file' => [
                'max_size' => lang('Files.file_too_large', [$maxMb]),
                'ext_in'   => lang('Files.file_extension_not_allowed'),
                'mime_in'  => lang('Files.file_mime_not_allowed'),
            ],
        ];
    }

    public function data(): array
    {
        $data = parent::data();
        $file = $this->request->getFile('file');
        if ($file && $file->isValid()) {
            $data['file'] = $file->getName();
        }
        return $data;
    }

    public function payload(): array
    {
        return [
            'visibility' => 'private',
        ];
    }

    /**
     * Two-stage MIME validation:
     *   1. Standard CI4 rules (size, extension, client-reported MIME).
     *   2. Real MIME from fileinfo, cross-checked against the extension's
     *      whitelist. Catches `evil.php` renamed to `evil.jpg` with a forged
     *      Content-Type header.
     */
    public function validate(): bool
    {
        $passedRules = parent::validate();
        if (! $passedRules) {
            return false;
        }

        $file = $this->request->getFile('file');
        if ($file === null || ! $file->isValid()) {
            return $passedRules;
        }

        $error = $this->checkRealMime($file);
        if ($error !== null) {
            $this->validation->setError('file', $error);
            return false;
        }

        return true;
    }

    /**
     * Cross-check the file's real MIME (via fileinfo) against the whitelist
     * for its extension. Returns a localized error message on mismatch, or
     * null if the file is consistent. Public so unit tests can exercise this
     * branch without relying on $_FILES superglobal state.
     *
     * @param object $file UploadedFile (typed as object to ease mocking)
     */
    public function checkRealMime(object $file): ?string
    {
        if (! method_exists($file, 'getMimeType') || ! method_exists($file, 'getClientExtension')) {
            return null;
        }

        $extension    = strtolower((string) ($file->getClientExtension() ?: ''));
        if ($extension === '' && method_exists($file, 'guessExtension')) {
            $extension = strtolower((string) ($file->guessExtension() ?: ''));
        }

        $allowedMimes = self::ALLOWED_EXTENSION_MIMES[$extension] ?? null;
        if ($allowedMimes === null) {
            return lang('Files.file_extension_not_allowed');
        }

        $realMime = strtolower((string) ($file->getMimeType() ?: ''));
        if (! in_array($realMime, $allowedMimes, true)) {
            $clientMime = method_exists($file, 'getClientMimeType')
                ? (string) $file->getClientMimeType()
                : '';
            log_message('warning', '[FileUploadRequest] MIME mismatch: ext={ext} client={cli} real={real}', [
                'ext'  => $extension,
                'cli'  => $clientMime,
                'real' => $realMime,
            ]);

            return lang('Files.file_mime_mismatch');
        }

        return null;
    }
}
