<?php

namespace Modules\Product\JsonApi\V1\Products;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use Modules\Product\Models\Product;

class ProductSchema extends Schema
{
    public static string $model = Product::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('sku')->sortable(),
            Str::make('description'),
            Str::make('fullDescription', 'full_description'),
            Number::make('price')->sortable(),
            Number::make('cost')->sortable(),
            Boolean::make('iva'),
            Str::make('satClaveProdServ', 'sat_clave_prod_serv'),
            Str::make('satClaveUnidad', 'sat_clave_unidad'),
            Str::make('productType', 'product_type'),
            Number::make('taxRate', 'tax_rate'),
            Boolean::make('isActive', 'is_active')->sortable(),
            Boolean::make('isPublic', 'is_public')->sortable(),

            // Oferta: el catalogo publico ya lee estos campos (PublicProductSchema
            // + scope onSale con ventana de fechas), pero no estaban expuestos en
            // el schema de administracion, asi que no habia forma de marcar un
            // producto en oferta y la seccion "Ofertas del Mes" del sitio quedaba
            // siempre vacia.
            Boolean::make('isOnSale', 'is_on_sale')->sortable(),
            DateTime::make('saleStartsAt', 'sale_starts_at'),
            DateTime::make('saleEndsAt', 'sale_ends_at'),
            Str::make('saleBadge', 'sale_badge'),

            Str::make('imgPath', 'img_path'),
            Str::make('datasheetPath', 'datasheet_path'),
            Str::make('imgUrl')->readOnly()->extractUsing(
                static fn($model) => $model->img_url
            ),
            Str::make('datasheetUrl')->readOnly()->extractUsing(
                static fn($model) => $model->datasheet_url
            ),

            // Relaciones
            BelongsTo::make('unit')->type('units'),
            BelongsTo::make('category')->type('categories'),
            BelongsTo::make('brand')->type('brands'),
            BelongsTo::make('currency')->type('currencies'),
            HasMany::make('stock')->type('stocks'),
            HasMany::make('images')->type('product-images'),

            DateTime::make('createdAt', 'created_at')->readOnly()->sortable(),
            DateTime::make('updatedAt', 'updated_at')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('name'),
            Where::make('sku'),
            Where::make('search_name', 'name')->deserializeUsing(
                static fn($value) => "%{$value}%"
            )->using('like'),
            Where::make('search_sku', 'sku')->deserializeUsing(
                static fn($value) => "%{$value}%"
            )->using('like'),
            Where::make('search_description', 'description')->deserializeUsing(
                static fn($value) => "%{$value}%"
            )->using('like'),
            Scope::make('search'),
            Where::make('unit_id'),
            Where::make('category_id'),
            Where::make('brand_id'),
            Where::make('currency_id'),
            Where::make('is_active')->asBoolean(),
            Where::make('is_public')->asBoolean(),
            Where::make('is_on_sale')->asBoolean(),
            WhereIn::make('brands', 'brand_id')->delimiter(','),
            WhereIn::make('categories', 'category_id')->delimiter(','),
            WhereIn::make('units', 'unit_id')->delimiter(','),
        ];
    }

    public function includePaths(): array
    {
        return [
            'unit',
            'category',
            'brand',
            'currency',
            'stock',
            'stock.warehouse',
            'images',
        ];
    }

    /**
     * Bloque FE del ciclo (cierre del riesgo 39k): sin default, una peticion SIN
     * page devolvia los ~39,000 productos de golpe; PHP moria sin headers CORS y el
     * navegador reportaba "CORS" (mismo sintoma que el falso-CORS de Ofertas). Esta
     * version de laravel-json-api no tiene withDefaultPerPage en PagePagination; el
     * mecanismo es la propiedad defaultPagination del Schema, que aplica cuando el
     * cliente no manda parametros page.
     */
    protected ?array $defaultPagination = ['number' => 1, 'size' => 50];

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return 'products';
    }
}
