<?php

namespace Modules\Product\JsonApi\V1\Products;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class ProductRequest extends ResourceRequest
{
    public function rules(): array
    {
        $product = $this->model();

        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($product),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'fullDescription' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'iva' => ['required', 'boolean'],
            // WS9 SAT fields. No exists rule: the SAT catalogs can be empty
            // on fresh installs until sat:sync-catalogs runs.
            'satClaveProdServ' => ['nullable', 'string', 'max:10'],
            'satClaveUnidad' => ['nullable', 'string', 'max:20'],
            'productType' => ['nullable', 'string', Rule::in(['finished', 'raw_material', 'both'])],
            'taxRate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'isPublic' => ['sometimes', 'boolean'],
            // Oferta. Las fechas son opcionales: sin ellas la oferta corre
            // mientras isOnSale este activo (asi lo resuelve el scope onSale
            // del modelo). Si se dan ambas, el fin debe ser posterior al inicio.
            'isOnSale' => ['sometimes', 'boolean'],
            'saleStartsAt' => ['nullable', 'date'],
            'saleEndsAt' => ['nullable', 'date', 'after_or_equal:saleStartsAt'],
            'saleBadge' => ['nullable', 'string', 'max:50'],
            'imgPath' => ['nullable', 'string', 'max:500', 'not_regex:/\.\./'],
            'datasheetPath' => ['nullable', 'string', 'max:500', 'not_regex:/\.\./'],
            'unit' => JsonApiRule::toOne(),
            'category' => JsonApiRule::toOne(),
            'brand' => JsonApiRule::toOne(),
            'currency' => ['nullable', JsonApiRule::toOne()],
        ];
    }

    public function withDefaults(): array
    {
        return [
            'iva' => false,
        ];
    }
}
