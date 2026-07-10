<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Product extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'sku', 'price', 'cost', 'is_active',
                'category_id', 'brand_id', 'unit_id'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'sku',
        'description',
        'full_description',
        'price',
        'cost',
        'compare_at_price',
        'iva',
        'sat_clave_prod_serv',
        'sat_clave_unidad',
        'product_type',
        'tax_rate',
        'is_on_sale',
        'sale_starts_at',
        'sale_ends_at',
        'img_path',
        'datasheet_path',
        'unit_id',
        'category_id',
        'brand_id',
        'currency_id',
        'is_active',
        'average_rating',
        'total_reviews',
        'total_sales',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'price' => 'float',
        'cost' => 'float',
        'compare_at_price' => 'float',
        'iva' => 'boolean',
        'tax_rate' => 'float',
        'is_on_sale' => 'boolean',
        'sale_starts_at' => 'datetime',
        'sale_ends_at' => 'datetime',
        'unit_id' => 'integer',
        'category_id' => 'integer',
        'brand_id' => 'integer',
        'currency_id' => 'integer',
        'is_active' => 'boolean',
        'average_rating' => 'float',
        'total_reviews' => 'integer',
        'total_sales' => 'integer',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\Modules\Ecommerce\Models\Currency::class);
    }

    /**
     * Un producto puede tener registros de stock en diferentes bodegas.
     */
    public function stock(): HasMany
    {
        return $this->hasMany(\Modules\Inventory\Models\Stock::class);
    }

    /**
     * Un producto puede tener múltiples lotes.
     */
    public function productBatches(): HasMany
    {
        return $this->hasMany(\Modules\Inventory\Models\ProductBatch::class);
    }

    /**
     * Un producto puede tener múltiples reviews (Advanced Ecommerce - Phase 4.3.1)
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(\Modules\Ecommerce\Models\ProductReview::class);
    }

    /**
     * Only approved reviews
     */
    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', 'approved');
    }

    /**
     * Product images gallery, ordered by sort_order.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Primary image only.
     */
    public function primaryImage(): HasMany
    {
        return $this->hasMany(ProductImage::class)->where('is_primary', true);
    }

    /**
     * PR-M003: Un producto puede tener múltiples variantes.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * PR-M003: Variantes activas del producto.
     */
    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    /**
     * PR-M003: Variante por defecto del producto.
     */
    public function defaultVariant(): HasMany
    {
        return $this->variants()->where('is_default', true);
    }

    /**
     * PR-M003: Verificar si el producto tiene variantes.
     */
    public function hasVariants(): bool
    {
        return $this->variants()->exists();
    }

    /**
     * Update cached rating and review count
     * Call this method when reviews are added/updated/deleted
     */
    public function updateCachedReviewStats(): void
    {
        $this->average_rating = round($this->approvedReviews()->avg('rating') ?? 0.0, 1);
        $this->total_reviews = $this->approvedReviews()->count();
        $this->saveQuietly();
    }

    /**
     * Scope para búsqueda global en nombre, SKU y descripción
     */
    public function scopeSearch($query, $term)
    {
        $searchTerm = "%{$term}%";
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'like', $searchTerm)
              ->orWhere('sku', 'like', $searchTerm)
              ->orWhere('description', 'like', $searchTerm);
        });
    }

    /**
     * Scope for products on sale (active offers)
     */
    public function scopeOnSale($query)
    {
        return $query->where('is_on_sale', true)
            ->where(function ($q) {
                $q->whereNull('sale_starts_at')
                    ->orWhere('sale_starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('sale_ends_at')
                    ->orWhere('sale_ends_at', '>=', now());
            });
    }

    /**
     * Check if product is currently on sale
     */
    public function isCurrentlyOnSale(): bool
    {
        if (!$this->is_on_sale) {
            return false;
        }

        $now = now();

        if ($this->sale_starts_at && $this->sale_starts_at > $now) {
            return false;
        }

        if ($this->sale_ends_at && $this->sale_ends_at < $now) {
            return false;
        }

        return true;
    }

    /**
     * Effective tax rate (percentage, e.g. 16.0).
     *
     * WS9: tax_rate is the configurable rate; NULL falls back to the legacy
     * iva boolean (true = 16%, false = 0%) so products created before the SAT
     * fields keep their behavior. To represent "Exento" fiscally, use the SAT
     * tasa catalog (sat_tasa_o_cuota) at invoicing time; at product level a
     * null tax_rate is NOT treated as Exento to avoid silently dropping IVA
     * on legacy flows (quotes use effective_tax_rate ?? 0).
     */
    public function getEffectiveTaxRateAttribute(): float
    {
        if ($this->tax_rate !== null) {
            return (float) $this->tax_rate;
        }

        return $this->iva ? 16.0 : 0.0;
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentageAttribute(): ?int
    {
        if (!$this->compare_at_price || $this->compare_at_price <= $this->price) {
            return null;
        }

        return (int) round((($this->compare_at_price - $this->price) / $this->compare_at_price) * 100);
    }

    /**
     * Get savings amount
     */
    public function getSavingsAttribute(): ?float
    {
        if (!$this->compare_at_price || $this->compare_at_price <= $this->price) {
            return null;
        }

        return $this->compare_at_price - $this->price;
    }

    /**
     * Get full URL for product image
     */
    public function getImgUrlAttribute(): ?string
    {
        if (empty($this->img_path)) {
            return null;
        }

        // Si ya es una URL completa, devolverla tal cual
        if (str_starts_with($this->img_path, 'http://') || str_starts_with($this->img_path, 'https://')) {
            return $this->img_path;
        }

        // Si es una ruta absoluta del public directory (legacy data)
        if (str_starts_with($this->img_path, '/images/') || str_starts_with($this->img_path, '/storage/')) {
            return asset($this->img_path);
        }

        // Si ya incluye el directorio (nuevo formato: products/filename.jpg)
        if (str_starts_with($this->img_path, 'products/')) {
            return asset('storage/' . $this->img_path);
        }

        // Solo el nombre del archivo (legacy) - agregar prefijo de storage
        return asset('storage/products/' . $this->img_path);
    }

    /**
     * Get full URL for product datasheet
     */
    public function getDatasheetUrlAttribute(): ?string
    {
        if (empty($this->datasheet_path)) {
            return null;
        }

        // Si ya es una URL completa, devolverla tal cual
        if (str_starts_with($this->datasheet_path, 'http://') || str_starts_with($this->datasheet_path, 'https://')) {
            return $this->datasheet_path;
        }

        // Si es una ruta absoluta del public directory (legacy data)
        if (str_starts_with($this->datasheet_path, '/datasheets/') || str_starts_with($this->datasheet_path, '/storage/')) {
            return asset($this->datasheet_path);
        }

        // Si ya incluye el directorio (nuevo formato: datasheets/filename.pdf)
        if (str_starts_with($this->datasheet_path, 'datasheets/')) {
            return asset('storage/' . $this->datasheet_path);
        }

        // Solo el nombre del archivo (legacy) - agregar prefijo de storage
        return asset('storage/datasheets/' . $this->datasheet_path);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Product\Database\Factories\ProductFactory::new();
    }
}
