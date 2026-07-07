<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Controllers\BaseWebController;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Tests for BaseWebController protected utility methods:
 * extractItems, extractData, localizeApiMessage, safeApiCall, getFieldErrors, firstMessage.
 *
 * An anonymous concrete subclass exposes each protected method as public so tests can
 * call them directly without going through the full HTTP controller stack.
 *
 * @internal
 */
final class BaseWebControllerTest extends CIUnitTestCase
{
    private object $ctrl;

    protected function setUp(): void
    {
        parent::setUp();
        // Anonymous subclass — exposes protected methods for unit testing.
        // initController() is intentionally NOT called: the methods under test are pure
        // utility methods that do not depend on $this->apiClient, $this->session, etc.
        $this->ctrl = new class () extends BaseWebController {
            /** @param array<string, mixed> $response */
            public function callExtractItems(array $response): mixed
            {
                return $this->extractItems($response);
            }

            /** @param array<string, mixed> $response */
            public function callExtractData(array $response): mixed
            {
                return $this->extractData($response);
            }

            public function callLocalizeApiMessage(string $message): string
            {
                return $this->localizeApiMessage($message);
            }

            public function callSafeApiCall(callable $callback): mixed
            {
                return $this->safeApiCall($callback);
            }

            /** @param array<string, mixed> $response */
            public function callGetFieldErrors(array $response): mixed
            {
                return $this->getFieldErrors($response);
            }

            /** @param array<string, mixed> $response */
            public function callFirstMessage(array $response, string $fallback): string
            {
                return $this->firstMessage($response, $fallback);
            }
        };
    }

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    // ─── extractItems() ──────────────────────────────────────────────────────

    public function testExtractItemsReturnsPaginatedDataArray(): void
    {
        $items    = [['id' => 1], ['id' => 2]];
        $response = ['data' => ['data' => $items, 'meta' => ['total' => 2]]];

        $this->assertSame($items, $this->ctrl->callExtractItems($response));
    }

    public function testExtractItemsReturnsTopLevelDataArray(): void
    {
        $items    = [['id' => 1], ['id' => 2]];
        $response = ['data' => $items];

        $this->assertSame($items, $this->ctrl->callExtractItems($response));
    }

    public function testExtractItemsReturnsEmptyArrayOnMissingData(): void
    {
        $this->assertSame([], $this->ctrl->callExtractItems([]));
    }

    public function testExtractItemsReturnsEmptyArrayWhenDataIsNotArray(): void
    {
        $this->assertSame([], $this->ctrl->callExtractItems(['data' => 'not-an-array']));
    }

    public function testExtractItemsReturnsEmptyArrayWhenOkIsFalse(): void
    {
        $response = ['ok' => false, 'data' => [['id' => 1]]];

        $this->assertSame([], $this->ctrl->callExtractItems($response));
    }

    // ─── extractData() ───────────────────────────────────────────────────────

    public function testExtractDataReturnsSingleObjectPayload(): void
    {
        $object   = ['id' => 1, 'name' => 'Test User'];
        $response = ['data' => $object];

        $this->assertSame($object, $this->ctrl->callExtractData($response));
    }

    public function testExtractDataReturnsPaginationWrapperAsIs(): void
    {
        $wrapper  = ['data' => [['id' => 1]], 'meta' => ['total' => 1]];
        $response = ['data' => $wrapper];

        // When 'meta' is present the wrapper is returned intact (not unwrapped)
        $this->assertSame($wrapper, $this->ctrl->callExtractData($response));
    }

    public function testExtractDataReturnsEmptyArrayOnMissingData(): void
    {
        $this->assertSame([], $this->ctrl->callExtractData([]));
    }

    public function testExtractDataReturnsEmptyArrayWhenOkIsFalse(): void
    {
        $response = ['ok' => false, 'data' => ['id' => 99]];

        $this->assertSame([], $this->ctrl->callExtractData($response));
    }

    // ─── localizeApiMessage() ────────────────────────────────────────────────

    public function testLocalizeApiMessageTranslatesKnownErrorCode(): void
    {
        $result = $this->ctrl->callLocalizeApiMessage('email_already_registered');

        $this->assertNotSame('email_already_registered', $result, 'Should return a translated string, not the raw code');
        $this->assertStringNotContainsString('ApiErrors.', $result);
    }

    public function testLocalizeApiMessageReturnsOriginalForUnknownCode(): void
    {
        $result = $this->ctrl->callLocalizeApiMessage('some_unknown_error_code_xyz');

        $this->assertSame('some_unknown_error_code_xyz', $result);
    }

    public function testLocalizeApiMessageTrimsAndLowercasesInput(): void
    {
        $result = $this->ctrl->callLocalizeApiMessage('  EMAIL_ALREADY_REGISTERED  ');

        $this->assertNotSame('  EMAIL_ALREADY_REGISTERED  ', $result, 'Should normalize and translate');
        $this->assertStringNotContainsString('ApiErrors.', $result);
    }

    // ─── safeApiCall() ───────────────────────────────────────────────────────

    public function testSafeApiCallReturnsCallbackResult(): void
    {
        $expected = ['ok' => true, 'data' => ['id' => 1]];

        $result = $this->ctrl->callSafeApiCall(static fn () => $expected);

        $this->assertSame($expected, $result);
    }

    public function testSafeApiCallReturnsSyntheticErrorOnException(): void
    {
        $result = $this->ctrl->callSafeApiCall(static function (): never {
            throw new \RuntimeException('Connection timeout');
        });

        $this->assertIsArray($result);
        $this->assertFalse($result['ok']);
        $this->assertSame(0, $result['status']);
        $this->assertArrayHasKey('messages', $result);
        $this->assertArrayHasKey('fieldErrors', $result);
    }

    // ─── getFieldErrors() ────────────────────────────────────────────────────

    public function testGetFieldErrorsExtractsAndLocalizesErrors(): void
    {
        $response = ['fieldErrors' => ['email' => 'email_already_registered']];
        $result   = $this->ctrl->callGetFieldErrors($response);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('email', $result);
        // The value should be a translated string, not the raw code
        $this->assertStringNotContainsString('ApiErrors.', $result['email']);
    }

    public function testGetFieldErrorsReturnsEmptyArrayWhenKeyAbsent(): void
    {
        $this->assertSame([], $this->ctrl->callGetFieldErrors([]));
    }

    public function testGetFieldErrorsReturnsEmptyArrayWhenNotArray(): void
    {
        $this->assertSame([], $this->ctrl->callGetFieldErrors(['fieldErrors' => 'invalid']));
    }

    // ─── firstMessage() ──────────────────────────────────────────────────────

    public function testFirstMessageReturnsFirstMessageFromArray(): void
    {
        $response = ['messages' => ['First message', 'Second message']];

        $this->assertSame('First message', $this->ctrl->callFirstMessage($response, 'fallback'));
    }

    public function testFirstMessageReturnsFallbackWhenMessagesAbsent(): void
    {
        $this->assertSame('fallback text', $this->ctrl->callFirstMessage([], 'fallback text'));
    }

    public function testFirstMessageLocalizesMappedErrorCodes(): void
    {
        $response = ['messages' => ['email_already_registered']];
        $result   = $this->ctrl->callFirstMessage($response, 'fallback');

        $this->assertStringNotContainsString('ApiErrors.', $result);
        $this->assertNotSame('email_already_registered', $result, 'Should be localized');
    }

    public function testFirstMessageReadsCanonicalApiErrorEnvelope(): void
    {
        $message = $this->ctrl->callFirstMessage(['message' => 'message text'], 'fallback');
        $general = $this->ctrl->callFirstMessage(['errors' => ['general' => 'general text']], 'fallback');
        $legacy = $this->ctrl->callFirstMessage(['detail' => 'detail text', 'title' => 'title text'], 'fallback');

        $this->assertSame('message text', $message);
        $this->assertSame('general text', $general);
        $this->assertSame('fallback', $legacy);
    }
}
