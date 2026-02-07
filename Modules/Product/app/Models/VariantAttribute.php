<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PR-M003: Variant Attribute Model
 *
 * Represents attribute types like Size, Color, Material.
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property bool $is_active
 * @property int $sort_order
 * @property-read \Illuminate\Database\Eloquent\Collection|VariantAttributeValue[] $values
 */
class VariantAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'description', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'id' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    /**
     * Attribute values (Size -> S, M, L, XL).
     */
    public function values(): HasMany
    {
        return $this->hasMany(VariantAttributeValue::class)->orderBy('sort_order');
    }

    /**
     * Scope for active attributes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    protected static function newFactory()
    {
        return \Modules\Product\Database\Factories\VariantAttributeFactory::new();
    }
}
