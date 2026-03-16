<?php

namespace Modules\MailerManager\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplateImage extends Model
{
    protected $fillable = [
        'email_template_id',
        'filename',
        'path',
        'mime_type',
        'size',
    ];

    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class);
    }
}
