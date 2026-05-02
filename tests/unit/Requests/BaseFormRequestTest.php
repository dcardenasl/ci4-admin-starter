<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Support\Requests\BaseFormRequest;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Tests for BaseFormRequest typed helper methods (postInt, postBool, postArray).
 *
 * @internal
 */
final class BaseFormRequestTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    // ─── postInt() ───────────────────────────────────────────────────────────

    public function testPostIntReturnsIntegerFromNumericString(): void
    {
        $form = $this->makeForm(['count' => '42']);
        $this->assertSame(42, $form->getTestInt('count'));
    }

    public function testPostIntReturnsDefaultForMissingField(): void
    {
        $form = $this->makeForm([]);
        $this->assertSame(0, $form->getTestInt('count'));
        $this->assertSame(99, $form->getTestInt('count', 99));
    }

    public function testPostIntReturnsDefaultForNonNumericString(): void
    {
        $form = $this->makeForm(['count' => 'abc']);
        $this->assertSame(0, $form->getTestInt('count'));
    }

    public function testPostIntTruncatesDecimal(): void
    {
        $form = $this->makeForm(['count' => '3.9']);
        $this->assertSame(3, $form->getTestInt('count'));
    }

    // ─── postBool() ──────────────────────────────────────────────────────────

    public function testPostBoolReturnsTrueForTruthyValues(): void
    {
        foreach (['1', 'true', 'on', 'yes', 'TRUE', 'YES', 'On'] as $value) {
            $form = $this->makeForm(['flag' => $value]);
            $this->assertTrue($form->getTestBool('flag'), "Expected true for value: {$value}");
        }
    }

    public function testPostBoolReturnsFalseForFalsyValues(): void
    {
        foreach (['0', 'false', 'off', 'no', '', 'null'] as $value) {
            $form = $this->makeForm(['flag' => $value]);
            $this->assertFalse($form->getTestBool('flag'), "Expected false for value: {$value}");
        }
    }

    public function testPostBoolReturnsFalseForMissingField(): void
    {
        $form = $this->makeForm([]);
        $this->assertFalse($form->getTestBool('flag'));
    }

    // ─── postArray() ─────────────────────────────────────────────────────────

    public function testPostArrayReturnsArrayValue(): void
    {
        $form = $this->makeForm(['tags' => ['a', 'b', 'c']]);
        $this->assertSame(['a', 'b', 'c'], $form->getTestArray('tags'));
    }

    public function testPostArrayReturnsEmptyArrayForMissingField(): void
    {
        $form = $this->makeForm([]);
        $this->assertSame([], $form->getTestArray('tags'));
    }

    public function testPostArrayReturnsEmptyArrayForStringField(): void
    {
        $form = $this->makeForm(['tags' => 'not-an-array']);
        $this->assertSame([], $form->getTestArray('tags'));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $postData
     */
    private function makeForm(array $postData): object
    {
        $request = service('request');
        $request->setGlobal('post', $postData);

        // Anonymous concrete subclass that exposes protected methods for testing
        return new class ($request, service('validation')) extends BaseFormRequest {
            public function rules(): array
            {
                return [];
            }

            protected function fields(): array
            {
                return [];
            }

            public function getTestInt(string $field, int $default = 0): int
            {
                return $this->postInt($field, $default);
            }

            public function getTestBool(string $field): bool
            {
                return $this->postBool($field);
            }

            /** @return array<mixed> */
            public function getTestArray(string $field): array
            {
                return $this->postArray($field);
            }
        };
    }
}
