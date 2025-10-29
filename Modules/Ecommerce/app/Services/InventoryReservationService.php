<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Ecommerce\Models\InventoryReservation;
use Modules\Inventory\Models\Stock;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryReservationService
{
    /**
     * Reserve inventory items for checkout session
     *
     * @param CheckoutSession $session
     * @return array
     */
    public function reserveItems(CheckoutSession $session): array
    {
        $reservations = [];
        $cart = $session->shoppingCart;

        // Check if already reserved
        if ($session->inventoryReservations()->active()->exists()) {
            return [
                'status' => 'already_reserved',
                'reservations' => $session->inventoryReservations,
            ];
        }

        DB::transaction(function () use ($session, $cart, &$reservations) {
            foreach ($cart->cartItems as $item) {
                // Find available stock
                $stock = Stock::where('product_id', $item->product_id)
                    ->where('quantity_on_hand', '>=', $item->quantity)
                    ->lockForUpdate() // Pessimistic locking to prevent race conditions
                    ->first();

                if (!$stock) {
                    throw new \Exception("Insufficient stock for product: {$item->product->name}");
                }

                // Create reservation
                $reservation = InventoryReservation::create([
                    'checkout_session_id' => $session->id,
                    'stock_id' => $stock->id,
                    'product_id' => $item->product_id,
                    'warehouse_id' => $stock->warehouse_id,
                    'quantity_reserved' => $item->quantity,
                    'status' => 'active',
                    'expires_at' => now()->addMinutes(30), // Same as checkout session
                    'notes' => "Reserved for checkout session #{$session->id}",
                ]);

                // Update stock (reduce available)
                $stock->decrement('quantity_on_hand', $item->quantity);
                $stock->increment('quantity_reserved', $item->quantity);

                $reservations[] = $reservation;
            }
        });

        return [
            'status' => 'success',
            'reservations' => $reservations,
        ];
    }

    /**
     * Release a single reservation
     *
     * @param InventoryReservation $reservation
     * @param string|null $reason
     * @return void
     */
    public function releaseReservation(InventoryReservation $reservation, ?string $reason = null): void
    {
        if (!$reservation->canBeReleased()) {
            return;
        }

        DB::transaction(function () use ($reservation, $reason) {
            // Update stock (restore available quantity)
            $stock = $reservation->stock;
            $stock->increment('quantity_on_hand', $reservation->quantity_reserved);
            $stock->decrement('quantity_reserved', $reservation->quantity_reserved);

            // Mark reservation as released
            $reservation->release($reason);
        });
    }

    /**
     * Release all reservations for a checkout session
     *
     * @param CheckoutSession $session
     * @param string|null $reason
     * @return int
     */
    public function releaseSessionReservations(CheckoutSession $session, ?string $reason = null): int
    {
        $releasedCount = 0;

        foreach ($session->inventoryReservations()->active()->get() as $reservation) {
            $this->releaseReservation($reservation, $reason ?? 'Session cancelled or expired');
            $releasedCount++;
        }

        return $releasedCount;
    }

    /**
     * Fulfill a reservation (convert to actual inventory movement)
     *
     * @param InventoryReservation $reservation
     * @return void
     */
    public function fulfillReservation(InventoryReservation $reservation): void
    {
        if (!$reservation->canBeFulfilled()) {
            throw new \Exception('Reservation cannot be fulfilled in current state');
        }

        DB::transaction(function () use ($reservation) {
            $stock = $reservation->stock;

            // Update stock (remove from reserved)
            $stock->decrement('quantity_reserved', $reservation->quantity_reserved);

            // Note: The quantity_on_hand was already decremented when reservation was created
            // So we don't need to decrement it again here

            // Mark reservation as fulfilled
            $reservation->fulfill();
        });
    }

    /**
     * Cleanup expired reservations (background job)
     *
     * @return int Number of reservations cleaned up
     */
    public function cleanupExpiredReservations(): int
    {
        $expiredReservations = InventoryReservation::expired()->get();
        $cleanedCount = 0;

        foreach ($expiredReservations as $reservation) {
            try {
                $this->releaseReservation($reservation, 'Automatically released due to expiration');
                $reservation->expire();
                $cleanedCount++;
            } catch (\Exception $e) {
                // Log error but continue with other reservations
                logger()->error('Failed to cleanup expired reservation', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $cleanedCount;
    }

    /**
     * Check if product has sufficient available stock
     *
     * @param int $productId
     * @param float $quantity
     * @param int|null $warehouseId
     * @return bool
     */
    public function hasAvailableStock(int $productId, float $quantity, ?int $warehouseId = null): bool
    {
        $query = Stock::where('product_id', $productId)
            ->where('quantity_on_hand', '>=', $quantity);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->exists();
    }

    /**
     * Get available stock quantity for a product
     *
     * @param int $productId
     * @param int|null $warehouseId
     * @return float
     */
    public function getAvailableStock(int $productId, ?int $warehouseId = null): float
    {
        $query = Stock::where('product_id', $productId);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->sum('quantity_on_hand');
    }

    /**
     * Get total reserved quantity for a product
     *
     * @param int $productId
     * @param int|null $warehouseId
     * @return float
     */
    public function getReservedQuantity(int $productId, ?int $warehouseId = null): float
    {
        $query = InventoryReservation::active()
            ->where('product_id', $productId);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->sum('quantity_reserved');
    }

    /**
     * Extend reservation expiration time
     *
     * @param InventoryReservation $reservation
     * @param int $additionalMinutes
     * @return void
     */
    public function extendReservation(InventoryReservation $reservation, int $additionalMinutes = 15): void
    {
        if (!$reservation->getIsActiveAttribute()) {
            throw new \Exception('Cannot extend inactive or expired reservation');
        }

        $newExpirationTime = $reservation->expires_at->addMinutes($additionalMinutes);

        $reservation->update([
            'expires_at' => $newExpirationTime,
        ]);
    }
}
