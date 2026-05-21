<?php

declare(strict_types=1);

namespace Tests\Unit\Views;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class TableHeadersConsistencyTest extends CIUnitTestCase
{
    /**
     * @return array<int, string>
     */
    private function tableViewPaths(): array
    {
        return [
            APPPATH . 'Views/users/index.php',
            APPPATH . 'Views/audit/index.php',
            APPPATH . 'Views/api_keys/index.php',
            APPPATH . 'Views/files/partials/list_section.php',
            APPPATH . 'Views/dashboard/partials/widget_recent_files.php',
            APPPATH . 'Views/metrics/index.php',
        ];
    }

    public function testTableHeadersUseSharedTableColumnsDictionary(): void
    {
        $allowedDomainKeys = [
            "lang('ApiKeys.rate_limit_requests')",
            "lang('ApiKeys.rate_limit_window')",
        ];

        foreach ($this->tableViewPaths() as $path) {
            $contents = (string) file_get_contents($path);
            $this->assertNotSame('', $contents, "Unable to read view: {$path}");

            preg_match_all('/<th\\b[^>]*>.*?<\\/th>/s', $contents, $matches);
            $headers = $matches[0] ?? [];
            $this->assertNotEmpty($headers, "No table headers found in: {$path}");

            foreach ($headers as $header) {
                if (! str_contains($header, "lang('")) {
                    continue;
                }

                $isAllowedDomainHeader = false;
                foreach ($allowedDomainKeys as $key) {
                    if (str_contains($header, $key)) {
                        $isAllowedDomainHeader = true;

                        break;
                    }
                }

                if ($isAllowedDomainHeader) {
                    continue;
                }

                $this->assertStringContainsString(
                    "lang('TableColumns.",
                    $header,
                    "Header should use TableColumns dictionary in: {$path}"
                );
            }
        }
    }

    public function testSortableHeadersUseSharedSortAriaTemplate(): void
    {
        $sortableViews = [
            APPPATH . 'Views/users/index.php',
            APPPATH . 'Views/audit/index.php',
            APPPATH . 'Views/api_keys/index.php',
            APPPATH . 'Views/files/partials/list_section.php',
        ];

        foreach ($sortableViews as $path) {
            $contents = (string) file_get_contents($path);
            $this->assertNotSame('', $contents, "Unable to read view: {$path}");
            $this->assertStringContainsString(
                "lang('TableA11y.sort_by'",
                $contents,
                "Sortable headers should use shared a11y sort template in: {$path}"
            );
        }
    }
}
