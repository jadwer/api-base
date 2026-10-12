<?php

namespace Modules\Accounting\JsonApi\V1\IdempotencyKeys;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class IdempotencyKeyRequest extends ResourceRequest
{
    public function rules(): array
    {
        $idempotencykey = $this->model();
        $isUpdate = $idempotencykey && $idempotencykey->exists;

        
        return [            'userId' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'endpoint' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'idempotencyKey' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'requestHash' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'responseData' => ['nullable', 'array'],
            'status' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'expiresAt' => [$isUpdate ? 'sometimes' : 'required', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [            'userId.required' => 'El campo usuario es obligatorio.',
            'endpoint.required' => 'El campo endpoint es obligatorio.',
            'endpoint.string' => 'El campo endpoint debe ser texto.',
            'endpoint.max' => 'El campo endpoint no puede tener más de 255 caracteres.',
            'idempotencyKey.required' => 'El campo clave de idempotencia es obligatorio.',
            'idempotencyKey.string' => 'El campo clave de idempotencia debe ser texto.',
            'idempotencyKey.max' => 'El campo clave de idempotencia no puede tener más de 255 caracteres.',
            'requestHash.required' => 'El campo hash de la solicitud es obligatorio.',
            'requestHash.string' => 'El campo hash de la solicitud debe ser texto.',
            'requestHash.max' => 'El campo hash de la solicitud no puede tener más de 255 caracteres.',
            'responseData.array' => 'El campo respuesta debe ser un arreglo.',
            'status.required' => 'El campo estado es obligatorio.',
            'status.string' => 'El campo estado debe ser texto.',
            'status.max' => 'El campo estado no puede tener más de 255 caracteres.',
            'expiresAt.required' => 'El campo fecha de vencimiento es obligatorio.',
        ];
    }
}
