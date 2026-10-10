<?php

namespace Modules\Product\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Product\Models\Product;

/**
 * Revalidacion bajo demanda de la ficha publica (P1 granel, decision D5).
 *
 * La ficha /productos/[slug] del frontend es ISR de 1 h. Cuando cambia un
 * producto o su galeria, el backend avisa al route handler
 * POST {STOREFRONT_REVALIDATE_URL} con Authorization: Bearer
 * {STOREFRONT_REVALIDATE_TOKEN}. El token vive solo en servidores (nunca en
 * el navegador).
 *
 * Los avisos de una misma peticion se juntan y se envian UNA vez al terminar
 * (app()->terminating: despues de mandar la respuesta, asi que no bloquea al
 * usuario). Sin URL o sin token no hace nada. Cualquier falla solo se
 * registra en el log: la revalidacion nunca rompe un guardado.
 */
class StorefrontRevalidator
{
    /** Mas productos que esto en una peticion (bulk, import): se invalida todo el catalogo por tag. */
    public const MAX_PRODUCTS = 50;

    /** @var array<int, string|null> id => slug */
    private array $products = [];

    private bool $scheduled = false;

    private bool $everything = false;

    public function enabled(): bool
    {
        return filled(config('services.storefront.revalidate_url'))
            && filled(config('services.storefront.revalidate_token'));
    }

    public function product(Product $product): void
    {
        if (! $this->enabled() || ! $product->id) {
            return;
        }
        $slug = is_string($product->slug) && trim($product->slug) !== '' ? $product->slug : null;
        $this->products[(int) $product->id] = $slug ?? ($this->products[(int) $product->id] ?? null);
        $this->schedule();
    }

    /** Cambio masivo (comando, import): invalida todo el catalogo por tag. */
    public function everything(): void
    {
        if (! $this->enabled()) {
            return;
        }
        $this->everything = true;
        $this->schedule();
    }

    public function productId(int $productId): void
    {
        if (! $this->enabled() || isset($this->products[$productId])) {
            return;
        }
        $product = Product::query()->select(['id', 'slug'])->find($productId);
        if ($product) {
            $this->product($product);
        }
    }

    /** @return array<int, string|null> */
    public function pending(): array
    {
        return $this->products;
    }

    /**
     * Cuerpo que recibe el route handler del frontend.
     *
     * @return array{paths: list<string>, tags: list<string>}
     */
    public function payload(): array
    {
        if ($this->everything || count($this->products) > self::MAX_PRODUCTS) {
            return ['paths' => [], 'tags' => ['public-products', 'public-catalog']];
        }

        $paths = [];
        $tags = ['public-catalog'];
        foreach ($this->products as $id => $slug) {
            $tags[] = "public-product:{$id}";
            $paths[] = "/productos/{$id}";
            if ($slug !== null) {
                $tags[] = "public-product-slug:{$slug}";
                $paths[] = "/productos/{$slug}";
            }
        }

        return ['paths' => array_values(array_unique($paths)), 'tags' => array_values(array_unique($tags))];
    }

    public function flush(): void
    {
        $this->scheduled = false;
        if (($this->products === [] && ! $this->everything) || ! $this->enabled()) {
            $this->products = [];
            $this->everything = false;

            return;
        }

        $payload = $this->payload();
        $this->products = [];
        $this->everything = false;

        try {
            $response = Http::acceptJson()
                ->withToken((string) config('services.storefront.revalidate_token'))
                ->connectTimeout(2)
                ->timeout((int) config('services.storefront.revalidate_timeout', 5))
                ->post((string) config('services.storefront.revalidate_url'), $payload);

            if (! $response->successful()) {
                Log::warning('Revalidacion de la tienda: respuesta ' . $response->status(), [
                    'body' => mb_substr($response->body(), 0, 300),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Revalidacion de la tienda fallo: ' . $e->getMessage());
        }
    }

    private function schedule(): void
    {
        if ($this->scheduled) {
            return;
        }
        $this->scheduled = true;
        app()->terminating(fn () => $this->flush());
    }
}
