<?php

namespace Modules\Contacts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ContactDocument extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'contact_documents';
    
    protected $fillable = [
        'contact_id', 'document_type', 'file_path', 'original_filename', 'mime_type', 'file_size', 'uploaded_by', 'verified_at', 'verified_by', 'expires_at', 'notes', 'metadata'
    ];

    protected $casts = [
        'verified_at' => 'date',
        'expires_at' => 'date',
        'metadata' => 'array'
    ];

    // Business Logic Validation
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($document) {
            $document->validateBusinessRules();
        });

        static::deleting(function ($document) {
            $document->deletePhysicalFile();
        });
    }

    public function validateBusinessRules()
    {
        // Validate file size (max 10MB for example)
        if ($this->file_size && $this->file_size > 10 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'file_size' => 'File size cannot exceed 10MB.'
            ]);
        }

        // Validate allowed document types
        $allowedTypes = [
            'rfc', 'cedula_fiscal', 'ine', 'constancia_sat', 'opinion_sat', 
            'certificado_sello', 'comprobante_domicilio', 'cotizacion', 
            'orden_compra', 'factura', 'contrato', 'otros'
        ];
        
        if (!in_array($this->document_type, $allowedTypes)) {
            throw ValidationException::withMessages([
                'document_type' => 'Invalid document type.'
            ]);
        }

        // Validate allowed MIME types for security
        $allowedMimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        if ($this->mime_type && !in_array($this->mime_type, $allowedMimeTypes)) {
            throw ValidationException::withMessages([
                'mime_type' => 'File type not allowed for security reasons.'
            ]);
        }

        // Expiration date must be in the future if set
        if ($this->expires_at && $this->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'expires_at' => 'Expiration date must be in the future.'
            ]);
        }
    }

    private function deletePhysicalFile()
    {
        if ($this->file_path && Storage::exists($this->file_path)) {
            Storage::delete($this->file_path);
        }
    }

    // Scopes
    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopeUnverified($query)
    {
        return $query->whereNull('verified_at');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeExpiringIn($query, $days = 30)
    {
        return $query->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    public function scopeUploadedBy($query, $userId)
    {
        return $query->where('uploaded_by', $userId);
    }

    // Business Methods
    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isExpiringIn($days = 30): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        
        return $this->expires_at->isBefore(now()->addDays($days));
    }

    public function verify($verifiedBy = null): void
    {
        $this->verified_at = now();
        $this->verified_by = $verifiedBy;
        $this->save();
    }

    public function unverify(): void
    {
        $this->verified_at = null;
        $this->verified_by = null;
        $this->save();
    }

    public function getFileUrl(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return Storage::url($this->file_path);
    }

    public function getFileSizeFormatted(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getExpirationStatus(): string
    {
        if (!$this->expires_at) {
            return 'no_expiration';
        }
        
        if ($this->isExpired()) {
            return 'expired';
        }
        
        if ($this->isExpiringIn(30)) {
            return 'expiring_soon';
        }
        
        return 'valid';
    }

    public function isCriticalDocument(): bool
    {
        $criticalTypes = ['rfc', 'cedula_fiscal', 'constancia_sat'];
        return in_array($this->document_type, $criticalTypes);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Contacts\Database\Factories\ContactDocumentFactory::new();
    }
}
