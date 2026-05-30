<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * ComponentsTest
 *
 * Verifies that standard component views compile successfully and respect contracts.
 *
 * @internal
 */
final class ComponentsTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper(['form', 'url']);
    }

    public function testTextComponentCompilesWithRequiredAndAria(): void
    {
        $html = view('components/form/text', [
            'name' => 'test_name',
            'label' => 'App.name',
            'required' => true,
            'value' => 'Alpha',
        ], ['saveData' => false]);

        $this->assertStringContainsString('id="test_name"', $html);
        $this->assertStringContainsString('name="test_name"', $html);
        $this->assertStringContainsString('value="Alpha"', $html);
        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('aria-required="true"', $html);
    }

    public function testNumberComponentHandlesMinMaxStep(): void
    {
        $html = view('components/form/number', [
            'name' => 'qty',
            'label' => 'App.quantity',
            'min' => 2,
            'max' => 10,
            'step' => 1,
        ], ['saveData' => false]);

        $this->assertStringContainsString('type="number"', $html);
        $this->assertStringContainsString('min="2"', $html);
        $this->assertStringContainsString('max="10"', $html);
        $this->assertStringContainsString('step="1"', $html);
    }

    public function testDecimalComponentRendersDefaultStep(): void
    {
        $html = view('components/form/decimal', [
            'name' => 'price',
            'label' => 'App.price',
            'value' => 12.34,
        ], ['saveData' => false]);

        $this->assertStringContainsString('step="0.01"', $html);
        $this->assertStringContainsString('value="12.34"', $html);
    }

    public function testTextareaComponentRespectsRows(): void
    {
        $html = view('components/form/textarea', [
            'name' => 'desc',
            'label' => 'App.description',
            'value' => 'Hello',
            'rows' => 5,
        ], ['saveData' => false]);

        $this->assertStringContainsString('rows="5"', $html);
        $this->assertStringContainsString('Hello</textarea>', $html);
    }

    public function testSelectComponentIteratesOptions(): void
    {
        $html = view('components/form/select', [
            'name' => 'status',
            'label' => 'App.status',
            'value' => 'draft',
            'options' => [
                'draft' => 'Draft State',
                'active' => 'Active State',
            ]
        ], ['saveData' => false]);

        $this->assertStringContainsString('<option value="draft" selected>', $html);
        $this->assertStringContainsString('Draft State', $html);
        $this->assertStringContainsString('Active State', $html);
    }

    public function testBooleanComponentRendersToggleState(): void
    {
        $html = view('components/form/boolean', [
            'name' => 'is_visible',
            'label' => 'App.is_visible',
            'value' => true,
            'help' => 'App.visible_help',
        ], ['saveData' => false]);

        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('name="is_visible"', $html);
        $this->assertStringContainsString('value="1"', $html);
        $this->assertStringContainsString('checked', $html);
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString(lang('App.visible_help'), $html);
    }


    public function testTagsComponentInjectsJsonValue(): void
    {
        $html = view('components/form/tags', [
            'name' => 'labels',
            'label' => 'App.tags',
            'value' => ['alpha', 'beta'],
        ], ['saveData' => false]);

        $this->assertStringContainsString('tags: ["alpha","beta"]', $html);
    }

    public function testTableCellsFormating(): void
    {
        // text_cell
        $html = view('components/table/text_cell', ['value' => 'VentureOS Scaffolding'], ['saveData' => false]);
        $this->assertStringContainsString('VentureOS Scaffolding', $html);

        // badge_cell
        $html = view('components/table/badge_cell', ['value' => 'published'], ['saveData' => false]);
        $this->assertStringContainsString('Published', $html);
        $this->assertStringContainsString('bg-green-50', $html);

        // boolean_cell
        $html = view('components/table/boolean_cell', ['value' => true], ['saveData' => false]);
        $this->assertStringContainsString('text-green-600', $html);

        // date_cell
        $html = view('components/table/date_cell', ['value' => '2026-05-30 15:34:00'], ['saveData' => false]);
        $this->assertStringContainsString('2026', $html);

        // number_cell
        $html = view('components/table/number_cell', ['value' => 199.99, 'type' => 'currency', 'currency' => 'USD', 'locale' => 'en'], ['saveData' => false]);
        $this->assertStringContainsString('$199.99', $html);
    }

    public function testHeadPartialUsesTranslatedPageTitle(): void
    {
        $html = view('layouts/partials/head', [
            'title' => lang('App.components_title'),
        ], ['saveData' => false]);

        $this->assertStringContainsString('<title>' . lang('App.components_title') . '</title>', $html);
    }
}
