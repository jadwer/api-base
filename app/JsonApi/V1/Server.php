<?php

namespace App\JsonApi\V1;

use Illuminate\Support\Facades\Auth;
use LaravelJsonApi\Core\Server\Server as BaseServer;
use Modules\Audit\JsonApi\V1\Audits\AuditAuthorizer;
use Modules\Audit\JsonApi\V1\Audits\AuditSchema;
use Modules\User\JsonApi\V1\Users\UserSchema;
use Modules\PageBuilder\JsonApi\V1\Pages\PageSchema;

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

            // Aquí puedes ir agregando más schemas por módulo:
            // \Modules\Auth\JsonApi\V1\SomethingSchema::class,
            // \Modules\Product\JsonApi\V1\ProductSchema::class,
        ];
    }

    protected function authorizers(): array
{
    return [
        'audits' => AuditAuthorizer::class,
    ];
}
}
