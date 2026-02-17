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
            'report_type' => 'users',
            'group_by'    => 'day',
            'limit'       => '25',
        ];

        $this->assertFalse(has_active_filters([], $defaults));
        $this->assertFalse(has_active_filters(['report_type' => 'users', 'group_by' => 'day', 'limit' => '25'], $defaults));
        $this->assertTrue(has_active_filters(['report_type' => 'files'], $defaults));
        $this->assertTrue(has_active_filters(['group_by' => ''], $defaults));
    }
}

