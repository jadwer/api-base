<?php

namespace Modules\MailerManager\JsonApi\V1\SystemEmails;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class SystemEmailRequest extends ResourceRequest
{
    public function rules($record = null): array
    {
        return [
            'isEnabled' => ['sometimes', 'boolean'],
            'emailTemplate' => JsonApiRule::toOne(),
        ];
    }
}
