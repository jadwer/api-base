<?php

// TODO: Refactorizar con JSON:API de manera empresarial
// - Mover upload, download, view a Actions dentro del ContactDocumentController JSON:API estándar
// - Usar relationships para verify/unverify en lugar de endpoints custom
// - Implementar resource actions según JSON:API specification
// - Eliminar este controlador "pegote" y usar solo JSON:API controllers

namespace Modules\Contacts\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Contacts\Models\ContactDocument;
use Illuminate\Support\Str;

class ContactDocumentUploadController extends Controller
{
    /**
     * Upload a document for a contact
     */
    public function store(Request $request)
    {
        // Manual authentication check for API requests
        if (!auth('sanctum')->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'document_type' => 'required|string|in:rfc,cedula_fiscal,ine,constancia_sat,opinion_sat,certificado_sello,comprobante_domicilio,cotizacion,orden_compra,factura,contrato,otros',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,gif,doc,docx,xls,xlsx|max:10240', // 10MB max
            'notes' => 'nullable|string',
            'expires_at' => 'nullable|date|after:today'
        ]);

        $file = $request->file('file');
        
        // Generate secure filename
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        // Store in organized directory structure
        $path = "contacts/{$request->contact_id}/documents/{$filename}";
        
        // Store file (private by default for security)
        $storedPath = $file->storeAs('contacts/' . $request->contact_id . '/documents', $filename, 'private');
        
        // Create document record
        $document = ContactDocument::create([
            'contact_id' => $request->contact_id,
            'document_type' => $request->document_type,
            'file_path' => $storedPath,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
            'notes' => $request->notes,
            'expires_at' => $request->expires_at,
            'metadata' => [
                'upload_ip' => $request->ip(),
                'upload_user_agent' => $request->userAgent(),
                'upload_timestamp' => now()->toISOString()
            ]
        ]);

        return response()->json([
            'data' => [
                'type' => 'contact-documents',
                'id' => (string) $document->id,
                'attributes' => [
                    'contactId' => $document->contact_id,
                    'documentType' => $document->document_type,
                    'originalFilename' => $document->original_filename,
                    'mimeType' => $document->mime_type,
                    'fileSize' => $document->file_size,
                    'fileSizeFormatted' => $document->getFileSizeFormatted(),
                    'status' => $document->status,
                    'downloadUrl' => route('contact-documents.download', $document->id),
                    'viewUrl' => route('contact-documents.view', $document->id),
                    'notes' => $document->notes,
                    'expiresAt' => $document->expires_at?->format('Y-m-d'),
                    'uploadedAt' => $document->created_at->format('Y-m-d H:i:s')
                ]
            ],
            'message' => 'Document uploaded successfully'
        ], 201);
    }

    /**
     * Download a document (with authorization check)
     */
    public function download(ContactDocument $document)
    {
        // Manual authentication check for API requests
        if (!auth('sanctum')->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!$document->file_path || !Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'Document not found');
        }

        return Storage::disk('private')->download(
            $document->file_path, 
            $document->original_filename
        );
    }

    /**
     * Get document preview/view (for PDFs, images)
     */
    public function view(ContactDocument $document)
    {
        // Manual authentication check for API requests
        if (!auth('sanctum')->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!$document->file_path || !Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'Document not found');
        }

        $file = Storage::disk('private')->get($document->file_path);
        
        return response($file, 200)
            ->header('Content-Type', $document->mime_type)
            ->header('Cache-Control', 'no-cache, private')
            ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->header('Access-Control-Allow-Credentials', 'true');
    }

    /**
     * Verify a document (mark as verified)
     */
    public function verify(ContactDocument $document)
    {
        // Manual authentication check for API requests
        if (!auth('sanctum')->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth('sanctum')->user();
        
        // Check permission (you can customize this based on your permission system)
        if (!$user->can('contact-documents.update')) {
            return response()->json(['error' => 'Forbidden - insufficient permissions'], 403);
        }

        // Verify the document
        $document->verify($user->id);

        return response()->json([
            'data' => [
                'type' => 'contact-documents',
                'id' => (string) $document->id,
                'attributes' => [
                    'status' => $document->status,
                    'verifiedAt' => $document->verified_at?->format('Y-m-d H:i:s'),
                    'verifiedBy' => $document->verified_by,
                    'isVerified' => $document->isVerified(),
                ]
            ],
            'message' => 'Document verified successfully'
        ]);
    }

    /**
     * Unverify a document (remove verification)
     */
    public function unverify(ContactDocument $document)
    {
        // Manual authentication check for API requests
        if (!auth('sanctum')->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth('sanctum')->user();
        
        // Check permission (you can customize this based on your permission system)
        if (!$user->can('contact-documents.update')) {
            return response()->json(['error' => 'Forbidden - insufficient permissions'], 403);
        }

        // Unverify the document
        $document->unverify();

        return response()->json([
            'data' => [
                'type' => 'contact-documents',
                'id' => (string) $document->id,
                'attributes' => [
                    'status' => $document->status,
                    'verifiedAt' => $document->verified_at,
                    'verifiedBy' => $document->verified_by,
                    'isVerified' => $document->isVerified(),
                ]
            ],
            'message' => 'Document verification removed successfully'
        ]);
    }

}