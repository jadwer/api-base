<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * PR-M003: Product Variant Model
 *
 * Represents a specific variant of a product with attribute combinations.
 *
 * @property int $id
 * @property int $product_id
 * @property string $sku
 * @property string|null $name
 * @property float|null $price
 * @property float|null $cost
 * @property float|null $weight
 * @property string|null $barcode
 * @property string|null $img_path
 * @property int $stock_quantity
 * @property int|null $low_stock_threshold
 * @property bool $is_active
 * @property bool $is_default
 * @property array|null $metadata
 * @property-read Product $product
 * @property-read \Illuminate\Database\Eloquent\Collection|VariantAttributeValue[] $attributeValues
 */
class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'sku', 'name', 'price', 'cost', 'weight',
        'barcode', 'img_path', 'stock_quantity', 'low_stock_threshold',
        'is_active', 'is_default', 'metadata',
    ];

    protected $casts = [
        'id' => 'integer',
        'product_id' => 'integer',
        'price' => 'float',
        'cost' => 'float',
        'weight' => 'float',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'stock_quantity' => 0,
        'is_active' => true,
        'is_default' => false,
    ];

    /**
     * Parent product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Attribute values for this variant (Red, Large, etc.).
     */
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            VariantAttributeValue::class,
            'product_variant_attributes',
            'product_variant_id',
            'variant_attribute_value_id'
        )->withTimestamps();
    }

    /**
     * Get effective price (variant price or product price).
     */
    public function getEffectivePriceAttribute(): ?float
    {
        return $this->price ?? $this->product?->price;
    }

    /**
     * Get effective cost (variant cost or product cost).
     */
    public function getEffectiveCostAttribute(): ?float
    {
        return $this->cost ?? $this->product?->cost;
    }

    /**
     * Get variant display name.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->name) {
            return $this->name;
        }

        $productName = $this->product?->name ?? '';
        $attributes = $this->attributeValues->pluck('display_name')->implode(' / ');

        return $attributes ? "{$productName} - {$attributes}" : $productName;
    }

    /**
     * Check if variant is low on stock.
     */
    public function isLowStock(): bool
    {
        if ($this->low_stock_threshold === null) {
            return false;
        }

        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    /**
     * Scope for active variants.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for default variant.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope for variants with stock.
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    /**
     * Scope for low stock variants.
     */
    public function scopeLowStock($query)
    {
        return $query->whereNotNull('low_stock_threshold')
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }

    protected static function newFactory()
    {
        return \Modules\Product\Database\Factories\ProductVariantFactory::new();
    }
}
