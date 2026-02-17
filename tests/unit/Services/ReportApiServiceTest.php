<?php

namespace Tests\Unit\Services;

use App\Libraries\ApiClientInterface;
use App\Services\ReportApiService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ReportApiServiceTest extends CIUnitTestCase
{
    public function testListUsesReportsEndpoint(): void
    {
        $mock = $this->createMock(ApiClientInterface::class);
        $expected = ['ok' => true, 'status' => 200, 'data' => ['data' => []]];

        $mock->expects($this->once())
            ->method('get')
            ->with('/reports', ['report_type' => 'users'])
            ->willReturn($expected);

        $service = new ReportApiService($mock);
        $result = $service->list(['report_type' => 'users']);

        $this->assertSame($expected, $result);
    }

    public function testExportCsvUsesExportEndpoint(): void
    {
        $mock = $this->createMock(ApiClientInterface::class);

        $mock->expects($this->once())
            ->method('get')
            ->with('/reports/export/csv', ['date_from' => '2026-01-01'])
            ->willReturn(['ok' => true, 'status' => 200, 'data' => ['download_url' => 'https://example.test/r.csv']]);

        $service = new ReportApiService($mock);
        $result = $service->exportCsv(['date_from' => '2026-01-01']);

        $this->assertTrue($result['ok']);
    }

    public function testExportPdfUsesExportEndpoint(): void
    {
        $mock = $this->createMock(ApiClientInterface::class);

        $mock->expects($this->once())
            ->method('get')
            ->with('/reports/export/pdf', ['date_from' => '2026-01-01'])
            ->willReturn(['ok' => true, 'status' => 200, 'data' => ['download_url' => 'https://example.test/r.pdf']]);

        $service = new ReportApiService($mock);
        $result = $service->exportPdf(['date_from' => '2026-01-01']);

        $this->assertTrue($result['ok']);
    }
}
