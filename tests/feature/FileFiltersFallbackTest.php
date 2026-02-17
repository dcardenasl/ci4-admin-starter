<?php

namespace Tests\Feature;

use App\Services\FileApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class FileFiltersFallbackTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testSearchFilterIsForwardedToApiListQueryUsingQ(): void
    {
        $mock = $this->createMock(FileApiService::class);
        $mock->expects($this->once())
            ->method('list')
            ->with($this->callback(static function (array $params): bool {
                return ($params['search'] ?? null) === 'invoice'
                    && ! array_key_exists('q', $params);
            }))
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [
                    'data' => [
                        ['id' => 1, 'name' => 'invoice-2026.pdf', 'status' => 'active', 'created_at' => '2026-02-01 10:00:00'],
                    ],
                ],
                'raw'         => '',
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('fileApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'user'],
        ])->get('/files/data?search=invoice');

        $result->assertStatus(200);
        $this->assertStringContainsString('invoice-2026.pdf', $result->getBody());
    }

    public function testCursorPaginationHasPriorityOverPage(): void
    {
        $mock = $this->createMock(FileApiService::class);
        $mock->expects($this->once())
            ->method('list')
            ->with($this->callback(static function (array $params): bool {
                return ($params['cursor'] ?? null) === 'next-token'
                    && ! array_key_exists('page', $params);
            }))
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [
                    'data' => [],
                    'meta' => ['next_cursor' => '', 'prev_cursor' => ''],
                ],
                'raw'         => '',
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('fileApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'user'],
        ])->get('/files/data?cursor=next-token&page=9');

        $result->assertStatus(200);
    }
}
