<?php

namespace Modules\Accounting\JsonApi\V1\IdempotencyKeys;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class IdempotencyKeyRequest extends ResourceRequest
{
    public function rules(): array
    {
        $idempotencykey = $this->model();
        
        return [
            'company_id' => ['required', 'string'],
            'user_id' => ['required', 'string'],
            'endpoint' => ['required', 'string', 'max:255'],
            'idempotency_key' => ['required', 'string', 'max:255'],
            'request_hash' => ['required', 'string', 'max:255'],
            'response_data' => ['nullable', 'array'],
            'status' => ['required', 'string', 'max:255'],
            'expires_at' => ['required', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'El campo Company id es obligatorio.',
            'user_id.required' => 'El campo User id es obligatorio.',
            'endpoint.required' => 'El campo Endpoint es obligatorio.',
            'endpoint.string' => 'El campo Endpoint debe ser texto.',
            'endpoint.max' => 'El campo Endpoint no puede tener más de 255 caracteres.',
            'idempotency_key.required' => 'El campo Idempotency key es obligatorio.',
            'idempotency_key.string' => 'El campo Idempotency key debe ser texto.',
            'idempotency_key.max' => 'El campo Idempotency key no puede tener más de 255 caracteres.',
            'request_hash.required' => 'El campo Request hash es obligatorio.',
            'request_hash.string' => 'El campo Request hash debe ser texto.',
            'request_hash.max' => 'El campo Request hash no puede tener más de 255 caracteres.',
            'response_data.array' => 'El campo Response data debe ser un arreglo.',
            'status.required' => 'El campo Status es obligatorio.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
            'expires_at.required' => 'El campo Expires at es obligatorio.',
        ];
    }
}
