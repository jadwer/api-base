<?php

namespace Modules\Sales\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Regla de surtido que impide crear una remision (estado del pedido, cantidad
 * pendiente o existencia). Se responde 422 con el detalle en espanol.
 */
class RemissionRuleException extends \RuntimeException
{
    /** @param array<string, mixed> $details */
    public function __construct(string $message, private array $details = [])
    {
        parent::__construct($message);
    }

    /** @return array<string, mixed> */
    public function details(): array
    {
        return $this->details;
    }

    public function toResponse(): JsonResponse
    {
        return response()->json(array_merge(['error' => $this->getMessage()], $this->details), 422);
    }
}
