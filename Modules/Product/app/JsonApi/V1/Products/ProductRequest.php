<?php

namespace Modules\Product\JsonApi\V1\Products;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;
use Modules\Product\Models\Product;

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
            // P2 granel: price es obligatorio solo con precio fijo. Sin
            // pricingType en el alta se asume fixed (default de la columna). En
            // PATCH el merge de JSON:API trae el pricingType y el price
            // vigentes, asi que pasar de quote_only a fixed sin mandar price da
            // 422. En quote_only el precio que llegue se descarta
            // (Product::normalizePricing).
            'pricingType' => ['sometimes', 'string', Rule::in(Product::PRICING_TYPES)],
            // P2b (D8): con precio fijo el precio debe ser mayor a 0 (un
            // producto vendible en $0 obliga a respetarlo ante PROFECO). Sin
            // precio o en 0 el producto se da de alta como sujeto a cotizacion.
            'price' => [
                Rule::requiredIf(fn () => ! $this->isQuoteOnlyPayload()),
                'nullable',
                'numeric',
                Rule::when(fn () => $this->isQuoteOnlyPayload(), ['min:0'], ['min:0.01']),
            ],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'iva' => ['required', 'boolean'],
            // WS9 SAT fields. No exists rule: the SAT catalogs can be empty
            // on fresh installs until sat:sync-catalogs runs.
            'satClaveProdServ' => ['nullable', 'string', 'max:10'],
            'satClaveUnidad' => ['nullable', 'string', 'max:20'],
            'productType' => ['nullable', 'string', Rule::in(['finished', 'raw_material', 'both'])],
            'taxRate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'isPublic' => ['sometimes', 'boolean'],
            'isActive' => ['sometimes', 'boolean'],
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

    /**
     * Una oferta sin precio no tiene sentido: si el cliente pide isOnSale=true
     * para un producto sujeto a cotizacion se rechaza en vez de apagarla en
     * silencio. Se mira solo lo que mando el cliente (no el valor vigente que
     * agrega el merge), para que cambiar a quote_only un producto que estaba
     * en oferta no exija mandar isOnSale=false.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->isQuoteOnlyPayload()) {
                return;
            }

            $attributes = (array) $this->json('data.attributes', []);
            if (($attributes['isOnSale'] ?? null) === true) {
                $validator->errors()->add(
                    'isOnSale',
                    'Un producto con precio sujeto a cotizacion no puede estar en oferta.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'price.required' => 'El precio de venta es obligatorio cuando el tipo de precio es fijo.',
            'price.min' => 'El precio de venta debe ser mayor a 0 cuando el tipo de precio es fijo. Si el producto no tiene precio, usa "Precio sujeto a cotizacion".',
            'pricingType.in' => 'El tipo de precio debe ser: ' . implode(', ', Product::PRICING_TYPES) . '.',
        ];
    }

    /**
     * pricingType efectivo: el del payload (en PATCH ya mezclado con el
     * vigente) o fixed si no viene.
     */
    private function isQuoteOnlyPayload(): bool
    {
        $data = $this->validationData();

        return ($data['pricingType'] ?? Product::PRICING_FIXED) === Product::PRICING_QUOTE_ONLY;
    }

    public function withDefaults(): array
    {
        return [
            'iva' => false,
            'pricingType' => Product::PRICING_FIXED,
        ];
    }
}
