<?php

namespace Tests\Unit\Helpers;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class FormHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper(['form']);
    }

    public function testGetFieldErrorReturnsEmptyStringWhenNoErrors(): void
    {
        $session = session();
        $session->set('fieldErrors', []);

        $result = get_field_error('email');

        $this->assertSame('', $result);
    }

    public function testGetFieldErrorReturnsValueFromSession(): void
    {
        $session = session();
        $session->set('fieldErrors', [
            'email'    => 'Email is required',
            'password' => 'Password must be at least 8 characters',
        ]);

        $emailError = get_field_error('email');
        $passwordError = get_field_error('password');

        $this->assertSame('Email is required', $emailError);
        $this->assertSame('Password must be at least 8 characters', $passwordError);
    }

    public function testGetFieldErrorReturnsEmptyWhenFieldMissing(): void
    {
        $session = session();
        $session->set('fieldErrors', [
            'email' => 'Email is required',
        ]);

        $result = get_field_error('password');

        $this->assertSame('', $result);
    }

    public function testHasFieldErrorReturnsTrueWhenErrorExists(): void
    {
        $session = session();
        $session->set('fieldErrors', [
            'email' => 'Email is required',
        ]);

        $result = has_field_error('email');

        $this->assertTrue($result);
    }

    public function testHasFieldErrorReturnsFalseWhenNoError(): void
    {
        $session = session();
        $session->set('fieldErrors', []);

        $result = has_field_error('email');

        $this->assertFalse($result);
    }

    public function testFieldErrorClassReturnsEmptyWhenNoError(): void
    {
        $session = session();
        $session->set('fieldErrors', []);

        $result = field_error_class('email');

        $this->assertSame('', $result);
    }

    public function testFieldErrorClassReturnsDefaultClassWhenErrorExists(): void
    {
        $session = session();
        $session->set('fieldErrors', [
            'email' => 'Email is required',
        ]);

        $result = field_error_class('email');

        $this->assertSame('border-red-500 focus:border-red-500 focus:ring-red-500', $result);
    }

    public function testFieldErrorClassReturnsCustomClassWhenErrorExists(): void
    {
        $session = session();
        $session->set('fieldErrors', [
            'email' => 'Email is required',
        ]);

        $result = field_error_class('email', 'custom-error-class');

        $this->assertSame('custom-error-class', $result);
    }

    public function testRenderFieldErrorReturnsEmptyStringWhenNoError(): void
    {
        $session = session();
        $session->set('fieldErrors', []);

        $result = render_field_error('email');

        $this->assertSame('', $result);
    }

    public function testRenderFieldErrorRendersHtmlWhenErrorPresent(): void
    {
        $session = session();
        $session->set('fieldErrors', [
            'email' => 'Email is required',
        ]);

        $result = render_field_error('email');

        $this->assertStringContainsString('<p class="mt-1 text-sm text-red-600">', $result);
        $this->assertStringContainsString('Email is required', $result);
        $this->assertStringContainsString('</p>', $result);
    }

    public function testRenderFieldErrorEscapesOutput(): void
    {
        $session = session();
        $session->set('fieldErrors', [
            'email' => '<script>alert("xss")</script>',
        ]);

        $result = render_field_error('email');

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    public function testGetFieldErrorWithNullFieldErrors(): void
    {
        $session = session();
        $session->set('fieldErrors', null);

        $result = get_field_error('email');

        $this->assertSame('', $result);
    }

    public function testMultipleFieldsWithDifferentErrors(): void
    {
        $session = session();
        $session->set('fieldErrors', [
            'first_name' => 'First name is required',
            'email'      => 'Email format is invalid',
            'password'   => 'Password is too weak',
        ]);

        $firstName = get_field_error('first_name');
        $email = get_field_error('email');
        $password = get_field_error('password');
        $phone = get_field_error('phone');

        $this->assertSame('First name is required', $firstName);
        $this->assertSame('Email format is invalid', $email);
        $this->assertSame('Password is too weak', $password);
        $this->assertSame('', $phone);
    }
}
