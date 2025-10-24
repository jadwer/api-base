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
        'code', 'name', 'description', 'prefix', 'type', 'status', 'metadata'
    ];

    protected $casts = [
                'metadata' => 'array'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function journalSequences()
    {
        return $this->hasMany(JournalSequence::class);
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Accounting\Database\Factories\JournalFactory::new();
    }
}
