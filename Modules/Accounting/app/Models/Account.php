<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Account extends Model
{
    use HasFactory, HasPermissions, LogsActivity;

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'account_type', 'nature', 'parent_id', 'is_postable', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $table = 'accounts';
    
    protected $fillable = [
        'company_id', 'code', 'name', 'account_type', 'nature', 'level', 'parent_id', 'currency', 'is_postable', 'is_cash_flow', 'status', 'metadata'
    ];

    protected $casts = [
                'is_postable' => 'boolean',
        'is_cash_flow' => 'boolean',
        'metadata' => 'array'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Paquete A (auditoria 10 pasos): buscador del listado. El FE ya mandaba
     * filter[search] pero el Schema no lo declaraba y el backend respondia 400.
     */
    public function scopeSearch($query, string $term)
    {
        $term = trim($term);

        return $query->where(function ($q) use ($term) {
            $q->where('code', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%");
        });
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function journalLines()
    {
        return $this->hasMany(JournalLine::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Accounting\Database\Factories\AccountFactory::new();
    }
}
