<?php

namespace Modules\Inventory\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Error de negocio del fraccionamiento con codigo estable para el frontend.
 *
 * Se responde como JSON:API errors[{status, code, title, detail, meta}] mas
 * `message` (el mismo detalle) para clientes que solo leen message.
 */
class FractionationException extends \RuntimeException
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public readonly string $errorCode,
        public readonly string $title,
        string $detail,
        public readonly int $status = 422,
        public readonly array $meta = [],
    ) {
        parent::__construct($detail);
    }

    public function toResponse(): JsonResponse
    {
        return self::errorResponse($this->status, $this->title, $this->getMessage(), $this->errorCode, $this->meta);
    }

    /**
     * @param array<string, mixed> $meta
     */
    public static function errorResponse(int $status, string $title, string $detail, ?string $code = null, array $meta = []): JsonResponse
    {
        $error = array_filter([
            'status' => (string) $status,
            'code' => $code,
            'title' => $title,
            'detail' => $detail,
            'meta' => $meta ?: null,
        ], static fn ($value) => $value !== null);

        return response()->json([
            'message' => $detail,
            'errors' => [$error],
        ], $status);
    }
}
