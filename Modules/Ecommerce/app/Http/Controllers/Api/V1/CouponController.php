<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Ecommerce\Models\Coupon;

class CouponController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\UpdateRelationship;
    use Actions\AttachRelationship;
    use Actions\DetachRelationship;

    /**
     * Validate a coupon code without applying it
     * GET /api/v1/coupons/validate/{code}
     */
    public function validate(string $code): JsonResponse
    {
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return response()->json([
                'valid' => false,
                'error' => 'Coupon not found'
            ], 404);
        }

        if (!$coupon->is_active) {
            return response()->json([
                'valid' => false,
                'error' => 'Coupon is inactive'
            ], 400);
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return response()->json([
                'valid' => false,
                'error' => 'Coupon is not yet active'
            ], 400);
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return response()->json([
                'valid' => false,
                'error' => 'Coupon has expired'
            ], 400);
        }

        if ($coupon->max_uses && $coupon->used_count >= $coupon->max_uses) {
            return response()->json([
                'valid' => false,
                'error' => 'Coupon usage limit reached'
            ], 400);
        }

        return response()->json([
            'valid' => true,
            'data' => [
                'type' => 'coupons',
                'id' => (string) $coupon->id,
                'attributes' => [
                    'code' => $coupon->code,
                    'name' => $coupon->name,
                    'couponType' => $coupon->type,
                    'value' => $coupon->value,
                    'minAmount' => $coupon->min_amount,
                    'maxAmount' => $coupon->max_amount,
                    'startsAt' => $coupon->starts_at?->toISOString(),
                    'expiresAt' => $coupon->expires_at?->toISOString(),
                ]
            ]
        ]);
    }
}
