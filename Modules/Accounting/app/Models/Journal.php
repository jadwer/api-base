<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;


class Journal extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'journals';
    
    protected $fillable = [
        'code', 'name', 'auto_numbering', 'sequence_prefix', 'sequence_next', 'default_currency', 'post_policy'
    ];

    protected $casts = [
                'auto_numbering' => 'boolean'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }



    // Factory
    protected static function newFactory()
    {
        return \Modules\Accounting\Database\Factories\JournalFactory::new();
    }
}
