<?php

namespace Modules\MailerManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Modules\MailerManager\Database\Factories\EmailTemplateFactory;

class EmailTemplate extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'subject',
        'html',
        'css',
        'json',
        'status',
        'category',
        'user_id',
    ];

    protected $casts = [
        'json' => 'array',
    ];

    public function images()
    {
        return $this->hasMany(EmailTemplateImage::class);
    }

    public function systemEmails()
    {
        return $this->hasMany(SystemEmail::class);
    }

    public function user()
    {
        return $this->belongsTo(\Modules\User\Models\User::class);
    }

    public static function newFactory()
    {
        return EmailTemplateFactory::new();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('email-template')
            ->logOnly(['name', 'slug', 'subject', 'status', 'category'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
