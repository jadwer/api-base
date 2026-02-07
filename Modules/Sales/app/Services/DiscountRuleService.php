<?php

namespace Modules\Sales\Services;

use Modules\Sales\Models\DiscountRule;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Illuminate\Support\Collection;

/**
 * SA-M003: Discount Rule Service
 *
 * Handles automatic discount application to orders and order items.
 */
class DiscountRuleService
{
    /**
     * Get all applicable discount rules for an order.
     */
    public function getApplicableRules(SalesOrder $order): Collection
    {
        $rules = DiscountRule::valid()
            ->byPriority()
            ->get();

        return $rules->filter(function (DiscountRule $rule) use ($order) {
            return $rule->canBeUsedBy($order->contact_id);
        });
    }

    /**
     * Calculate and apply discounts to an order.
     *
     * Returns an array with:
     * - applied_rules: Collection of applied discount rules
     * - order_discount: Total discount for the order
     * - item_discounts: Array of item-level discounts
     */
    public function applyDiscounts(SalesOrder $order): array
    {
        $applicableRules = $this->getApplicableRules($order);
        $appliedRules = collect();
        $orderDiscount = 0.0;
        $itemDiscounts = [];

        // First, apply order-level discounts
        $orderRules = $applicableRules->filter(fn ($r) => $r->applies_to === DiscountRule::APPLIES_TO_ORDER);
        $nonCombinableApplied = false;

        foreach ($orderRules as $rule) {
            // If a non-combinable rule was already applied, stop
            if ($nonCombinableApplied) {
                break;
            }
            // If this rule is non-combinable but others already applied, skip it
            if ($appliedRules->isNotEmpty() && !$rule->is_combinable) {
                continue;
            }

            $discount = $rule->calculateDiscount($order->subtotal, $order->items->sum('quantity'));

            if ($discount > 0) {
                $orderDiscount += $discount;
                $appliedRules->push([
                    'rule' => $rule,
                    'discount' => $discount,
                    'type' => 'order',
                ]);

                if (!$rule->is_combinable) {
                    $nonCombinableApplied = true;
                }
            }
        }

        // Then, apply product/category-level discounts
        $productRules = $applicableRules->filter(fn ($r) => in_array($r->applies_to, [
            DiscountRule::APPLIES_TO_PRODUCT,
            DiscountRule::APPLIES_TO_CATEGORY,
        ]));

        foreach ($order->items as $item) {
            $itemDiscounts[$item->id] = 0.0;

            foreach ($productRules as $rule) {
                if (!$rule->appliesToProduct($item->product)) {
                    continue;
                }

                $discount = $rule->calculateDiscount($item->total, $item->quantity);

                if ($discount > 0) {
                    $itemDiscounts[$item->id] += $discount;
                    $appliedRules->push([
                        'rule' => $rule,
                        'discount' => $discount,
                        'type' => 'item',
                        'item_id' => $item->id,
                    ]);

                    if (!$rule->is_combinable) {
                        break;
                    }
                }
            }
        }

        // Cap total discount to not exceed order subtotal
        $totalDiscount = min($orderDiscount + array_sum($itemDiscounts), $order->subtotal);
        $orderDiscount = min($orderDiscount, $order->subtotal);

        return [
            'applied_rules' => $appliedRules,
            'order_discount' => $orderDiscount,
            'item_discounts' => $itemDiscounts,
            'total_discount' => $totalDiscount,
        ];
    }

    /**
     * Simulate discount calculation without applying.
     */
    public function simulateDiscounts(float $subtotal, int $quantity, ?int $customerId = null): array
    {
        $rules = DiscountRule::valid()
            ->forOrder()
            ->byPriority()
            ->get()
            ->filter(fn ($rule) => $rule->canBeUsedBy($customerId));

        $discounts = [];

        foreach ($rules as $rule) {
            $discount = $rule->calculateDiscount($subtotal, $quantity);

            if ($discount > 0) {
                $discounts[] = [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'rule_code' => $rule->code,
                    'discount_type' => $rule->discount_type,
                    'discount_value' => $rule->discount_value,
                    'calculated_discount' => $discount,
                    'is_combinable' => $rule->is_combinable,
                ];

                if (!$rule->is_combinable) {
                    break;
                }
            }
        }

        return $discounts;
    }

    /**
     * Mark discount rules as used after order completion.
     */
    public function markRulesAsUsed(Collection $appliedRules): void
    {
        $ruleIds = $appliedRules->pluck('rule')->pluck('id')->unique();

        foreach ($ruleIds as $ruleId) {
            $rule = DiscountRule::find($ruleId);
            if ($rule) {
                $rule->incrementUsage();
            }
        }
    }

    /**
     * Find the best single discount for an order.
     */
    public function findBestDiscount(float $subtotal, int $quantity, ?int $customerId = null): ?array
    {
        $discounts = $this->simulateDiscounts($subtotal, $quantity, $customerId);

        if (empty($discounts)) {
            return null;
        }

        // Return the discount with the highest value
        return collect($discounts)->sortByDesc('calculated_discount')->first();
    }

    /**
     * Validate if a discount code is valid and applicable.
     */
    public function validateDiscountCode(string $code, float $subtotal, ?int $customerId = null): array
    {
        $rule = DiscountRule::where('code', $code)->first();

        if (!$rule) {
            return [
                'valid' => false,
                'error' => 'Código de descuento no encontrado.',
            ];
        }

        if (!$rule->is_valid) {
            if (!$rule->is_active) {
                return ['valid' => false, 'error' => 'El código de descuento está desactivado.'];
            }
            if ($rule->is_expired) {
                return ['valid' => false, 'error' => 'El código de descuento ha expirado.'];
            }
            if ($rule->usage_remaining === 0) {
                return ['valid' => false, 'error' => 'El código de descuento ha alcanzado su límite de uso.'];
            }
            return ['valid' => false, 'error' => 'El código de descuento no es válido.'];
        }

        if (!$rule->canBeUsedBy($customerId)) {
            return [
                'valid' => false,
                'error' => 'No puedes usar este código de descuento.',
            ];
        }

        if ($rule->min_order_amount && $subtotal < $rule->min_order_amount) {
            return [
                'valid' => false,
                'error' => "El pedido mínimo para este descuento es \${$rule->min_order_amount}.",
            ];
        }

        $discount = $rule->calculateDiscount($subtotal);

        return [
            'valid' => true,
            'rule' => $rule,
            'discount' => $discount,
            'message' => "Descuento aplicado: \${$discount}",
        ];
    }
}
