<?php

declare(strict_types=1);

namespace App\Support\Requests;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Validation\ValidationInterface;

abstract class BaseFormRequest implements FormRequestInterface
{
    public function __construct(
        protected IncomingRequest $request,
        protected ValidationInterface $validation,
    ) {
    }

    /**
     * CI4 validation rules for this form.
     * Keys are field names; values are rule strings or rule arrays.
     *
     * @return array<string, array<string>|string>
     */
    abstract public function rules(): array;

    /**
     * Fields whose values will be extracted from the POST body.
     *
     * @return array<int, string>
     */
    abstract protected function fields(): array;

    /**
     * @return array<string, array<string, string>>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $data = [];

        foreach ($this->fields() as $field) {
            $data[$field] = $this->request->getPost($field);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->data();
    }

    public function validate(): bool
    {
        $this->validation->reset();
        $this->validation->setRules($this->rules(), $this->messages());
        $data = $this->data();

        $result = $this->validation->run($data);

        if (! $result) {
            log_message('debug', '[BaseFormRequest] Validation failed for ' . static::class);
            log_message('debug', '[BaseFormRequest] Data: ' . json_encode($data));
            log_message('debug', '[BaseFormRequest] Errors: ' . json_encode($this->validation->getErrors()));
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->validation->getErrors();
    }

    /**
     * Return a scalar POST field as string, empty string if missing or non-scalar.
     */
    protected function postString(string $field): string
    {
        $value = $this->request->getPost($field);

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * Return a POST field coerced to int. Returns $default when missing or non-numeric.
     */
    protected function postInt(string $field, int $default = 0): int
    {
        $value = $this->request->getPost($field);

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    /**
     * Return a POST field as bool.
     * Truthy values: '1', 'true', 'on', 'yes' (case-insensitive).
     */
    protected function postBool(string $field): bool
    {
        $value = strtolower(trim($this->postString($field)));

        return in_array($value, ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * Return a POST field as array. Returns empty array when missing or non-array.
     *
     * @return array<mixed>
     */
    protected function postArray(string $field): array
    {
        $value = $this->request->getPost($field);

        return is_array($value) ? $value : [];
    }
}
