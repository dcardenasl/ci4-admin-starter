<?php

namespace App\Libraries;

/**
 * @phpstan-type ApiResponse array{
 *   ok: bool,
 *   status: int,
 *   data: array<string, mixed>,
 *   raw: string,
 *   headers: array<string, string>,
 *   messages: list<string>,
 *   fieldErrors: array<string, string>
 * }
 */
interface ApiClientInterface
{
    /** @return ApiResponse */
    public function get(string $path, array $query = []): array;

    /** @return ApiResponse */
    public function post(string $path, array $data = []): array;

    /** @return ApiResponse */
    public function put(string $path, array $data = []): array;

    /** @return ApiResponse */
    public function delete(string $path): array;

    /** @return ApiResponse */
    public function publicPost(string $path, array $data = []): array;

    /** @return ApiResponse */
    public function publicGet(string $path, array $query = []): array;

    /** @return ApiResponse */
    public function upload(string $path, array $files = [], array $fields = []): array;

    /** @return ApiResponse */
    public function request(string $method, string $path, array $options = [], bool $authenticated = true): array;
}
