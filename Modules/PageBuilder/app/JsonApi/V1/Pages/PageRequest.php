<?php

namespace Modules\PageBuilder\JsonApi\V1\Pages;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class PageRequest extends ResourceRequest
{

    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules($record = null): array
    {
        $record = $record ?: $this->model();
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pages', 'slug')->ignore($record?->id),
            ],
            'html' => ['nullable', 'string'],
            'css' => ['nullable', 'string'],
            'json' => ['nullable', 'array'],
            'publishedAt' => ['nullable', 'date'],
            'user' => ['nullable'],
        ];
    }
}
