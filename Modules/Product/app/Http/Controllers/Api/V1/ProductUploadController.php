<?php

namespace Modules\Product\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Product\Models\Product;
use Spatie\Activitylog\Models\Activity;

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

    /**
     * Download product datasheet with tracking.
     *
     * GET /api/v1/products/{product}/datasheet
     *
     * Public endpoint - no authentication required
     * Logs: IP, User-Agent, user (if authenticated), timestamp, product
     */
    public function downloadDatasheet(Request $request, Product $product)
    {
        // Check if product has a datasheet
        if (!$product->datasheet_path) {
            return response()->json([
                'error' => 'Este producto no tiene ficha técnica disponible'
            ], 404);
        }

        // Build the storage path
        $path = $product->datasheet_path;

        // Handle different path formats
        if (!str_starts_with($path, 'datasheets/')) {
            // If it's just a filename or has a different prefix
            $path = 'datasheets/' . basename($path);
        }

        // Check if file exists
        if (!Storage::disk('public')->exists($path)) {
            return response()->json([
                'error' => 'Archivo no encontrado'
            ], 404);
        }

        // Log the download using Spatie Activity Log
        $user = $request->user('sanctum');

        activity('datasheet_download')
            ->performedOn($product)
            ->by($user) // null if not authenticated
            ->withProperties([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('Referer'),
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'datasheet_path' => $path,
                'authenticated' => $user !== null,
                'user_id' => $user?->id,
                'user_email' => $user?->email,
            ])
            ->log('Descarga de ficha técnica');

        // Get the file
        $fullPath = Storage::disk('public')->path($path);
        $filename = $product->sku
            ? "ficha-tecnica-{$product->sku}.pdf"
            : "ficha-tecnica-{$product->id}.pdf";

        // Return file download response
        return response()->download($fullPath, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
