<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;


class JournalSequence extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'journal_sequences';
    
    protected $fillable = [
        'journal_id', 'fiscal_year', 'current_number', 'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Accounting\Database\Factories\JournalSequenceFactory::new();
    }
}
