<?php

namespace Modules\PageBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Modules\PageBuilder\Database\Factories\PageFactory;

class Page extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'title',
        'slug',
        'html',
        'css',
        'json',
        'published_at',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(\Modules\User\Models\User::class);
    }

    protected $casts = [
        'published_at' => 'datetime',
        'json' => 'array',
    ];

    public static function newFactory()
    {
        return PageFactory::new();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('page')
            ->logOnly(['title', 'slug', 'html', 'css', 'json', 'published_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
