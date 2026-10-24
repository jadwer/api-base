<?php

namespace Modules\Product\Models;

/**
 * Categoria vista desde el catalogo publico. Misma tabla que Category, clase
 * propia para que el servidor publico la serialice con PublicCategorySchema:
 * con dos schemas sobre el mismo modelo, laravel-json-api resuelve el ultimo
 * registrado (CategorySchema) y la respuesta salia como 'categories' con el
 * conteo de todos los productos, privados incluidos.
 */
class PublicCategory extends Category
{
    protected $table = 'categories';

    public function getForeignKey(): string
    {
        return 'category_id';
    }
}
