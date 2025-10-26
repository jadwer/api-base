<?php

namespace Modules\Accounting\JsonApi\V1\AuditLogs;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class AuditLogRequest extends ResourceRequest
{
    public function rules(): array
    {
        $auditlog = $this->model();
        $isUpdate = $AuditLog && $AuditLog->exists;

        
        return [            'model_type' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'model_id' => [$isUpdate ? 'sometimes' : 'required', 'string'],
            'action' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'userId' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'changes' => ['nullable', 'array'],
            'ipAddress' => ['nullable', 'string', 'max:255'],
            'userAgent' => ['nullable', 'string'],
            'session_id' => ['nullable', 'string', 'max:255'],
            'payload_hash' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'requires_retention' => [$isUpdate ? 'sometimes' : 'required', 'boolean'],
            'retention_until' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [            'model_type.required' => 'El campo Model type es obligatorio.',
            'model_type.string' => 'El campo Model type debe ser texto.',
            'model_type.max' => 'El campo Model type no puede tener más de 255 caracteres.',
            'model_id.required' => 'El campo Model id es obligatorio.',
            'action.required' => 'El campo Action es obligatorio.',
            'action.string' => 'El campo Action debe ser texto.',
            'action.max' => 'El campo Action no puede tener más de 255 caracteres.',
            'userId.required' => 'El campo User id es obligatorio.',
            'changes.array' => 'El campo Changes debe ser un arreglo.',
            'ipAddress.string' => 'El campo Ip address debe ser texto.',
            'ipAddress.max' => 'El campo Ip address no puede tener más de 255 caracteres.',
            'userAgent.string' => 'El campo User agent debe ser texto.',
            'session_id.string' => 'El campo Session id debe ser texto.',
            'session_id.max' => 'El campo Session id no puede tener más de 255 caracteres.',
            'payload_hash.required' => 'El campo Payload hash es obligatorio.',
            'payload_hash.string' => 'El campo Payload hash debe ser texto.',
            'payload_hash.max' => 'El campo Payload hash no puede tener más de 255 caracteres.',
            'requires_retention.required' => 'El campo Requires retention es obligatorio.',
            'requires_retention.boolean' => 'El campo Requires retention debe ser verdadero o falso.',
        ];
    }
}
