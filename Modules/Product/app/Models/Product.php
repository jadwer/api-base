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
        // Faltaba en fillable: la columna y el schema publico ya lo exponian,
        // pero al guardar se descartaba en silencio (mass assignment).
        'sale_badge',
        'img_path',
        'datasheet_path',
        'unit_id',
        'category_id',
        'brand_id',
        'currency_id',
        'is_active',
        'is_public',
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
        'is_public' => 'boolean',
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
     * Numero maximo de filas que el fallback Levenshtein carga en memoria.
     * Acota el universo para no traer toda la tabla cuando las capas 1+2
     * devuelven cero resultados. Si se trunca, se deja rastro en el log.
     */
    public const FUZZY_CANDIDATE_CAP = 2000;

    /**
     * Scope para busqueda global tolerante a errores de escritura (typos).
     *
     * Tres capas en cascada, de barata a cara:
     *   1. Normalizacion: el termino se pasa a minusculas y se le quitan
     *      acentos (en PHP). La comparacion en SQL usa LOWER(columna), que
     *      existe tanto en MySQL como en SQLite. LIMITE CONOCIDO: solo se
     *      normaliza el termino, no la columna. Quitar acentos de la columna
     *      de forma portable entre MySQL y SQLite no es viable sin una UDF o
     *      columna generada, asi que se acepta que los acentos de la columna
     *      permanezcan. Cubre el caso comun (usuario escribe sin acentos).
     *   2. Tokens: el termino se parte en palabras y se exige que TODAS
     *      aparezcan (AND), cada una en cualquier campo (name/sku/description).
     *      Asi "lavadora industrial" encuentra "Industrial de Lavado Lavadora".
     *   3. Fallback Levenshtein: SOLO si las capas 1+2 devuelven 0 resultados,
     *      se aplica un filtro tolerante en PHP (portable, no depende de UDF).
     *
     * El Scope filter de JSON:API (Scope::make('search')) mapea a este metodo,
     * por lo que aqui vive la logica completa para admin y publico.
     */
    public function scopeSearch($query, $term)
    {
        $normalized = static::normalizeSearchTerm($term);

        if ($normalized === '') {
            return $query;
        }

        // Capas 1 + 2: tokens en AND, cada token en cualquier campo.
        $tokens = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        // Registra cuantas condiciones where existian antes (otros filtros
        // JSON:API como category_id, is_active) para poder revertir SOLO las
        // que agrega la busqueda si hay que ir al fallback, sin tocar el resto.
        $base = $query->getQuery();
        $whereCountBefore = count($base->wheres ?? []);
        $bindingsBefore = $base->bindings['where'] ?? [];

        $query->where(function ($outer) use ($tokens) {
            foreach ($tokens as $token) {
                $like = '%' . $token . '%';
                $outer->where(function ($q) use ($like) {
                    $q->whereRaw('LOWER(name) LIKE ?', [$like])
                      ->orWhereRaw('LOWER(sku) LIKE ?', [$like])
                      ->orWhereRaw('LOWER(description) LIKE ?', [$like]);
                });
            }
        });

        // Capa 3: fallback Levenshtein solo si las capas 1+2 no matchearon nada.
        // Se clona para contar sin consumir el builder original (que JSON:API
        // sigue usando para aplicar orden, paginacion, etc.).
        if ((clone $query)->count() === 0) {
            $ids = static::fuzzyMatchIds($normalized, $tokens);

            // Revierte SOLO la condicion que agrego la busqueda (el grupo where
            // anidado) y sus bindings, preservando otros filtros previos. Luego
            // aplica whereIn de ids. Si no hubo candidatos, whereIn([]) devuelve
            // vacio (no explota trayendo toda la tabla).
            $base->wheres = array_slice($base->wheres, 0, $whereCountBefore);
            $base->bindings['where'] = $bindingsBefore;
            $query->whereIn('id', $ids);
        }

        return $query;
    }

    /**
     * Normaliza un termino de busqueda: minusculas y sin acentos.
     * Usa iconv para transliterar (é -> e, ñ -> n) de forma portable en PHP.
     */
    public static function normalizeSearchTerm(?string $term): string
    {
        $term = trim((string) $term);

        if ($term === '') {
            return '';
        }

        // Transliteracion ASCII: quita acentos y diacriticos.
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $term);
        if ($ascii !== false) {
            // iconv puede dejar residuos como 'n para ñ segun locale; limpiar.
            $ascii = preg_replace('/[^\x20-\x7E]/', '', $ascii);
            $ascii = str_replace(['\'', '`', '^', '~', '"'], '', $ascii);
            $term = $ascii;
        }

        return mb_strtolower(trim($term));
    }

    /**
     * Fallback tolerante a typos con levenshtein() de PHP.
     *
     * Acota el universo de candidatos a FUZZY_CANDIDATE_CAP filas para no
     * cargar toda la tabla. Compara cada token del termino contra las palabras
     * del name normalizado (y el sku) con un umbral proporcional a la longitud.
     * Un producto matchea si TODOS los tokens tienen al menos una palabra
     * cercana (coherente con el AND de la capa 2).
     *
     * @param  array<int,string>  $tokens
     * @return array<int,int>  ids de productos que matchean
     */
    protected static function fuzzyMatchIds(string $normalized, array $tokens): array
    {
        if ($tokens === []) {
            return [];
        }

        // Universo acotado: solo columnas necesarias, con cap de filas.
        $candidates = static::query()
            ->select(['id', 'name', 'sku'])
            ->limit(static::FUZZY_CANDIDATE_CAP)
            ->get();

        if ($candidates->count() === static::FUZZY_CANDIDATE_CAP) {
            \Illuminate\Support\Facades\Log::warning(
                'Product fuzzy search truncated candidate set',
                ['cap' => static::FUZZY_CANDIDATE_CAP, 'term' => $normalized]
            );
        }

        $ids = [];

        foreach ($candidates as $candidate) {
            $haystackWords = static::fuzzyHaystackWords($candidate->name, $candidate->sku);

            $allTokensMatch = true;
            foreach ($tokens as $token) {
                if (!static::tokenHasCloseWord($token, $haystackWords)) {
                    $allTokensMatch = false;
                    break;
                }
            }

            if ($allTokensMatch) {
                $ids[] = (int) $candidate->id;
            }
        }

        return $ids;
    }

    /**
     * Palabras normalizadas del name y sku de un candidato.
     *
     * @return array<int,string>
     */
    protected static function fuzzyHaystackWords(?string $name, ?string $sku): array
    {
        $text = static::normalizeSearchTerm(trim(($name ?? '') . ' ' . ($sku ?? '')));

        return preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Un token esta "cerca" de una palabra si su distancia de Levenshtein
     * cae bajo un umbral proporcional a la longitud del token.
     *
     * @param  array<int,string>  $haystackWords
     */
    protected static function tokenHasCloseWord(string $token, array $haystackWords): bool
    {
        $len = mb_strlen($token);
        // Umbral: palabras cortas toleran menos que largas.
        $threshold = $len <= 4 ? 1 : (int) floor($len / 4);

        foreach ($haystackWords as $word) {
            // Coincidencia por substring exacta cuenta como distancia 0.
            if ($word === $token || str_contains($word, $token)) {
                return true;
            }

            if (levenshtein($token, $word) <= $threshold) {
                return true;
            }
        }

        return false;
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
