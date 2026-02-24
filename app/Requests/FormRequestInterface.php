<?php

namespace App\Requests;

interface FormRequestInterface
{
    /**
     * @return array<string, string>
     */
    public function rules(): array;

    /**
     * @return array<string, array<string, string>>
     */
    public function messages(): array;

    /**
     * @return array<string, mixed>
     */
    public function data(): array;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;

    public function validate(): bool;

    /**
     * @return array<string, string>
     */
    public function errors(): array;
}
