<?php

namespace Modules\Inventory\Console\Commands;

use Illuminate\Console\Command;
use Modules\Inventory\Models\Stock;
use Illuminate\Support\Facades\Log;

/**
 * Check Stock Reorder Alerts Command
 *
 * Identifies products that have fallen below their reorder point and logs alerts.
 * This command should be scheduled to run daily for proactive inventory management.
 *
 * IV-M002: Stock Reorder Alerts Implementation (P2)
 *
 * Usage:
 *   php artisan inventory:check-reorder-alerts
 *   php artisan inventory:check-reorder-alerts -v
 */
class CheckStockReorderAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:check-reorder-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for stock items below reorder point and create alerts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Checking stock reorder alerts...');
        $this->newLine();

        // Find stock items below reorder point with active status
        $lowStockItems = Stock::where('status', 'active')
            ->where('available_quantity', '<=', \DB::raw('reorder_point'))
            ->where('reorder_point', '>', 0) // Only check items with reorder point set
            ->with(['product', 'warehouse', 'location'])
            ->get();

        if ($lowStockItems->isEmpty()) {
            $this->line('  ✅ No stock items require reordering');
            return 0;
        }

        $this->warn("  ⚠️  Found {$lowStockItems->count()} stock item(s) below reorder point");

        if ($this->getOutput()->isVerbose()) {
            $this->newLine();
            $this->table(
                ['Product', 'SKU', 'Warehouse', 'Location', 'Available', 'Reorder Point', 'Status'],
                $lowStockItems->map(function ($stock) {
                    return [
                        $stock->product->name ?? 'N/A',
                        $stock->product->sku ?? 'N/A',
                        $stock->warehouse->name ?? 'N/A',
                        $stock->location->name ?? '-',
                        number_format($stock->available_quantity, 2),
                        number_format($stock->reorder_point, 2),
                        $this->getStockStatus($stock),
                    ];
                })
            );
            $this->newLine();
        }

        // Log alerts for each low stock item
        foreach ($lowStockItems as $stock) {
            $deficit = $stock->reorder_point - $stock->available_quantity;
            $percentageBelow = ($deficit / $stock->reorder_point) * 100;

            Log::warning('Stock reorder alert', [
                'product_id' => $stock->product_id,
                'product_name' => $stock->product->name ?? 'Unknown',
                'product_sku' => $stock->product->sku ?? 'Unknown',
                'warehouse_id' => $stock->warehouse_id,
                'warehouse_name' => $stock->warehouse->name ?? 'Unknown',
                'location_id' => $stock->warehouse_location_id,
                'location_name' => $stock->location->name ?? null,
                'available_quantity' => $stock->available_quantity,
                'reorder_point' => $stock->reorder_point,
                'deficit' => $deficit,
                'percentage_below' => round($percentageBelow, 2),
                'suggested_order_quantity' => $this->calculateSuggestedOrderQuantity($stock),
            ]);

            if ($this->getOutput()->isVerbose()) {
                $this->line(sprintf(
                    "  📦 %s (SKU: %s) in %s: Available %.2f / Reorder Point %.2f (%.1f%% below)",
                    $stock->product->name ?? 'Unknown',
                    $stock->product->sku ?? 'N/A',
                    $stock->warehouse->name ?? 'Unknown',
                    $stock->available_quantity,
                    $stock->reorder_point,
                    $percentageBelow
                ));
            }
        }

        $this->newLine();
        $this->info("✅ Logged {$lowStockItems->count()} reorder alert(s)");

        return 0;
    }

    /**
     * Get stock status indicator
     *
     * @param Stock $stock
     * @return string
     */
    private function getStockStatus(Stock $stock): string
    {
        $percentageAvailable = ($stock->available_quantity / $stock->reorder_point) * 100;

        if ($percentageAvailable <= 0) {
            return '🔴 Critical';
        } elseif ($percentageAvailable <= 50) {
            return '🟠 Low';
        } else {
            return '🟡 Near Reorder';
        }
    }

    /**
     * Calculate suggested order quantity
     *
     * IV-M002: Suggest order quantity based on maximum stock or a default multiplier
     *
     * @param Stock $stock
     * @return float
     */
    private function calculateSuggestedOrderQuantity(Stock $stock): float
    {
        $deficit = $stock->reorder_point - $stock->available_quantity;

        // If maximum_stock is set, order up to maximum
        if ($stock->maximum_stock && $stock->maximum_stock > $stock->available_quantity) {
            return $stock->maximum_stock - $stock->available_quantity;
        }

        // Otherwise, use multiplier from config (default 2x reorder point)
        $multiplier = config('inventory.reorder_quantity_multiplier', 2);
        $targetQuantity = $stock->reorder_point * $multiplier;

        return max($deficit, $targetQuantity - $stock->available_quantity);
    }
}
