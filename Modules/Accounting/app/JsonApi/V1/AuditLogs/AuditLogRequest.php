<?php

namespace Modules\Accounting\JsonApi\V1\AuditLogs;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class AuditLogRequest extends ResourceRequest
{
    public function rules(): array
    {
        $auditlog = $this->model();
        $isUpdate = $auditlog && $auditlog->exists;

        
        return [            'modelType' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'modelId' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'action' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'userId' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'changes' => ['nullable', 'array'],
            'ipAddress' => ['nullable', 'string', 'max:255'],
            'userAgent' => ['nullable', 'string'],
            'sessionId' => ['nullable', 'string', 'max:255'],
            'payloadHash' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'requiresRetention' => [$isUpdate ? 'sometimes' : 'required', 'boolean'],
            'retentionUntil' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [            'modelType.required' => 'El campo tipo de registro es obligatorio.',
            'modelType.string' => 'El campo tipo de registro debe ser texto.',
            'modelType.max' => 'El campo tipo de registro no puede tener más de 255 caracteres.',
            'modelId.required' => 'El campo registro es obligatorio.',
            'modelId.integer' => 'El campo registro debe ser un número entero.',
            'action.required' => 'El campo acción es obligatorio.',
            'action.string' => 'El campo acción debe ser texto.',
            'action.max' => 'El campo acción no puede tener más de 255 caracteres.',
            'userId.required' => 'El campo usuario es obligatorio.',
            'changes.array' => 'El campo cambios debe ser un arreglo.',
            'ipAddress.string' => 'El campo dirección IP debe ser texto.',
            'ipAddress.max' => 'El campo dirección IP no puede tener más de 255 caracteres.',
            'userAgent.string' => 'El campo navegador debe ser texto.',
            'sessionId.string' => 'El campo sesión debe ser texto.',
            'sessionId.max' => 'El campo sesión no puede tener más de 255 caracteres.',
            'payloadHash.required' => 'El campo hash del contenido es obligatorio.',
            'payloadHash.string' => 'El campo hash del contenido debe ser texto.',
            'payloadHash.max' => 'El campo hash del contenido no puede tener más de 255 caracteres.',
            'requiresRetention.required' => 'El campo requiere retención es obligatorio.',
            'requiresRetention.boolean' => 'El campo requiere retención debe ser verdadero o falso.',
        ];
    }
}
