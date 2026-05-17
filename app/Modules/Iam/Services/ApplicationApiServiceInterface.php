<?php

declare(strict_types=1);

namespace App\Modules\Iam\Services;

interface ApplicationApiServiceInterface
{
    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function list(array $filters = []): array;

    /**
     * @return array<string, mixed>
     */
    public function get(int|string $id): array;
}
