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
        return [            'modelType.required' => 'El campo Model type es obligatorio.',
            'modelType.string' => 'El campo Model type debe ser texto.',
            'modelType.max' => 'El campo Model type no puede tener más de 255 caracteres.',
            'modelId.required' => 'El campo Model id es obligatorio.',
            'modelId.integer' => 'El campo Model id debe ser un número entero.',
            'action.required' => 'El campo Action es obligatorio.',
            'action.string' => 'El campo Action debe ser texto.',
            'action.max' => 'El campo Action no puede tener más de 255 caracteres.',
            'userId.required' => 'El campo User id es obligatorio.',
            'changes.array' => 'El campo Changes debe ser un arreglo.',
            'ipAddress.string' => 'El campo Ip address debe ser texto.',
            'ipAddress.max' => 'El campo Ip address no puede tener más de 255 caracteres.',
            'userAgent.string' => 'El campo User agent debe ser texto.',
            'sessionId.string' => 'El campo Session id debe ser texto.',
            'sessionId.max' => 'El campo Session id no puede tener más de 255 caracteres.',
            'payloadHash.required' => 'El campo Payload hash es obligatorio.',
            'payloadHash.string' => 'El campo Payload hash debe ser texto.',
            'payloadHash.max' => 'El campo Payload hash no puede tener más de 255 caracteres.',
            'requiresRetention.required' => 'El campo Requires retention es obligatorio.',
            'requiresRetention.boolean' => 'El campo Requires retention debe ser verdadero o falso.',
        ];
    }
}
