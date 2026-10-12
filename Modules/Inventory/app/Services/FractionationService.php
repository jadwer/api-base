<?php

namespace Modules\Inventory\Services;

use App\Support\DatabaseCompatibilityHelper as DBHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Exceptions\FractionationException;
use Modules\Inventory\Models\Fractionation;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\ProductBatch;
use Modules\Inventory\Models\ProductConversion;
use Modules\Inventory\Models\Stock;
use Modules\Product\Models\Product;
use Modules\Sales\Models\FolioSequence;

/**
 * Fraccionamiento de granel en presentaciones (P3 granel, 2026-10-10).
 *
 * Reglas:
 * - Idempotencia: el formulario manda idempotency_key; el mismo POST repetido
 *   devuelve el fraccionamiento ya hecho (sin movimientos ni folio nuevos).
 *   Tres capas: consulta previa, consulta con candado despues de bloquear el
 *   stock origen (serializa peticiones concurrentes) y UNIQUE en BD con
 *   captura de la violacion.
 * - Disponible = quantity - reserved_quantity (no se fracciona lo apartado).
 * - Ubicacion: si el origen tiene existencias en varias ubicaciones del
 *   almacen hay que elegir una; ambos movimientos usan la misma.
 * - Lotes: si el origen tiene lotes activos en el almacen, el lote es
 *   obligatorio; se descuenta y nace un lote destino con el folio como numero,
 *   lote y caducidad heredados del origen.
 * - Costo: destino = (consumido * costo origen) / producido; la merma se
 *   absorbe en el costo. El stock destino promedia (solo esta entrada).
 * - Contabilidad: es inventario a inventario por el mismo valor; el listener
 *   GL no postea COGS ni Accrual para reference_type fractionation.
 */
class FractionationService
{
    public const REFERENCE_TYPE = 'fractionation';

    private const EPSILON = 0.00005;

    public function __construct(protected InventoryMovementService $movementService)
    {
    }

    /**
     * Vista previa sin tocar la base de datos.
     *
     * @param array<string, mixed> $data source_product_id, destination_product_id, source_quantity,
     *                                   warehouse_id, warehouse_location_id?, product_batch_id?
     * @return array<string, mixed>
     */
    public function calculate(array $data): array
    {
        $conversion = $this->findConversion($data, true);
        $plan = $this->plan($conversion, (float) $data['source_quantity']);

        $sourceId = (int) $data['source_product_id'];
        $warehouseId = (int) $data['warehouse_id'];
        $locationId = isset($data['warehouse_location_id']) ? (int) $data['warehouse_location_id'] : null;

        $batch = null;
        if (!empty($data['product_batch_id'])) {
            $batch = ProductBatch::find((int) $data['product_batch_id']);
            if ($batch) {
                $this->assertBatchBelongs($batch, $sourceId, $warehouseId);
            }
        }

        $rows = $this->stockRows($sourceId, $warehouseId, $locationId, false);
        $row = $this->pickStockRow($rows, $locationId, $batch);
        $locationRequired = $row === null && $rows->count() > 1;

        $scopeLocation = $row->warehouse_location_id ?? $locationId;
        $batches = $this->fefoBatches($sourceId, $warehouseId, $scopeLocation);
        $batchRequired = $this->hasActiveBatches($sourceId, $warehouseId, $scopeLocation);
        $suggested = $this->suggestBatch($batches, $plan['consumed']);
        $evaluated = $batch ?? $suggested;

        $onHand = $row ? (float) $row->quantity : (float) $rows->sum(fn (Stock $s) => (float) $s->quantity);
        $reserved = $row ? (float) $row->reserved_quantity : (float) $rows->sum(fn (Stock $s) => (float) $s->reserved_quantity);
        $available = round($onHand - $reserved, 4);
        $batchAvailable = $evaluated ? $this->batchAvailable($evaluated) : null;

        $hasEnoughStock = $available + self::EPSILON >= $plan['consumed'];
        $hasEnoughBatch = $evaluated
            ? ($batchAvailable + self::EPSILON >= $plan['consumed'] && !$this->isExpired($evaluated))
            : !$batchRequired;

        $sourceUnitCost = $this->resolveSourceUnitCost($row ?? $rows->first(), $evaluated, $conversion->sourceProduct);
        $totalCost = round($plan['consumed'] * $sourceUnitCost, 4);
        $destinationUnitCost = $plan['produced'] > 0 ? round($totalCost / $plan['produced'], 6) : 0.0;

        $warnings = $this->warnings($plan, $sourceUnitCost, $conversion);
        if ($locationRequired) {
            $warnings[] = 'El producto origen tiene existencias en varias ubicaciones de este almacén: elige la ubicación.';
        }
        if ($batchRequired && !$evaluated) {
            $warnings[] = 'El producto origen maneja lotes y ninguno tiene disponible suficiente.';
        }

        return [
            'source_product' => $this->productPayload($conversion->sourceProduct),
            'destination_product' => $this->productPayload($conversion->destinationProduct),
            'source_quantity' => $plan['requested'],
            'requested_quantity' => $plan['requested'],
            'consumed_quantity' => $plan['consumed'],
            'leftover_quantity' => $plan['leftover'],
            'conversion_factor' => (float) $conversion->conversion_factor,
            'waste_percentage' => (float) $conversion->waste_percentage,
            'whole_units' => (bool) $conversion->whole_units,
            'produced_quantity' => $plan['produced'],
            'waste_quantity' => $plan['waste'],
            'available_stock' => $available,
            'on_hand_stock' => round($onHand, 4),
            'reserved_stock' => round($reserved, 4),
            'has_enough_stock' => $hasEnoughStock && $hasEnoughBatch,
            'source_unit_cost' => round($sourceUnitCost, 6),
            'destination_unit_cost' => $destinationUnitCost,
            'total_cost' => $totalCost,
            'has_cost' => $sourceUnitCost > 0,
            'location_required' => $locationRequired,
            'warehouse_location_id' => $row->warehouse_location_id ?? $locationId,
            'locations' => $this->locationsPayload($rows),
            'batch_required' => $batchRequired,
            'batches' => $batches->map(fn (ProductBatch $b) => $this->batchPayload($b))->values()->all(),
            'suggested_batch_id' => $suggested?->id,
            'product_batch_id' => $evaluated?->id,
            'batch_available' => $batchAvailable,
            'can_execute' => $hasEnoughStock && $hasEnoughBatch && !$locationRequired,
            'warnings' => $warnings,
        ];
    }

    /**
     * Ejecuta el fraccionamiento en una transaccion.
     *
     * @param array<string, mixed> $data campos de calculate + user_id, notes?, idempotency_key?
     * @return array{fractionation: Fractionation, replayed: bool, warnings: string[]}
     */
    public function execute(array $data): array
    {
        $key = !empty($data['idempotency_key']) ? (string) $data['idempotency_key'] : null;

        if ($key && ($existing = $this->findByIdempotencyKey($key, false))) {
            return $this->replay($existing, $data);
        }

        try {
            return DB::transaction(fn () => $this->executeInTransaction($data, $key));
        } catch (UniqueConstraintViolationException $e) {
            // Carrera: otra peticion con la misma clave gano el insert.
            if ($key && ($existing = $this->findByIdempotencyKey($key, false))) {
                return $this->replay($existing, $data);
            }
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array{fractionation: Fractionation, replayed: bool, warnings: string[]}
     */
    protected function executeInTransaction(array $data, ?string $key): array
    {
        $conversion = $this->findConversion($data, false);
        $plan = $this->plan($conversion, (float) $data['source_quantity']);

        $sourceId = (int) $data['source_product_id'];
        $destinationId = (int) $data['destination_product_id'];
        $warehouseId = (int) $data['warehouse_id'];
        $locationId = isset($data['warehouse_location_id']) ? (int) $data['warehouse_location_id'] : null;

        // 1. Candado del stock origen: serializa fraccionamientos concurrentes
        $rows = $this->stockRows($sourceId, $warehouseId, $locationId, true);

        // 2. Re-chequeo de la clave con lectura bloqueante (ve lo ya confirmado)
        if ($key && ($existing = $this->findByIdempotencyKey($key, true))) {
            return $this->replay($existing, $data);
        }

        // 3. Lote origen
        $batch = null;
        if (!empty($data['product_batch_id'])) {
            $batch = ProductBatch::whereKey((int) $data['product_batch_id'])->lockForUpdate()->first();
            if (!$batch) {
                throw new FractionationException('batch_not_found', 'Lote no encontrado', 'El lote seleccionado no existe.');
            }
            $this->assertBatchBelongs($batch, $sourceId, $warehouseId);
        }

        // 4. Ubicacion / fila de stock
        if ($rows->isEmpty()) {
            throw new FractionationException(
                'no_stock',
                'Sin existencias',
                'El producto origen no tiene existencias en este almacén' . ($locationId ? ' y ubicación.' : '.')
            );
        }
        $row = $this->pickStockRow($rows, $locationId, $batch);
        if (!$row) {
            throw new FractionationException(
                'location_required',
                'Ubicación requerida',
                "El producto origen tiene existencias en {$rows->count()} ubicaciones de este almacén: elige de cuál se fracciona.",
                422,
                ['locations' => $this->locationsPayload($rows)]
            );
        }
        $resolvedLocation = $row->warehouse_location_id;

        if (!$batch && $this->hasActiveBatches($sourceId, $warehouseId, $resolvedLocation)) {
            throw new FractionationException(
                'batch_required',
                'Lote requerido',
                'El producto origen maneja lotes en este almacén: elige el lote que se fracciona.'
            );
        }
        if ($batch) {
            $this->assertBatchUsable($batch, $resolvedLocation, $plan['consumed']);
        }

        // 5. Disponible (descuenta lo reservado)
        $available = round((float) $row->quantity - (float) $row->reserved_quantity, 4);
        if ($available + self::EPSILON < $plan['consumed']) {
            throw new FractionationException(
                'insufficient_stock',
                'Stock insuficiente',
                "Stock insuficiente. Disponible: {$this->fmt($available)} (existencia {$this->fmt((float) $row->quantity)}, "
                    . "reservado {$this->fmt((float) $row->reserved_quantity)}); requerido: {$this->fmt($plan['consumed'])}.",
                422,
                ['available' => $available, 'required' => $plan['consumed']]
            );
        }

        // 6. Costos (el mismo criterio que createExit: costo del stock si es real)
        $sourceProduct = Product::find($sourceId);
        $fallbackCost = $this->resolveSourceUnitCost(null, $batch, $sourceProduct);
        $sourceUnitCost = (float) $row->unit_cost > 0 ? (float) $row->unit_cost : $fallbackCost;
        $totalCost = round($plan['consumed'] * $sourceUnitCost, 4);
        $destinationUnitCost = $plan['produced'] > 0 ? round($totalCost / $plan['produced'], 6) : 0.0;

        // 7. Folio
        $folioNumber = FolioSequence::getNextFolio('fractionation');

        // 8. Lote destino (solo si se fracciona un lote)
        $destinationBatch = $batch
            ? $this->createDestinationBatch($batch, $destinationId, $warehouseId, $resolvedLocation ?? $batch->warehouse_location_id, $folioNumber, $plan['produced'], $destinationUnitCost)
            : null;

        $movementKey = $key ? "fractionation:{$key}" : null;

        // 9. Salida del origen
        $exitMovement = $this->movementService->createExit([
            'product_id' => $sourceId,
            'warehouse_id' => $warehouseId,
            'warehouse_location_id' => $resolvedLocation,
            'product_batch_id' => $batch?->id,
            'destination_batch_id' => $destinationBatch?->id,
            'quantity' => $plan['consumed'],
            'unit_cost' => $sourceUnitCost,
            'user_id' => $data['user_id'],
            'reference_type' => self::REFERENCE_TYPE,
            'idempotency_key' => $movementKey ? "{$movementKey}:exit" : null,
            'description' => "Fraccionamiento {$folioNumber}: salida de producto origen",
            'quality_checked' => true,
            'quality_checked_by' => $data['user_id'],
            'metadata' => array_filter([
                'fractionation_folio' => $folioNumber,
                'fractionation_type' => 'exit',
                'source_batch_number' => $batch?->batch_number,
            ]),
        ]);

        // 10. Entrada del destino (promedio ponderado solo aqui)
        $entryMovement = $this->movementService->createEntry([
            'product_id' => $destinationId,
            'warehouse_id' => $warehouseId,
            'warehouse_location_id' => $resolvedLocation,
            'product_batch_id' => $destinationBatch?->id,
            'quantity' => $plan['produced'],
            'unit_cost' => round($destinationUnitCost, 4),
            'stock_cost_policy' => 'weighted_average',
            'user_id' => $data['user_id'],
            'reference_type' => self::REFERENCE_TYPE,
            'idempotency_key' => $movementKey ? "{$movementKey}:entry" : null,
            'description' => "Fraccionamiento {$folioNumber}: entrada de producto destino",
            'metadata' => array_filter([
                'fractionation_folio' => $folioNumber,
                'fractionation_type' => 'entry',
                'source_batch_id' => $batch?->id,
                'source_batch_number' => $batch?->batch_number,
            ]),
        ]);

        // 11. Descontar el lote origen
        if ($batch) {
            $remaining = round((float) $batch->current_quantity - $plan['consumed'], 4);
            $batch->current_quantity = max(0, $remaining);
            if ($batch->current_quantity <= self::EPSILON) {
                $batch->current_quantity = 0;
                $batch->status = 'consumed';
            }
            $batch->save();
        }

        // 12. Registro
        $fractionation = Fractionation::create([
            'folio_number' => $folioNumber,
            'idempotency_key' => $key,
            'source_product_id' => $sourceId,
            'destination_product_id' => $destinationId,
            'product_conversion_id' => $conversion->id,
            'warehouse_id' => $warehouseId,
            'warehouse_location_id' => $resolvedLocation,
            'source_batch_id' => $batch?->id,
            'destination_batch_id' => $destinationBatch?->id,
            'user_id' => $data['user_id'],
            'source_quantity' => $plan['consumed'],
            'requested_quantity' => $plan['requested'],
            'produced_quantity' => $plan['produced'],
            'waste_percentage' => $conversion->waste_percentage,
            'waste_quantity' => $plan['waste'],
            'conversion_factor_used' => $conversion->conversion_factor,
            'source_unit_cost' => round($sourceUnitCost, 6),
            'destination_unit_cost' => $destinationUnitCost,
            'total_cost' => $totalCost,
            'exit_movement_id' => $exitMovement->id,
            'entry_movement_id' => $entryMovement->id,
            'status' => Fractionation::STATUS_COMPLETED,
            'notes' => $data['notes'] ?? null,
            'executed_at' => now(),
        ]);

        // Trazabilidad: los movimientos apuntan al fraccionamiento (query builder,
        // sin eventos: el GL ya se resolvio al crearlos).
        InventoryMovement::whereKey([$exitMovement->id, $entryMovement->id])
            ->update(['reference_id' => $fractionation->id]);
        if ($destinationBatch) {
            ProductBatch::whereKey($destinationBatch->id)->update([
                'metadata' => json_encode(array_merge($destinationBatch->metadata ?? [], [
                    'fractionation_id' => $fractionation->id,
                ])),
            ]);
        }

        Log::info('Fractionation executed', [
            'folio' => $folioNumber,
            'source_product_id' => $sourceId,
            'destination_product_id' => $destinationId,
            'requested_quantity' => $plan['requested'],
            'consumed_quantity' => $plan['consumed'],
            'produced_quantity' => $plan['produced'],
            'waste_quantity' => $plan['waste'],
            'warehouse_id' => $warehouseId,
            'warehouse_location_id' => $resolvedLocation,
            'source_batch_id' => $batch?->id,
            'destination_batch_id' => $destinationBatch?->id,
            'destination_unit_cost' => $destinationUnitCost,
        ]);

        return [
            'fractionation' => $this->loadForResponse($fractionation),
            'replayed' => false,
            'warnings' => $this->warnings($plan, $sourceUnitCost, $conversion),
        ];
    }

    /**
     * Busca un fraccionamiento por clave. $lock = lectura bloqueante, que en
     * InnoDB ve lo confirmado por otra transaccion aunque esta ya tenga snapshot.
     */
    protected function findByIdempotencyKey(string $key, bool $lock): ?Fractionation
    {
        return Fractionation::where('idempotency_key', $key)
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->first();
    }

    /**
     * Respuesta para una clave ya usada. Si los datos no coinciden con los del
     * fraccionamiento original, es un error del cliente (clave reutilizada).
     *
     * @param array<string, mixed> $data
     * @return array{fractionation: Fractionation, replayed: bool, warnings: string[]}
     */
    protected function replay(Fractionation $existing, array $data): array
    {
        $requested = $existing->requested_quantity ?? $existing->source_quantity;
        $same = (int) $existing->source_product_id === (int) $data['source_product_id']
            && (int) $existing->destination_product_id === (int) $data['destination_product_id']
            && (int) $existing->warehouse_id === (int) $data['warehouse_id']
            && abs((float) $requested - (float) $data['source_quantity']) <= self::EPSILON;

        if (!$same) {
            throw new FractionationException(
                'idempotency_conflict',
                'Clave ya usada',
                "La clave de esta operación ya se usó en el fraccionamiento {$existing->folio_number} con otros datos. Recarga el formulario para generar una nueva.",
                409,
                ['fractionation_id' => $existing->id, 'folio_number' => $existing->folio_number]
            );
        }

        return [
            'fractionation' => $this->loadForResponse($existing),
            'replayed' => true,
            'warnings' => [],
        ];
    }

    private function loadForResponse(Fractionation $fractionation): Fractionation
    {
        return $fractionation->load([
            'sourceProduct', 'destinationProduct', 'warehouse',
            'user', 'exitMovement', 'entryMovement', 'sourceBatch', 'destinationBatch',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function findConversion(array $data, bool $withUnits): ProductConversion
    {
        $conversion = ProductConversion::active()
            ->where('source_product_id', $data['source_product_id'])
            ->where('destination_product_id', $data['destination_product_id'])
            ->with($withUnits ? ['sourceProduct.unit', 'destinationProduct.unit'] : [])
            ->first();

        if (!$conversion) {
            throw new FractionationException(
                'conversion_not_found',
                'Conversión no configurada',
                'No hay una conversión activa entre el producto origen y el producto destino.'
            );
        }

        return $conversion;
    }

    /**
     * @return array{requested: float, consumed: float, produced: float, waste: float, leftover: float}
     */
    private function plan(ProductConversion $conversion, float $requested): array
    {
        try {
            return $conversion->planProduction($requested);
        } catch (\InvalidArgumentException $e) {
            throw new FractionationException('insufficient_for_unit', 'Cantidad insuficiente', $e->getMessage());
        }
    }

    /**
     * @return Collection<int, Stock>
     */
    private function stockRows(int $productId, int $warehouseId, ?int $locationId, bool $lock): Collection
    {
        return Stock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->when($locationId !== null, fn ($q) => $q->where('warehouse_location_id', $locationId))
            ->with('location')
            ->orderBy('id')
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->get();
    }

    /**
     * Fila de stock a usar: la unica, la de la ubicacion pedida o la del lote.
     * null = hay varias y no se puede decidir.
     *
     * @param Collection<int, Stock> $rows
     */
    private function pickStockRow(Collection $rows, ?int $locationId, ?ProductBatch $batch): ?Stock
    {
        if ($rows->count() === 1) {
            return $rows->first();
        }
        if ($rows->isEmpty()) {
            return null;
        }
        if ($locationId === null && $batch?->warehouse_location_id) {
            return $rows->firstWhere('warehouse_location_id', $batch->warehouse_location_id);
        }

        return null;
    }

    /**
     * Lotes activos con disponible, en orden FEFO (mismo criterio que
     * LotTraceabilityService::selectBatchesFEFO).
     *
     * @return Collection<int, ProductBatch>
     */
    private function fefoBatches(int $productId, int $warehouseId, ?int $locationId): Collection
    {
        return $this->batchScope($productId, $warehouseId, $locationId)
            ->whereRaw('current_quantity - reserved_quantity > 0')
            ->orderByRaw(DBHelper::coalesceDate('expiration_date', '9999-12-31') . ' ASC')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    private function hasActiveBatches(int $productId, int $warehouseId, ?int $locationId): bool
    {
        return $this->batchScope($productId, $warehouseId, $locationId)
            ->where('current_quantity', '>', 0)
            ->exists();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<ProductBatch>
     */
    private function batchScope(int $productId, int $warehouseId, ?int $locationId)
    {
        return ProductBatch::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'active')
            ->when($locationId !== null, fn ($q) => $q->where(function ($w) use ($locationId) {
                $w->where('warehouse_location_id', $locationId)->orWhereNull('warehouse_location_id');
            }));
    }

    /**
     * FEFO: el primero sin caducar que alcance; si ninguno alcanza, el primero
     * sin caducar (la vista previa avisa que no alcanza).
     *
     * @param Collection<int, ProductBatch> $batches
     */
    private function suggestBatch(Collection $batches, float $required): ?ProductBatch
    {
        // D10: caducados, en cuarentena o rechazados por calidad nunca se sugieren.
        $usable = $batches->filter(fn (ProductBatch $b) => $this->unavailableReason($b) === null);

        return $usable->first(fn (ProductBatch $b) => $this->batchAvailable($b) + self::EPSILON >= $required)
            ?? $usable->first();
    }

    private function assertBatchBelongs(ProductBatch $batch, int $productId, int $warehouseId): void
    {
        if ((int) $batch->product_id !== $productId) {
            throw new FractionationException(
                'batch_product_mismatch',
                'Lote de otro producto',
                "El lote {$batch->batch_number} no pertenece al producto origen."
            );
        }
        if ((int) $batch->warehouse_id !== $warehouseId) {
            throw new FractionationException(
                'batch_warehouse_mismatch',
                'Lote de otro almacén',
                "El lote {$batch->batch_number} está en otro almacén."
            );
        }
    }

    private function assertBatchUsable(ProductBatch $batch, ?int $locationId, float $required): void
    {
        if ($batch->warehouse_location_id && $locationId && (int) $batch->warehouse_location_id !== $locationId) {
            throw new FractionationException(
                'batch_location_mismatch',
                'Lote en otra ubicación',
                "El lote {$batch->batch_number} está en otra ubicación del almacén."
            );
        }
        if ($batch->status !== 'active') {
            throw new FractionationException(
                'batch_not_active',
                'Lote no disponible',
                "El lote {$batch->batch_number} no está activo (estado: {$batch->status})."
            );
        }
        // D10 (Gabino 2026-10-10): lotes caducados o en cuarentena no se
        // fraccionan (criterio de medicamento y materia prima).
        if (in_array($batch->quality_status, ['failed', 'quarantine'], true)) {
            throw new FractionationException(
                'batch_quality_blocked',
                'Lote bloqueado por calidad',
                "No se puede fraccionar el lote {$batch->batch_number}: {$this->unavailableReason($batch)}."
            );
        }
        if ($this->isExpired($batch)) {
            throw new FractionationException(
                'batch_expired',
                'Lote caducado',
                "No se puede fraccionar el lote {$batch->batch_number}: {$this->unavailableReason($batch)}."
            );
        }
        $available = $this->batchAvailable($batch);
        if ($available + self::EPSILON < $required) {
            throw new FractionationException(
                'batch_insufficient',
                'Lote insuficiente',
                "El lote {$batch->batch_number} tiene disponible {$this->fmt($available)}; requerido: {$this->fmt($required)}.",
                422,
                ['available' => $available, 'required' => $required]
            );
        }
    }

    private function createDestinationBatch(
        ProductBatch $source,
        int $destinationProductId,
        int $warehouseId,
        ?int $locationId,
        string $folioNumber,
        float $produced,
        float $unitCost
    ): ProductBatch {
        $batchNumber = $folioNumber;
        $suffix = 1;
        while (ProductBatch::where('batch_number', $batchNumber)->exists()) {
            $suffix++;
            $batchNumber = "{$folioNumber}-{$suffix}";
        }

        return ProductBatch::create([
            'product_id' => $destinationProductId,
            'warehouse_id' => $warehouseId,
            'warehouse_location_id' => $locationId,
            'batch_number' => $batchNumber,
            'lot_number' => $source->lot_number,
            'manufacturing_date' => $source->manufacturing_date,
            'expiration_date' => $source->expiration_date,
            'best_before_date' => $source->best_before_date,
            'initial_quantity' => $produced,
            'current_quantity' => $produced,
            'reserved_quantity' => 0,
            'unit_cost' => round($unitCost, 4),
            'status' => 'active',
            'quality_status' => $source->quality_status ?? 'pending',
            'supplier_name' => $source->supplier_name,
            'supplier_batch' => $source->supplier_batch,
            'metadata' => [
                'origin' => 'fractionation',
                'fractionation_folio' => $folioNumber,
                'source_batch_id' => $source->id,
                'source_batch_number' => $source->batch_number,
                'source_product_id' => $source->product_id,
            ],
        ]);
    }

    /**
     * Costo unitario del origen: stock (si es real) > lote > costo del producto > 0.
     */
    private function resolveSourceUnitCost(?Stock $stock, ?ProductBatch $batch, ?Product $product): float
    {
        foreach ([$stock?->unit_cost, $batch?->unit_cost, $product?->cost] as $candidate) {
            if ($candidate !== null && (float) $candidate > 0) {
                return (float) $candidate;
            }
        }

        return 0.0;
    }

    private function batchAvailable(ProductBatch $batch): float
    {
        return round((float) $batch->current_quantity - (float) $batch->reserved_quantity, 4);
    }

    private function isExpired(ProductBatch $batch): bool
    {
        return $batch->expiration_date !== null && $batch->expiration_date->lt(now()->startOfDay());
    }

    /**
     * Motivo legible (en espanol) por el que un lote no se puede fraccionar,
     * o null si se puede. Lo usan la sugerencia FEFO, la vista previa (para
     * que el formulario explique por que un lote esta deshabilitado) y los
     * errores de execute.
     */
    private function unavailableReason(ProductBatch $batch): ?string
    {
        if ($batch->status !== 'active') {
            return "el lote no está activo (estado: {$batch->status})";
        }
        if ($batch->quality_status === 'quarantine') {
            return 'el lote está en cuarentena';
        }
        if ($batch->quality_status === 'failed') {
            return 'el lote no pasó el control de calidad';
        }
        if ($this->isExpired($batch)) {
            return 'el lote caducó el ' . $batch->expiration_date->format('d/m/Y');
        }

        return null;
    }

    /**
     * @param array{requested: float, consumed: float, produced: float, waste: float, leftover: float} $plan
     * @return string[]
     */
    private function warnings(array $plan, float $sourceUnitCost, ProductConversion $conversion): array
    {
        $warnings = [];
        if ($sourceUnitCost <= 0) {
            $warnings[] = 'El producto origen no tiene costo: las presentaciones entrarán con costo 0. Captura el costo del granel para valuar el inventario.';
        }
        if ($conversion->whole_units && $plan['leftover'] > 0) {
            $warnings[] = "Solo piezas completas: salen {$this->fmt($plan['consumed'])} de {$this->fmt($plan['requested'])}; "
                . "el sobrante ({$this->fmt($plan['leftover'])}) se queda en el granel.";
        }

        return $warnings;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function productPayload(?Product $product): ?array
    {
        if (!$product) {
            return null;
        }

        $unit = $product->unit;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'unit' => $unit ? [
                'name' => $unit->getAttribute('name'),
                'code' => $unit->getAttribute('code'),
            ] : null,
        ];
    }

    /**
     * @param Collection<int, Stock> $rows
     * @return array<int, array<string, mixed>>
     */
    private function locationsPayload(Collection $rows): array
    {
        return $rows->map(fn (Stock $s) => [
            'warehouse_location_id' => $s->warehouse_location_id,
            'name' => $s->location?->name,
            'code' => $s->location?->code,
            'quantity' => round((float) $s->quantity, 4),
            'available' => round((float) $s->quantity - (float) $s->reserved_quantity, 4),
        ])->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function batchPayload(ProductBatch $batch): array
    {
        return [
            'id' => $batch->id,
            'batch_number' => $batch->batch_number,
            'lot_number' => $batch->lot_number,
            'expiration_date' => $batch->expiration_date?->format('Y-m-d'),
            'warehouse_location_id' => $batch->warehouse_location_id,
            'available_quantity' => $this->batchAvailable($batch),
            'unit_cost' => (float) $batch->unit_cost,
            'is_expired' => $this->isExpired($batch),
            'is_expiring_soon' => $batch->isExpiringSoon(),
            'quality_status' => $batch->quality_status,
            // D10: el formulario deshabilita el lote y muestra este motivo.
            'is_usable' => $this->unavailableReason($batch) === null,
            'unavailable_reason' => $this->unavailableReason($batch),
        ];
    }

    private function fmt(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ','), '0'), '.');
    }
}
