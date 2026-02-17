<?php

namespace Tests\Unit\Helpers;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class UiHelperTest extends CIUnitTestCase
{
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
