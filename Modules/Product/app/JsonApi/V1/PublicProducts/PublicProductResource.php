<?php

namespace Modules\Product\JsonApi\V1\PublicProducts;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class PublicProductResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'name'            => $this->name,
            'sku'             => $this->sku,
            'description'     => $this->description,
            'fullDescription' => $this->full_description,
            'price'           => $this->price,
            'cost'            => $this->cost,
            'compareAtPrice'  => $this->compare_at_price,
            'isOnSale'        => $this->is_on_sale,
            'saleStartsAt'    => $this->sale_starts_at,
            'saleEndsAt'      => $this->sale_ends_at,
            'saleBadge'       => $this->sale_badge,
            'iva'             => $this->iva,
            'imgPath'         => $this->img_path,
            'datasheetPath'   => $this->datasheet_path,
            'imageUrl'        => $this->img_url,
            'datasheetUrl'    => $this->datasheet_url,
            'createdAt'       => $this->created_at,
            'updatedAt'       => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'unit' => $this->relation('unit'),
            'category' => $this->relation('category'),
            'brand' => $this->relation('brand'),
        ];
    }
}