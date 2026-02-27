<?php

namespace Tests\Unit\Helpers;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class UiHelperTest extends CIUnitTestCase
{
    public function testIsEmailVerifiedReturnsTrueForTimestampFields(): void
    {
        $this->assertTrue(is_email_verified(['emailVerifiedAt' => '2026-01-20T10:00:00Z']));
    }

    public function testIsEmailVerifiedSupportsBooleanNumericAndStringFlags(): void
    {
        $this->assertTrue(is_email_verified(['emailVerified' => true]));
        $this->assertTrue(is_email_verified(['isEmailVerified' => 1]));
        $this->assertTrue(is_email_verified(['verified' => 'true']));
        $this->assertTrue(is_email_verified(['verified' => 'verified']));
        $this->assertFalse(is_email_verified(['emailVerified' => false]));
        $this->assertFalse(is_email_verified(['isEmailVerified' => 0]));
        $this->assertFalse(is_email_verified(['verified' => 'pending']));
    }

    public function testIsEmailVerifiedReturnsFalseWhenNoKnownVerificationFieldExists(): void
    {
        $this->assertFalse(is_email_verified([]));
        $this->assertFalse(is_email_verified(['status' => 'active']));
    }

    public function testHasActiveFiltersReturnsFalseWhenQueryIsEmpty(): void
    {
        $this->assertFalse(has_active_filters([]));
    }

    public function testHasActiveFiltersReturnsTrueWhenSearchHasValue(): void
    {
        $this->assertTrue(has_active_filters(['search' => 'john']));
    }

    public function testHasActiveFiltersIgnoresSortPageAndCursor(): void
    {
        $this->assertFalse(has_active_filters([
            'sort' => '-created_at',
            'page' => '2',
            'cursor' => 'abc123',
        ]));
    }

    public function testHasActiveFiltersUsesDefaultsAsBaseline(): void
    {
        $defaults = [
            'status' => 'active',
            'limit'  => '25',
        ];

        $this->assertFalse(has_active_filters([], $defaults));
        $this->assertFalse(has_active_filters(['status' => 'active', 'limit' => '25'], $defaults));
        $this->assertTrue(has_active_filters(['status' => 'inactive'], $defaults));
        $this->assertTrue(has_active_filters(['status' => ''], $defaults));
    }
}
