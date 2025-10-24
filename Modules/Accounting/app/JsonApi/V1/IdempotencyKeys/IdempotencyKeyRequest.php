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
            'companyId' => ['required', 'string'],
            'userId' => ['required', 'string'],
            'endpoint' => ['required', 'string', 'max:255'],
            'idempotencyKey' => ['required', 'string', 'max:255'],
            'requestHash' => ['required', 'string', 'max:255'],
            'responseData' => ['nullable', 'array'],
            'status' => ['required', 'string', 'max:255'],
            'expiresAt' => ['required', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'companyId.required' => 'El campo Company id es obligatorio.',
            'userId.required' => 'El campo User id es obligatorio.',
            'endpoint.required' => 'El campo Endpoint es obligatorio.',
            'endpoint.string' => 'El campo Endpoint debe ser texto.',
            'endpoint.max' => 'El campo Endpoint no puede tener más de 255 caracteres.',
            'idempotencyKey.required' => 'El campo Idempotency key es obligatorio.',
            'idempotencyKey.string' => 'El campo Idempotency key debe ser texto.',
            'idempotencyKey.max' => 'El campo Idempotency key no puede tener más de 255 caracteres.',
            'requestHash.required' => 'El campo Request hash es obligatorio.',
            'requestHash.string' => 'El campo Request hash debe ser texto.',
            'requestHash.max' => 'El campo Request hash no puede tener más de 255 caracteres.',
            'responseData.array' => 'El campo Response data debe ser un arreglo.',
            'status.required' => 'El campo Status es obligatorio.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
            'expiresAt.required' => 'El campo Expires at es obligatorio.',
        ];
    }
}
