<?php

namespace App\JsonApi\V1;

use Illuminate\Support\Facades\Auth;
use LaravelJsonApi\Core\Server\Server as BaseServer;

use Modules\User\JsonApi\V1\Users\UserSchema;

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
            UserSchema::class
            // Aquí puedes ir agregando más schemas por módulo:
            // \Modules\Auth\JsonApi\V1\SomethingSchema::class,
            // \Modules\Product\JsonApi\V1\ProductSchema::class,
        ];
    }
}
