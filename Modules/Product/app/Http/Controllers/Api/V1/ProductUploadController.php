<?php

namespace Modules\Product\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductUploadController extends Controller
{
    /**
     * Upload product image.
     *
     * POST /api/v1/products/upload-image
     *
     * Requires permission: products.store OR products.update
     * (same permissions as creating/editing a product)
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // User must have permission to create or update products to upload images
        if (!$user->can('products.store') && !$user->can('products.update')) {
            return response()->json(['error' => 'Forbidden - requires products.store or products.update permission'], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,webp|max:10240', // 10MB
        ]);

        $file = $request->file('file');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('products', $filename, 'public');

        return response()->json([
            'path' => $path,
            'url' => asset('storage/' . $path),
            'filename' => $filename,
            'originalName' => $file->getClientOriginalName(),
            'mimeType' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    /**
     * Upload product datasheet (PDF).
     *
     * POST /api/v1/products/upload-datasheet
     *
     * Requires permission: products.store OR products.update
     * (same permissions as creating/editing a product)
     */
    public function uploadDatasheet(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // User must have permission to create or update products to upload datasheets
        if (!$user->can('products.store') && !$user->can('products.update')) {
            return response()->json(['error' => 'Forbidden - requires products.store or products.update permission'], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB
        ]);

        $file = $request->file('file');
        $filename = Str::uuid() . '.pdf';
        $path = $file->storeAs('datasheets', $filename, 'public');

        return response()->json([
            'path' => $path,
            'url' => asset('storage/' . $path),
            'filename' => $filename,
            'originalName' => $file->getClientOriginalName(),
            'mimeType' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }
}
