<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * PR-M003: Variant Attribute Value Model
 *
 * Represents specific values for attributes: Small, Medium, Large for Size.
 *
 * @property int $id
 * @property int $variant_attribute_id
 * @property string $value
 * @property string $code
 * @property string|null $display_value
 * @property string|null $color_hex
 * @property int $sort_order
 * @property bool $is_active
 * @property-read VariantAttribute $variantAttribute
 * @property-read \Illuminate\Database\Eloquent\Collection|ProductVariant[] $productVariants
 */
class VariantAttributeValue extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'id' => 'integer',
        'variant_attribute_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    /**
     * Parent attribute (Size, Color).
     */
    public function variantAttribute(): BelongsTo
    {
        return $this->belongsTo(VariantAttribute::class);
    }

    /**
     * Product variants using this value.
     */
    public function productVariants(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'product_variant_attributes',
            'variant_attribute_value_id',
            'product_variant_id'
        )->withTimestamps();
    }

    /**
     * Scope for active values.
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
        return $query->orderBy('sort_order')->orderBy('value');
    }

    /**
     * Get display name (display_value or value).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->display_value ?? $this->value;
    }

    protected static function newFactory()
    {
        return \Modules\Product\Database\Factories\VariantAttributeValueFactory::new();
    }
}
