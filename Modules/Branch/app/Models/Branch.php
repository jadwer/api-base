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

        // Borrar una sucursal con datos ligados chocaba con la FK restrict (500
        // generico). Ahora se rechaza con 409 y el motivo; la principal nunca.
        static::deleting(function (Branch $branch) {
            if ($branch->is_main) {
                abort(409, 'La sucursal principal no se puede eliminar. Marca otra como principal primero.');
            }
            $links = $branch->linkedRecordCounts();
            if ($links !== []) {
                $detail = implode(', ', array_map(fn ($k, $v) => "{$v} {$k}", array_keys($links), $links));
                abort(409, "No se puede eliminar la sucursal: tiene {$detail}. Desactivala en su lugar.");
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

    /**
     * Ids de sucursal a los que se limita el usuario autenticado, o null si no
     * hay restriccion (sin usuario, god, admin o customer). Memo por instancia
     * de usuario: se recalcula si el objeto del usuario cambia (fresh()).
     *
     * @return int[]|null
     */
    public static function restrictedIdsForCurrentUser(): ?array
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'accessibleBranchIds')) {
            return null;
        }

        static $memo = null;
        $memo ??= new \WeakMap();
        if (! isset($memo[$user])) {
            $memo[$user] = ['ids' => $user->hasAnyRole(['god', 'admin', 'customer']) ? null : $user->accessibleBranchIds()];
        }

        return $memo[$user]['ids'];
    }

    public static function forgetMainCache(): void
    {
        static::$mainIdCache = null;
    }

    /** Registros que impiden borrar la sucursal (solo los que existen). */
    public function linkedRecordCounts(): array
    {
        $db = \Illuminate\Support\Facades\DB::connection();
        $checks = [
            'usuarios' => fn () => $db->table('users')->where('branch_id', $this->id)->count()
                + $db->table('branch_user')->where('branch_id', $this->id)->count(),
            'almacenes' => fn () => $db->table('warehouses')->where('branch_id', $this->id)->count(),
            'cotizaciones' => fn () => $db->table('quotes')->where('branch_id', $this->id)->count(),
            'ventas' => fn () => $db->table('sales_orders')->where('branch_id', $this->id)->count(),
            'compras' => fn () => $db->table('purchase_orders')->where('branch_id', $this->id)->count(),
            'remisiones' => fn () => $db->table('remissions')->where('branch_id', $this->id)->count(),
            'facturas' => fn () => $db->table('cfdi_invoices')->where('branch_id', $this->id)->count(),
        ];

        return array_filter(array_map(fn ($check) => $check(), $checks));
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
