<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 */
class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'unit_type',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Product\Database\Factories\UnitFactory::new();
    }

    /**
     * Busqueda parcial del listado (rediseno de Productos, 17-11): antes el filtro era
     * filter[name] exacto y escribir parte del nombre no encontraba nada.
     */
    public function scopeSearch($query, $term)
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';

        return $query->where(function ($q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('code', 'like', $like);
        });
    }
}
