<?php

namespace App\JsonApi\V1;

use Illuminate\Support\Facades\Auth;
use LaravelJsonApi\Core\Server\Server as BaseServer;
use Modules\Audit\JsonApi\V1\Audits\AuditAuthorizer;
use Modules\Audit\JsonApi\V1\Audits\AuditSchema;
use Modules\User\JsonApi\V1\Users\UserSchema;
use Modules\PageBuilder\JsonApi\V1\Pages\PageSchema;
use Modules\PermissionManager\JsonApi\V1\Permissions\PermissionSchema;
use Modules\PermissionManager\JsonApi\V1\Roles\RoleSchema;
use Modules\Product\JsonApi\V1\Products\ProductSchema;
use Modules\Product\JsonApi\V1\Units\UnitSchema;
use Modules\Product\JsonApi\V1\Categories\CategorySchema;
use Modules\Product\JsonApi\V1\Brands\BrandSchema;

class Server extends BaseServer
{

    /**
     * The base URI namespace for this server.
     *
     * @var string
     */
    protected string $baseUri = '/api/v1';

    /**
     * Bootstrap the server when it is handling an HTTP request.
     *
     * @return void
     */
    public function serving(): void
    {
        // no-op
    Auth::shouldUse('sanctum');
    }

    /**
     * Get the server's list of schemas.
     *
     * @return array
     */
    protected function allSchemas(): array
    {
        return [
            UserSchema::class,
            AuditSchema::class,
            PageSchema::class,
            RoleSchema::class,
            PermissionSchema::class,

            // Product Module
            ProductSchema::class,
            UnitSchema::class,
            CategorySchema::class,
            BrandSchema::class,
        ];
    }

    protected function authorizers(): array
{
    return [
        'audits' => AuditAuthorizer::class,
        'products' => \Modules\Product\JsonApi\V1\Products\ProductAuthorizer::class,
        'units' => \Modules\Product\JsonApi\V1\Units\UnitAuthorizer::class,
        'categories' => \Modules\Product\JsonApi\V1\Categories\CategoryAuthorizer::class,
        'brands' => \Modules\Product\JsonApi\V1\Brands\BrandAuthorizer::class,
    ];
}
}
