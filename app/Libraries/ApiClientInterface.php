<?php

namespace App\Libraries;

/**
 * @method array{
 *   ok: bool,
 *   status: int,
 *   data: array,
 *   raw: string,
 *   headers: array<string, string>,
 *   messages: string[],
 *   fieldErrors: array<string, string>
 * }
 */
interface ApiClientInterface
{
    public function get(string $path, array $query = []): array;

    public function post(string $path, array $data = []): array;

    public function put(string $path, array $data = []): array;

    public function delete(string $path): array;

    public function publicPost(string $path, array $data = []): array;

    public function publicGet(string $path, array $query = []): array;

    public function upload(string $path, array $files = [], array $fields = []): array;

    public function request(string $method, string $path, array $options = [], bool $authenticated = true): array;
}
