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
        // The renderer service persists data across view() calls with the default
        // saveData=true behavior; several display components nest a view() call to
        // components/display/form_section. Without a reset, one test method's saved
        // data can leak into the next test's rendering of the same shared template.
        $this->resetServices();
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
            'label' => 'App.status_label',
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

    public function testRelationComponentWarnsWhenOptionsAreMissing(): void
    {
        $html = view('components/form/relation', [
            'name' => 'category_id',
            'label' => 'App.category',
            'required' => true,
            'options' => [],
            'help' => 'App.visible_help',
        ], ['saveData' => false]);

        $this->assertStringContainsString(lang('App.relation_missing_options'), $html);
        $this->assertStringContainsString(lang('App.relation_missing_options_desc'), $html);
        $this->assertStringNotContainsString('<select', $html);
        $this->assertStringContainsString(lang('App.visible_help'), $html);
    }

    public function testBooleanComponentRendersToggleState(): void
    {
        $html = view('components/form/boolean', [
            'name' => 'is_visible',
            'label' => 'App.is_visible',
            'value' => true,
            'help' => 'App.visible_help',
            'on_label' => 'App.yes',
            'off_label' => 'App.no',
        ], ['saveData' => false]);

        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('name="is_visible"', $html);
        $this->assertStringContainsString('value="1"', $html);
        $this->assertStringContainsString('checked', $html);
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString(lang('App.visible_help'), $html);
        $this->assertStringContainsString(lang('App.yes'), $html);
        $this->assertStringNotContainsString('Enabled', $html);
    }

    public function testDisplayComponentsCompile(): void
    {
        $empty = view('components/display/empty_state', [
            'title' => 'App.no_results',
            'description' => 'App.no_results_desc',
            'actionUrl' => '/dummy/create',
            'actionLabel' => 'App.create',
        ], ['saveData' => false]);

        $reorder = view('components/display/reorder', [
            'items' => [
                ['id' => 1, 'name' => 'Alpha'],
                ['id' => 2, 'name' => 'Beta'],
            ],
            'saveUrl' => '/dummy/reorder',
            'displayKey' => 'name',
            'backUrl' => '/dummy',
        ], ['saveData' => false]);

        $this->assertStringContainsString(lang('App.no_results'), $empty);
        $this->assertStringContainsString(lang('App.create'), $empty);
        $this->assertStringContainsString('Alpha', $reorder);
        $this->assertStringContainsString('Beta', $reorder);
        $this->assertStringContainsString('saveOrder', $reorder);
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

    public function testAdminPageHeaderRendersBackLinkEyebrowAndBadge(): void
    {
        $html = view('components/display/admin_page_header', [
            'backUrl' => '/dummy',
            'backLabel' => 'App.back',
            'eyebrow' => 'App.details',
            'title' => 'App.name',
            'subtitle' => 'Some subtitle',
            'badge' => '<span class="badge">Active</span>',
        ], ['saveData' => false]);

        $this->assertStringContainsString('href="/dummy"', $html);
        $this->assertStringContainsString(lang('App.back'), $html);
        $this->assertStringContainsString(lang('App.details'), $html);
        $this->assertStringContainsString(lang('App.name'), $html);
        $this->assertStringContainsString('Some subtitle', $html);
        $this->assertStringContainsString('<span class="badge">Active</span>', $html);
    }

    public function testAdminResourceLayoutRendersMainAndOptionalAside(): void
    {
        $mainOnly = view('components/display/admin_resource_layout', [
            'main' => '<p>Main content</p>',
        ], ['saveData' => false]);

        $this->assertStringContainsString('Main content', $mainOnly);
        $this->assertStringNotContainsString('<aside', $mainOnly);

        $withAside = view('components/display/admin_resource_layout', [
            'main' => '<p>Main content</p>',
            'aside' => '<p>Aside content</p>',
        ], ['saveData' => false]);

        $this->assertStringContainsString('<aside', $withAside);
        $this->assertStringContainsString('Aside content', $withAside);
    }

    public function testFormSectionRendersTitleDescriptionBadgeAndContent(): void
    {
        $html = view('components/display/form_section', [
            'title' => 'App.details',
            'description' => 'App.no_results_desc',
            'badge' => 'App.status_label',
            'content' => '<p>Body</p>',
        ], ['saveData' => false]);

        $this->assertStringContainsString(lang('App.details'), $html);
        $this->assertStringContainsString(lang('App.no_results_desc'), $html);
        $this->assertStringContainsString(lang('App.status_label'), $html);
        $this->assertStringContainsString('<p>Body</p>', $html);
    }

    public function testAdminActionsPanelRendersContentAndDangerZone(): void
    {
        $html = view('components/display/admin_actions_panel', [
            'content' => '<button>Edit</button>',
            'dangerContent' => '<button>Delete</button>',
        ], ['saveData' => false]);

        $this->assertStringContainsString('<button>Edit</button>', $html);
        $this->assertStringContainsString('<button>Delete</button>', $html);
        $this->assertStringContainsString(lang('App.actions'), $html);
    }

    public function testAdminMetaPanelRendersItemsAsDefinitionList(): void
    {
        $html = view('components/display/admin_meta_panel', [
            'title' => 'App.details',
            'items' => [
                ['label' => 'App.name', 'value' => 'Alpha'],
                ['label' => 'App.status_label', 'value' => 'active', 'isHtml' => false],
            ],
        ], ['saveData' => false]);

        $this->assertStringContainsString('<dl', $html);
        $this->assertStringContainsString(lang('App.name'), $html);
        $this->assertStringContainsString('Alpha', $html);
        $this->assertStringContainsString(lang('App.status_label'), $html);
        $this->assertStringContainsString('active', $html);
    }

    public function testLoadingStateRendersDefaultCopyAndSpinnerIcon(): void
    {
        $html = view('components/display/loading_state', [], ['saveData' => false]);

        $this->assertStringContainsString(lang('App.loading'), $html);
        $this->assertStringContainsString(lang('App.loading_refreshing'), $html);
        $this->assertStringContainsString('data-lucide="loader"', $html);
        $this->assertStringContainsString('animate-spin', $html);
    }

    public function testSubmittingOverlayRendersMessageBehindAlpineShowGuard(): void
    {
        $html = view('components/form/submitting_overlay', [
            'message' => 'Saving changes…',
        ], ['saveData' => false]);

        $this->assertStringContainsString('x-show="submitting"', $html);
        $this->assertStringContainsString('Saving changes…', $html);
    }
}
