<?php

namespace Modules\Product\JsonApi\V1\Products;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class ProductResource extends JsonApiResource
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
            'iva'             => $this->iva,
            'satClaveProdServ' => $this->sat_clave_prod_serv,
            'satClaveUnidad'  => $this->sat_clave_unidad,
            'productType'     => $this->product_type,
            'taxRate'         => $this->tax_rate,
            'isActive'        => $this->is_active,
            'imgPath'         => $this->img_path,
            'datasheetPath'   => $this->datasheet_path,
            'imgUrl'          => $this->img_url,
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
            'currency' => $this->relation('currency'),
            'images' => $this->relation('images'),
        ];
    }
}
