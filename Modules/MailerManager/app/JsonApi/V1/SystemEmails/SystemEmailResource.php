<?php

namespace Modules\MailerManager\JsonApi\V1\SystemEmails;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class SystemEmailResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'key' => $this->key,
            'module' => $this->module,
            'name' => $this->name,
            'description' => $this->description,
            'mailableClass' => $this->mailable_class,
            'availableVariables' => $this->available_variables,
            'sampleData' => $this->sample_data,
            'isEnabled' => $this->is_enabled,
            'defaultSubject' => $this->default_subject,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
