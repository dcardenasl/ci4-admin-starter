<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Client-side wrapper normalized from the canonical API envelope.
 *
 * The backend contract remains `status` / `message` / `data` / `errors`;
 * this wrapper derives `messages` and `fieldErrors` for controller UX.
 *
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
    /**
     * @param array<string, mixed> $query
     * @return ApiResponse
     */
    public function get(string $path, array $query = []): array;

    /**
     * @param array<string, mixed> $data
     * @return ApiResponse
     */
    public function post(string $path, array $data = []): array;

    /**
     * @param array<string, mixed> $data
     * @return ApiResponse
     */
    public function put(string $path, array $data = []): array;

    /**
     * @param array<string, mixed> $data
     * @return ApiResponse
     */
    public function patch(string $path, array $data = []): array;

    /** @return ApiResponse */
    public function delete(string $path): array;

    /**
     * @param array<string, mixed> $data
     * @return ApiResponse
     */
    public function publicPost(string $path, array $data = []): array;

    /**
     * @param array<string, mixed> $query
     * @return ApiResponse
     */
    public function publicGet(string $path, array $query = []): array;

    /**
     * @param array<string, mixed> $files
     * @param array<string, mixed> $fields
     * @return ApiResponse
     */
    public function upload(string $path, array $files = [], array $fields = []): array;

    /**
     * @param array<string, mixed> $options
     * @return ApiResponse
     */
    public function request(string $method, string $path, array $options = [], bool $authenticated = true): array;

    public function clearSessionAuth(): void;
}
