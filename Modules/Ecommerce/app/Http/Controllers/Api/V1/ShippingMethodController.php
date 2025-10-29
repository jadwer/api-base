<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Ecommerce\Models\ShippingMethod;

class ShippingMethodController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * List available shipping methods
     *
     * GET /api/v1/shipping-methods
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $methods = ShippingMethod::active()
            ->orderBy('base_cost')
            ->get();

        // Filter by country if provided
        if ($request->has('country')) {
            $country = $request->input('country');
            $methods = $methods->filter(function ($method) use ($country) {
                return $method->isAvailableInCountry($country);
            });
        }

        return response()->json([
            'data' => $methods->values(),
        ]);
    }

    /**
     * Get single shipping method
     *
     * GET /api/v1/shipping-methods/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $method = ShippingMethod::active()->findOrFail($id);

        return response()->json([
            'data' => $method,
        ]);
    }

    /**
     * Calculate shipping cost for specific weight
     *
     * POST /api/v1/shipping-methods/{id}/calculate
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function calculate(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'weight' => 'required|numeric|min:0',
        ]);

        $method = ShippingMethod::active()->findOrFail($id);
        $cost = $method->calculateShippingCost($request->weight);

        return response()->json([
            'data' => [
                'shipping_method' => $method,
                'weight' => $request->weight,
                'cost' => $cost,
                'currency' => 'MXN',
                'estimated_delivery' => $method->estimated_delivery,
            ],
        ]);
    }
}
