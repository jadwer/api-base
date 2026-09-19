<?php

namespace Modules\Branch\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\User\Models\User;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Sucursal (peticion Jasim 16-sep-2026, referencia Bind ERP).
 *
 * Misma empresa y misma entidad fiscal: la sucursal es una etiqueta de
 * origen para usuarios, almacenes y documentos (cotizacion, venta, compra,
 * remision, CFDI). Siempre existe exactamente una sucursal principal
 * (is_main), "Matriz", sembrada por migracion; todo lo historico y todo lo
 * que se crea sin sucursal explicita cae ahi.
 */
class Branch extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'branches';

    public const MAIN_CODE = 'MATRIZ';

    /** Cache por request del id de la sucursal principal. */
    protected static ?int $mainIdCache = null;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'state',
        'postal_code',
        'phone',
        'email',
        'is_active',
        'is_main',
    ];

    protected $casts = [
        'id' => 'integer',
        'is_active' => 'boolean',
        'is_main' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Solo una sucursal principal: marcar otra como principal desmarca a la anterior.
        static::saved(function (Branch $branch) {
            static::$mainIdCache = null;
            if ($branch->is_main) {
                static::query()
                    ->where('id', '!=', $branch->id)
                    ->where('is_main', true)
                    ->update(['is_main' => false]);
            }
        });

        static::deleted(fn () => static::$mainIdCache = null);
    }

    /** Sucursal principal (Matriz), o null si la tabla esta vacia. */
    public static function main(): ?self
    {
        return static::query()->where('is_main', true)->orderBy('id')->first();
    }

    public static function mainId(): ?int
    {
        if (static::$mainIdCache === null) {
            static::$mainIdCache = static::query()->where('is_main', true)->orderBy('id')->value('id');
        }

        return static::$mainIdCache;
    }

    /**
     * Sucursal por defecto para lo que se crea ahora: la principal del usuario
     * autenticado y, si no hay usuario o no tiene, la Matriz.
     */
    public static function defaultId(): ?int
    {
        $user = auth()->user();
        $userBranch = $user?->getAttribute('branch_id');

        return $userBranch ?: static::mainId();
    }

    public static function forgetMainCache(): void
    {
        static::$mainIdCache = null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Usuarios cuya sucursal principal es esta. */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'branch_id');
    }

    /** Usuarios con acceso a esta sucursal (pivot branch_user). */
    public function accessUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_user')->withTimestamps();
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(\Modules\Inventory\Models\Warehouse::class, 'branch_id');
    }

    protected static function newFactory()
    {
        return \Modules\Branch\Database\Factories\BranchFactory::new();
    }
}
