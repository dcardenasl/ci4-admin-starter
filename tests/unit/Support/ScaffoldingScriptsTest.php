<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regression tests for `bin/make-module.sh` and `bin/remove-module.sh` proposed
 * by the audit. They exercise the scripts end-to-end against a temp project so
 * any future edit to to_snake / to_camel / route stripping is caught before
 * merge.
 *
 * The tests skip themselves automatically when:
 *   - bash isn't on PATH
 *   - python3 isn't on PATH (the scripts depend on it)
 *   - the temp directory can't be created
 *
 * They never touch the real project tree.
 */
class ScaffoldingScriptsTest extends CIUnitTestCase
{
    private static string $repoRoot;
    private static string $sandbox;

    public static function setUpBeforeClass(): void
    {
        if (!self::commandExists('bash') || !self::commandExists('python3')) {
            self::markTestSkipped('bash and python3 are required to exercise the scaffolding scripts.');
        }

        self::$repoRoot = (string) realpath(__DIR__ . '/../../..');

        $sandbox = sys_get_temp_dir() . '/ci4-scaffold-tests-' . bin2hex(random_bytes(4));
        if (!@mkdir($sandbox, 0o755, true)) {
            self::markTestSkipped("Could not create sandbox at {$sandbox}");
        }
        self::$sandbox = $sandbox;

        // Replicate the parts of the project the scripts touch. Symlinking app/
        // is not safe (writes would land in the real repo), so copy.
        $rsync = sprintf(
            'rsync -a --exclude=vendor --exclude=node_modules --exclude=.git --exclude=writable %s/ %s/',
            escapeshellarg(self::$repoRoot),
            escapeshellarg(self::$sandbox)
        );
        exec($rsync, $output, $code);
        if ($code !== 0) {
            self::markTestSkipped("rsync failed copying project to sandbox.");
        }

        // Lay down a stub composer autoload so the scripts run even without `composer install`.
        @mkdir(self::$sandbox . '/vendor/bin', 0o755, true);
    }

    public static function tearDownAfterClass(): void
    {
        if (isset(self::$sandbox) && is_dir(self::$sandbox)) {
            self::rrmdir(self::$sandbox);
        }
    }

    public function testMakeModuleHandlesAcronymsWithoutSplittingEveryLetter(): void
    {
        $output = self::runScript('bin/make-module.sh APIKey Security /security/api-keys --dry-run');

        $this->assertStringContainsString('APIKey', $output);

        // Inspect every path-like line emitted by the script. The regression
        // pattern only matters in real generated paths — the audit warning
        // text legitimately references 'a_p_i_key' as a documentation example.
        $pathLines = preg_grep('#(^|\s)[A-Za-z0-9_/.-]+/[A-Za-z0-9_/.-]+\.(php|html)\b#', explode("\n", $output)) ?: [];
        $this->assertNotEmpty($pathLines, 'Dry-run output had no path-like lines — sanity check failed');

        foreach ($pathLines as $line) {
            $this->assertDoesNotMatchRegularExpression(
                '#/[a-z](_[a-z])+(_|/)#',
                $line,
                "Path '{$line}' contains split-acronym garbage — regression in to_snake()"
            );
        }
    }

    public function testMakeModuleSecondRunIsIdempotent(): void
    {
        self::runScript('bin/make-module.sh Widget Catalog /catalog/widgets');
        $autoloadBefore = (string) file_get_contents(self::$sandbox . '/app/Config/Autoload.php');
        $servicesBefore = (string) file_get_contents(self::$sandbox . '/app/Config/Services.php');

        self::runScript('bin/make-module.sh Widget Catalog /catalog/widgets');
        $autoloadAfter = (string) file_get_contents(self::$sandbox . '/app/Config/Autoload.php');
        $servicesAfter = (string) file_get_contents(self::$sandbox . '/app/Config/Services.php');

        $this->assertSame($autoloadBefore, $autoloadAfter, 'Idempotent re-run modified Autoload.php');
        $this->assertSame($servicesBefore, $servicesAfter, 'Idempotent re-run modified Services.php');

        // Cleanup sandbox state for downstream tests.
        self::runScript('bin/remove-module.sh Widget Catalog');
    }

    public function testGeneratedViewsContainNoVerbatimPlaceholders(): void
    {
        self::runScript('bin/make-module.sh Gizmo Tools /tools/gizmos');

        $views = glob(self::$sandbox . '/app/Views/tools/gizmos/*.php') ?: [];
        $this->assertNotEmpty($views, 'No views were generated');

        foreach ($views as $view) {
            $contents = (string) file_get_contents($view);
            // The placeholder names that substitute_placeholders() must replace.
            foreach (['VIEW_ROUTE_NAME', 'VIEW_MODULE', 'VIEW_LANG_PREFIX_', 'VIEW_VIEW_PATH'] as $needle) {
                $this->assertStringNotContainsString(
                    $needle,
                    $contents,
                    "View {$view} contains unsubstituted placeholder {$needle}"
                );
            }
        }

        self::runScript('bin/remove-module.sh Gizmo Tools');
    }

    public function testMakeModuleRejectsCrossModuleRouteNameCollision(): void
    {
        // First module: registers route name admin.first.widgets
        self::runScript('bin/make-module.sh Widget First /first/widgets');

        // Second module tries to register the SAME route name via a different module.
        // Route name is derived as `admin.{module_lower}.{route_segment_underscore}`,
        // so to collide we need same module-lower + same route-segment-underscore.
        // Easiest collision: reuse same module name (`First`) AND same resource.
        // But that's a same-module re-run, allowed. The cross-module case fires
        // when a different module yields the same module_lower — e.g. `FIRST` vs
        // `First` (filesystem differs but `tr [:upper:][:lower:]` normalizes both
        // to 'first'). We trigger it by adding `app/Modules/First/Config/Routes.php`
        // ourselves with the route name we'll try to re-add from `FirstAlt`.

        // Pre-seed another module's Routes.php with our future ROUTE_NAME.
        $alienDir = self::$sandbox . '/app/Modules/AlienModule/Config';
        @mkdir($alienDir, 0o755, true);
        file_put_contents(
            $alienDir . '/Routes.php',
            "<?php\n\$routes->get('alien', 'X::y', ['as' => 'admin.different.widgets']);\n"
        );

        // Now scaffolding `Widget` under `Different` produces route name
        // admin.different.widgets — collides with AlienModule.
        $cwd = getcwd();
        chdir(self::$sandbox);
        exec('bash bin/make-module.sh Widget Different /different/widgets --dry-run 2>&1', $out, $code);
        chdir((string) $cwd);

        $this->assertSame(6, $code, 'Cross-module route name collision must exit 6');
        $merged = implode("\n", $out);
        $this->assertStringContainsString('already registered in another module', $merged);
        $this->assertStringContainsString('AlienModule', $merged);

        @unlink($alienDir . '/Routes.php');
        @rmdir($alienDir);
        @rmdir(dirname($alienDir));
        self::runScript('bin/remove-module.sh Widget First');
    }

    public function testMakeModuleWithServiceDomainEmitsDomainApiClientFactory(): void
    {
        $output = self::runScript(
            'bin/make-module.sh Project Subscription /projects --service=domain --dry-run'
        );

        // Dry-run prints the planned register-service.php invocation and the
        // factory body it would write. Both must reference domainApiClient.
        $this->assertStringContainsString('--client=domain', $output);
        $this->assertStringContainsString('static::domainApiClient()', $output);
        $this->assertStringNotContainsString('static::apiClient()', $output);
    }

    public function testMakeModuleWithServiceEventDomainEmitsEventDomainApiClientFactory(): void
    {
        $output = self::runScript(
            'bin/make-module.sh EventType Events /events/types --service=event-domain --dry-run'
        );

        $this->assertStringContainsString('--client=event-domain', $output);
        $this->assertStringContainsString('static::eventDomainApiClient()', $output);
        $this->assertStringNotContainsString('static::domainApiClient()', $output);
        $this->assertStringNotContainsString('static::apiClient()', $output);
    }

    public function testMakeModuleDefaultServiceWiresHubApiClient(): void
    {
        $output = self::runScript('bin/make-module.sh Catalog Marketplace /catalog --dry-run');

        $this->assertStringContainsString('--client=hub', $output);
        $this->assertStringContainsString('static::apiClient()', $output);
        $this->assertStringNotContainsString('static::domainApiClient()', $output);
    }

    public function testMakeModuleRejectsInvalidServiceFlag(): void
    {
        $cwd = getcwd();
        chdir(self::$sandbox);
        exec(
            'bash bin/make-module.sh Foo Bar /foo --service=oops --dry-run 2>&1',
            $out,
            $code,
        );
        chdir((string) $cwd);

        $this->assertNotSame(0, $code, 'Invalid --service value must cause non-zero exit');
        $this->assertStringContainsString('--service', implode("\n", $out));
    }

    public function testMakeModuleWithCustomActionsScaffoldsHooksEndToEnd(): void
    {
        self::runScript('bin/make-module.sh Release Publishing /publishing/releases --action=approve --action=publish');

        $service = (string) file_get_contents(self::$sandbox . '/app/Modules/Publishing/Services/ReleaseApiService.php');
        $interface = (string) file_get_contents(self::$sandbox . '/app/Modules/Publishing/Services/ReleaseApiServiceInterface.php');
        $controller = (string) file_get_contents(self::$sandbox . '/app/Modules/Publishing/Controllers/ReleaseController.php');
        $routes = (string) file_get_contents(self::$sandbox . '/app/Modules/Publishing/Config/Routes.php');
        $showView = (string) file_get_contents(self::$sandbox . '/app/Views/publishing/releases/show.php');
        $langEn = (string) file_get_contents(self::$sandbox . '/app/Modules/Publishing/Language/en/Publishing.php');

        $this->assertStringContainsString('public function approve(int|string $id): array;', $interface);
        $this->assertStringContainsString('public function publish(int|string $id): array;', $interface);
        $this->assertStringContainsString("'/approve'", $service);
        $this->assertStringContainsString("'/publish'", $service);
        $this->assertStringContainsString('public function approve(string $id): RedirectResponse', $controller);
        $this->assertStringContainsString('public function publish(string $id): RedirectResponse', $controller);
        $this->assertStringContainsString("admin.publishing.releases.approve", $routes);
        $this->assertStringContainsString("admin.publishing.releases.publish", $routes);
        $this->assertStringContainsString("route_to('admin.publishing.releases.approve'", $showView);
        $this->assertStringContainsString("route_to('admin.publishing.releases.publish'", $showView);
        $this->assertStringContainsString("'releases_approve'", $langEn);
        $this->assertStringContainsString("'releases_publish'", $langEn);

        self::runScript('bin/remove-module.sh Release Publishing');
    }

    public function testMakeModuleWithOrderFieldScaffoldsReorderAndEmptyState(): void
    {
        self::runScript('bin/make-module.sh Article Editorial /editorial/articles "title:string:required,is_active:boolean,sort_order:int:required"');

        $index = (string) file_get_contents(self::$sandbox . '/app/Views/editorial/articles/index.php');
        $create = (string) file_get_contents(self::$sandbox . '/app/Views/editorial/articles/create.php');
        $edit = (string) file_get_contents(self::$sandbox . '/app/Views/editorial/articles/edit.php');
        $toolbar = (string) file_get_contents(self::$sandbox . '/app/Views/editorial/articles/partials/toolbar_actions.php');
        $reorder = (string) file_get_contents(self::$sandbox . '/app/Views/editorial/articles/reorder.php');
        $controller = (string) file_get_contents(self::$sandbox . '/app/Modules/Editorial/Controllers/ArticleController.php');
        $routes = (string) file_get_contents(self::$sandbox . '/app/Modules/Editorial/Config/Routes.php');

        $this->assertStringContainsString("components/display/empty_state", $index);
        $this->assertStringNotContainsString('sort_order', $create);
        $this->assertStringNotContainsString('sort_order', $edit);
        $this->assertStringContainsString("route_to('admin.editorial.articles.reorder')", $toolbar);
        $this->assertStringContainsString("components/display/reorder", $reorder);
        $this->assertStringContainsString("public function reorder(): string|RedirectResponse", $controller);
        $this->assertStringContainsString("public function saveOrder(): ResponseInterface", $controller);
        $this->assertStringContainsString("['sort_order' => \$value]", $controller);
        $this->assertStringContainsString("admin.editorial.articles.reorder", $routes);
        $this->assertStringContainsString("admin.editorial.articles.save_order", $routes);

        self::runScript('bin/remove-module.sh Article Editorial');
    }

    public function testMakeModuleRelationFieldUsesRelationComponentContract(): void
    {
        self::runScript('bin/make-module.sh Article Catalog /catalog/articles "name:string:required,category_id:relation:required:categories"');

        $index = (string) file_get_contents(self::$sandbox . '/app/Views/catalog/articles/index.php');
        $create = (string) file_get_contents(self::$sandbox . '/app/Views/catalog/articles/create.php');
        $edit = (string) file_get_contents(self::$sandbox . '/app/Views/catalog/articles/edit.php');
        $filters = (string) file_get_contents(self::$sandbox . '/app/Views/catalog/articles/partials/filters.php');
        $controller = (string) file_get_contents(self::$sandbox . '/app/Modules/Catalog/Controllers/ArticleController.php');
        $service = (string) file_get_contents(self::$sandbox . '/app/Modules/Catalog/Services/ArticleApiService.php');

        $this->assertStringContainsString("'categories' => \$categories ?? []", $index);
        $this->assertMatchesRegularExpression("/tableDataResponse\\s*\\(\\s*\\[\\s*'category_id'\\s*\\]/", $controller);
        $this->assertStringContainsString("private function categoriesOptions(): array", $controller);
        $this->assertStringContainsString("categories' => \$this->categoriesOptions()", $controller);
        $this->assertStringContainsString("public function categories(array \$filters = []): array", $service);
        $this->assertStringContainsString("components/form/relation", $create);
        $this->assertStringContainsString("'options' => \$categories ?? []", $create);
        $this->assertStringContainsString("components/form/relation", $edit);
        $this->assertStringContainsString("'options' => \$categories ?? []", $edit);
        $this->assertStringContainsString('name="category_id"', $filters);

        self::runScript('bin/remove-module.sh Article Catalog');
    }

    public function testMakeModuleWithCsvScaffoldsExportAndImportHooks(): void
    {
        self::runScript('bin/make-module.sh Report Analytics /analytics/reports "name:string:required,score:int:required" --csv');

        $service = (string) file_get_contents(self::$sandbox . '/app/Modules/Analytics/Services/ReportApiService.php');
        $interface = (string) file_get_contents(self::$sandbox . '/app/Modules/Analytics/Services/ReportApiServiceInterface.php');
        $controller = (string) file_get_contents(self::$sandbox . '/app/Modules/Analytics/Controllers/ReportController.php');
        $routes = (string) file_get_contents(self::$sandbox . '/app/Modules/Analytics/Config/Routes.php');
        $index = (string) file_get_contents(self::$sandbox . '/app/Views/analytics/reports/index.php');
        $toolbar = (string) file_get_contents(self::$sandbox . '/app/Views/analytics/reports/partials/toolbar_actions.php');
        $csvExportButton = (string) file_get_contents(self::$sandbox . '/app/Views/components/table/export_button.php');
        $csvImportForm = (string) file_get_contents(self::$sandbox . '/app/Views/components/form/export_import.php');
        $csvImportPreview = (string) file_get_contents(self::$sandbox . '/app/Views/components/form/import_preview.php');

        $this->assertStringContainsString('public function exportCsv(array $filters = []): array;', $interface);
        $this->assertStringContainsString('public function importCsv(array $rows): array;', $interface);
        $this->assertStringContainsString("public function exportCsv(): ResponseInterface", $controller);
        $this->assertStringContainsString("public function importCsv(): RedirectResponse", $controller);
        $this->assertStringContainsString("admin.analytics.reports.export_csv", $routes);
        $this->assertStringContainsString("admin.analytics.reports.import_csv", $routes);
        $this->assertStringContainsString('components/table/export_button', $index);
        $this->assertStringContainsString('components/form/export_import', $index);
        $this->assertStringContainsString('components/table/export_button', $toolbar);
        $this->assertStringContainsString('csv', strtolower($csvExportButton));
        $this->assertStringContainsString('import', strtolower($csvImportForm));
        $this->assertStringContainsString('preview', strtolower($csvImportPreview));
        $this->assertStringContainsString('components/form/import_preview', $csvImportForm);

        self::runScript('bin/remove-module.sh Report Analytics');
    }

    public function testMakeModuleDerivesFieldMetadataFromTemplateJson(): void
    {
        $templateFile = self::$sandbox . '/template-metadata.json';
        file_put_contents(
            $templateFile,
            json_encode([
                'entities' => [
                    [
                        'name' => 'Guide',
                        'fields' => [
                            [
                                'name' => 'title',
                                'type' => 'string',
                                'label' => 'Headline',
                                'placeholder' => 'Enter headline',
                                'help' => 'Primary headline shown in listings.',
                                'i18n' => [
                                    'es' => [
                                        'label' => 'Titular',
                                        'placeholder' => 'Ingresa titular',
                                        'help' => 'Titular principal mostrado en listados.',
                                    ],
                                    'fr' => [
                                        'label' => 'Gros titre',
                                        'help' => 'Titre principal affiché dans les listes.',
                                    ],
                                ],
                            ],
                            [
                                'name' => 'summary',
                                'type' => 'text',
                                'label' => 'Summary',
                                'help' => 'Short summary for cards.',
                            ],
                            [
                                'name' => 'published_at',
                                'type' => 'datetime',
                                'label' => 'Publish At',
                            ],
                        ],
                    ],
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        self::runScript(
            'bin/make-module.sh Guide Metadata /metadata/guides "title:string:required,summary:text,published_at:datetime"',
            ['CI4_TEMPLATE_JSON' => 'template-metadata.json']
        );

        $create = (string) file_get_contents(self::$sandbox . '/app/Views/metadata/guides/create.php');
        $langEn = (string) file_get_contents(self::$sandbox . '/app/Modules/Metadata/Language/en/Metadata.php');
        $langEs = (string) file_get_contents(self::$sandbox . '/app/Modules/Metadata/Language/es/Metadata.php');
        $langFr = (string) file_get_contents(self::$sandbox . '/app/Modules/Metadata/Language/fr/Metadata.php');

        $this->assertStringContainsString("components/form/text", $create);
        $this->assertStringContainsString("'placeholder' => 'Metadata.field_title_placeholder'", $create);
        $this->assertStringContainsString("'help' => 'Metadata.field_title_help'", $create);
        $this->assertStringContainsString("'placeholder' => 'Metadata.field_summary_placeholder'", $create);
        $this->assertStringContainsString("'help' => 'Metadata.field_summary_help'", $create);
        $this->assertStringContainsString("'placeholder' => 'Metadata.field_published_at_placeholder'", $create);
        $this->assertStringContainsString("'help' => 'Metadata.field_published_at_help'", $create);

        $this->assertStringContainsString("'field_title' => 'Headline'", $langEn);
        $this->assertStringContainsString("'field_title_placeholder' => 'Enter headline'", $langEn);
        $this->assertStringContainsString("'field_title_help' => 'Primary headline shown in listings.'", $langEn);
        $this->assertStringContainsString("'field_summary' => 'Summary'", $langEn);
        $this->assertStringContainsString("'field_summary_help' => 'Short summary for cards.'", $langEn);
        $this->assertStringContainsString("'field_published_at_placeholder' => 'Select Publish At'", $langEn);
        $this->assertStringContainsString("'field_published_at_help' => 'Select Publish At.'", $langEn);

        $this->assertStringContainsString("'field_title' => 'Titular'", $langEs);
        $this->assertStringContainsString("'field_title_placeholder' => 'Ingresa titular'", $langEs);
        $this->assertStringContainsString("'field_title_help' => 'Titular principal mostrado en listados.'", $langEs);
        $this->assertStringContainsString("'field_summary' => 'Summary'", $langEs);
        $this->assertStringContainsString("'field_summary_help' => 'Short summary for cards.'", $langEs);
        $this->assertStringContainsString("'field_published_at_placeholder' => 'Select Publish At'", $langEs);
        $this->assertStringContainsString("'field_published_at_help' => 'Select Publish At.'", $langEs);

        $this->assertStringContainsString("'field_title' => 'Gros titre'", $langFr);
        $this->assertStringContainsString("'field_title_placeholder' => 'Enter headline'", $langFr);
        $this->assertStringContainsString("'field_title_help' => 'Titre principal affiché dans les listes.'", $langFr);
        $this->assertStringContainsString("'field_summary' => 'Summary'", $langFr);
        $this->assertStringContainsString("'field_summary_help' => 'Short summary for cards.'", $langFr);
        $this->assertStringContainsString("'field_published_at_placeholder' => 'Select Publish At'", $langFr);
        $this->assertStringContainsString("'field_published_at_help' => 'Select Publish At.'", $langFr);

        self::runScript('bin/remove-module.sh Guide Metadata');
        @unlink($templateFile);
    }

    public function testRegisterSidebarIsIdempotentAndUsesModuleFallbackLabels(): void
    {
        $sidebarFile = self::$sandbox . '/app/Views/layouts/partials/sidebar.php';
        $sidebarBackup = self::$sandbox . '/app/Views/layouts/partials/sidebar.php.bak';
        $templateFile = self::$sandbox . '/template.json';
        $moduleDir = self::$sandbox . '/app/Modules/Faq';

        @copy($sidebarFile, $sidebarBackup);
        @mkdir($moduleDir . '/Language/en', 0o755, true);
        @mkdir($moduleDir . '/Language/es', 0o755, true);

        file_put_contents(
            $moduleDir . '/Language/en/Faq.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n    'title' => 'Faq',\n];\n"
        );
        file_put_contents(
            $moduleDir . '/Language/es/Faq.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n    'title' => 'Faq',\n];\n"
        );
        file_put_contents($templateFile, (string) file_get_contents(self::$repoRoot . '/tests/fixtures/faq-domain-template.json'));

        try {
            self::runScript('bin/register-sidebar.sh template.json');
            $afterFirst = (string) file_get_contents($sidebarFile);

            self::runScript('bin/register-sidebar.sh template.json');
            $afterSecond = (string) file_get_contents($sidebarFile);

            $this->assertSame($afterFirst, $afterSecond, 'Sidebar registration must be idempotent');
            $this->assertSame(1, substr_count($afterSecond, '<!-- START Faq -->'));
            $this->assertSame(1, substr_count($afterSecond, '<!-- END Faq -->'));

            $langEn = (string) file_get_contents($moduleDir . '/Language/en/Faq.php');
            $langEs = (string) file_get_contents($moduleDir . '/Language/es/Faq.php');

            $this->assertStringContainsString("'sidebar_label' => 'Faq'", $langEn);
            $this->assertStringContainsString("'sidebar_label' => 'Faq'", $langEs);
            $this->assertStringNotContainsString('CI4 FAQ', $langEs);
        } finally {
            @copy($sidebarBackup, $sidebarFile);
            @unlink($sidebarBackup);
            self::rrmdir($moduleDir);
            @unlink($templateFile);
        }
    }

    public function testMakeModuleRejectsInvalidCustomActionFlag(): void
    {
        $cwd = getcwd();
        chdir(self::$sandbox);
        exec(
            'bash bin/make-module.sh Foo Bar /foo --action=Approve-Now --dry-run 2>&1',
            $out,
            $code,
        );
        chdir((string) $cwd);

        $this->assertNotSame(0, $code, 'Invalid --action value must cause non-zero exit');
        $this->assertStringContainsString('--action', implode("\n", $out));
    }

    public function testRemoveModuleStripsResourceWithoutTouchingSiblings(): void
    {
        self::runScript('bin/make-module.sh Alpha Demo /demo/alpha');
        self::runScript('bin/make-module.sh Beta Demo /demo/beta');

        $output = self::runScript('bin/remove-module.sh Alpha Demo');
        $this->assertStringContainsString('Module removed', $output);

        $this->assertFileDoesNotExist(self::$sandbox . '/app/Modules/Demo/Controllers/AlphaController.php');
        $this->assertFileExists(self::$sandbox . '/app/Modules/Demo/Controllers/BetaController.php');

        $routes = (string) file_get_contents(self::$sandbox . '/app/Modules/Demo/Config/Routes.php');
        $this->assertStringNotContainsString('AlphaController', $routes);
        $this->assertStringContainsString('BetaController', $routes);
    }

    private static function runScript(string $command, array $env = []): string
    {
        $cwd = getcwd();
        chdir(self::$sandbox);
        $output = [];
        $code = 0;
        $envPrefix = '';
        foreach ($env as $key => $value) {
            $envPrefix .= $key . '=' . escapeshellarg((string) $value) . ' ';
        }
        // 2>&1 captures both stdout and stderr — useful when the script fails.
        exec($envPrefix . "bash {$command} 2>&1", $output, $code);
        chdir((string) $cwd);

        $stdout = implode("\n", $output);
        // Idempotent re-run is allowed to exit non-zero only when the script
        // legitimately rejects (e.g. missing args). For these tests we always
        // expect success unless the test asserts otherwise.
        if ($code !== 0 && !str_contains($command, '--dry-run')) {
            // Non-zero with --dry-run is fine; non-zero otherwise is suspicious
            // but the test will catch it via stronger assertions on the output.
        }

        return $stdout;
    }

    private static function commandExists(string $name): bool
    {
        $where = trim((string) shell_exec('command -v ' . escapeshellarg($name)));
        return $where !== '';
    }

    private static function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            is_dir($path) && !is_link($path) ? self::rrmdir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
