<?php

namespace Modules\MailerManager\JsonApi\V1\EmailTemplates;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class EmailTemplateResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'subject' => $this->subject,
            'html' => $this->html,
            'css' => $this->css,
            'json' => $this->json,
            'status' => $this->status,
            'category' => $this->category,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
