<?php

namespace Modules\Accounting\JsonApi\V1\Journals;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class JournalResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'autoNumbering' => $this->auto_numbering,
            'sequencePrefix' => $this->sequence_prefix,
            'sequenceNext' => $this->sequence_next,
            'defaultCurrency' => $this->default_currency,
            'postPolicy' => $this->post_policy,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'journalEntry' => $this->relation('journalEntry'),
        ];
    }
}
