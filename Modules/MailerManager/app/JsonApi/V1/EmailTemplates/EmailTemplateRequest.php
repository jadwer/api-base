<?php

namespace Modules\MailerManager\JsonApi\V1\EmailTemplates;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class EmailTemplateRequest extends ResourceRequest
{
    public function rules($record = null): array
    {
        $record = $record ?: $this->model();
        $isUpdate = $record !== null;

        return [
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
                Rule::unique('email_templates', 'slug')->ignore($record?->id),
            ],
            'description' => ['nullable', 'string'],
            'subject' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'html' => ['nullable', 'string'],
            'css' => ['nullable', 'string'],
            'json' => ['nullable', 'array'],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'active', 'archived'])],
            'category' => ['nullable', 'string', Rule::in(['transactional', 'notification', 'marketing'])],
            'user' => JsonApiRule::toOne(),
        ];
    }
}
