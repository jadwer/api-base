<?php

namespace App\JsonApi\V1;

use Illuminate\Support\Facades\Auth;
use LaravelJsonApi\Core\Server\Server as BaseServer;
use Modules\Product\JsonApi\V1\PublicProducts\PublicProductSchema;
use Modules\Product\JsonApi\V1\Units\UnitSchema;
use Modules\Product\JsonApi\V1\Categories\CategorySchema;
use Modules\Product\JsonApi\V1\Brands\BrandSchema;

class PublicServer extends BaseServer
{
    /**
     * The base URI namespace for this server.
     *
     * @var string
     */
    protected string $baseUri = '/api/public/v1';

    /**
     * Bootstrap the server when it is handling an HTTP request.
     *
     * @return void
     */
    public function serving(): void
    {
        // Public endpoints don't require authentication
    }

    /**
     * Get the server's list of schemas.
     *
     * @return array
     */
    protected function allSchemas(): array
    {
        return [
            // Public Product Catalog - only what's needed for public access
            PublicProductSchema::class,
            UnitSchema::class,
            CategorySchema::class,
            BrandSchema::class,
        ];
    }

    protected function authorizers(): array
    {
        return [
            'public-products' => \Modules\Product\JsonApi\V1\PublicProducts\PublicProductAuthorizer::class,
            'units' => \Modules\Product\JsonApi\V1\Units\UnitAuthorizer::class,
            'categories' => \Modules\Product\JsonApi\V1\Categories\CategoryAuthorizer::class,
            'brands' => \Modules\Product\JsonApi\V1\Brands\BrandAuthorizer::class,
        ];
    }
}