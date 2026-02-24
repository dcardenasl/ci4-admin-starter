<?php

namespace App\Requests;

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

        return $this->validation->withRequest($this->request)->run($this->data());
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->validation->getErrors();
    }
}
