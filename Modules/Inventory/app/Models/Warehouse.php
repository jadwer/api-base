<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Modelo Warehouse
 *
 * Representa una bodega física de almacenamiento.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $code
 * @property string|null $address
 * @property string|null $street
 * @property string|null $exterior_number
 * @property string|null $interior_number
 * @property string|null $neighborhood
 * @property string|null $municipality
 * @property string|null $address_reference
 * @property string|null $city
 * @property string|null $state
 * @property string|null $country
 * @property string|null $postal_code
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $manager_name
 * @property bool $is_active
 * @property string $warehouse_type
 * @property float|null $max_capacity
 * @property string $capacity_unit
 * @property array|null $operating_hours
 * @property array|null $metadata
 * @property-read \Illuminate\Database\Eloquent\Collection $locations
 * @property-read \Illuminate\Database\Eloquent\Collection $stock
 * @property-read \Illuminate\Database\Eloquent\Collection $productBatches
 */
class Warehouse extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'is_active', 'warehouse_type', 'address', 'city', 'manager_name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'id' => 'integer',
        'is_active' => 'boolean',
        'max_capacity' => 'float',
        'operating_hours' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Warehouse $warehouse) {
            // Multi-sucursal (2026-09): sucursal del usuario autenticado o la Matriz.
            if (empty($warehouse->branch_id)) {
                $warehouse->branch_id = \Modules\Branch\Models\Branch::defaultId();
            }
        });
    }

    /** Sucursal de origen (multi-sucursal 2026-09). */
    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\Modules\Branch\Models\Branch::class);
    }

    /**
     * Una bodega puede tener muchas ubicaciones internas.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(WarehouseLocation::class);
    }

    /**
     * Una bodega puede tener muchos registros de stock.
     */
    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Una bodega puede tener muchos lotes de productos.
     */
    public function productBatches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Inventory\Database\Factories\WarehouseFactory::new();
    }

    /** Ubicaciones del almacen para el listado (usa withCount si viene cargado). */
    public function getLocationsCountAttribute(): int
    {
        return (int) ($this->attributes['locations_count'] ?? $this->locations()->count());
    }

    /** Busqueda del listado: nombre, codigo o ciudad. */
    public function scopeSearch($query, string $term)
    {
        $like = '%'.trim($term).'%';

        return $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('code', 'like', $like)->orWhere('city', 'like', $like));
    }
}
