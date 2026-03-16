<?php

namespace Modules\MailerManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\MailerManager\Database\Factories\SystemEmailFactory;

class SystemEmail extends Model
{
    use HasFactory;
    protected $fillable = [
        'key',
        'module',
        'name',
        'description',
        'mailable_class',
        'available_variables',
        'sample_data',
        'email_template_id',
        'is_enabled',
        'default_subject',
    ];

    protected $casts = [
        'available_variables' => 'array',
        'sample_data' => 'array',
        'is_enabled' => 'boolean',
    ];

    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public static function newFactory()
    {
        return SystemEmailFactory::new();
    }

    /**
     * Check if a system email is enabled by key.
     */
    public static function isEnabled(string $key): bool
    {
        try {
            $entry = static::where('key', $key)->first();
            return $entry ? $entry->is_enabled : true;
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * Get the assigned template for a system email by key.
     */
    public static function getTemplate(string $key): ?EmailTemplate
    {
        try {
            $entry = static::where('key', $key)->with('emailTemplate')->first();
            if ($entry && $entry->emailTemplate && $entry->emailTemplate->status === 'active') {
                return $entry->emailTemplate;
            }
            return null;
        } catch (\Throwable) {
            return null;
        }
    }
}
