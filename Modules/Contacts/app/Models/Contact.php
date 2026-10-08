<?php

namespace Modules\Contacts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property bool $is_customer
 * @property bool $is_supplier
 */
class Contact extends Model
{
    use HasFactory, HasPermissions, LogsActivity;

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'email', 'phone', 'status', 'is_customer', 'is_supplier',
                'credit_limit', 'credit_status', 'classification'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $table = 'contacts';
    
    protected $fillable = [
        'contact_type', 'name', 'legal_name', 'tax_id', 'email', 'additional_emails', 'phone', 'phone_extension', 'phones', 'website', 'status', 'is_customer', 'is_supplier', 'credit_limit', 'credit_status', 'credit_hold_at', 'credit_hold_reason', 'minimum_payment_score', 'current_credit', 'classification', 'payment_terms', 'notes', 'metadata',
        // WS5 Commissions
        'default_salesperson_id', 'collections_agent_id', 'commission_pct_override',
        // WS7.1 Bind fields
        'regimen_fiscal', 'uso_cfdi', 'credit_months', 'bank_account_number', 'referral_source', 'cuenta_contable', 'discount_pct',
    ];

    protected $casts = [
        'is_customer' => 'boolean',
        'is_supplier' => 'boolean',
        'credit_limit' => 'float',
        'minimum_payment_score' => 'float',
        'current_credit' => 'float',
        'credit_hold_at' => 'datetime',
        'metadata' => 'array',
        'additional_emails' => 'array',
        'phones' => 'array',
        'default_salesperson_id' => 'integer',
        'collections_agent_id' => 'integer',
        'commission_pct_override' => 'float',
        'credit_months' => 'integer',
        'discount_pct' => 'float',
    ];

    protected $attributes = [
        'contact_type' => 'company',
        'status' => 'active',
        'is_customer' => false,
        'is_supplier' => false,
        'credit_limit' => 0.00,
        'current_credit' => 0.00,
        'payment_terms' => 30,
    ];

    // Business Logic Validation
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($contact) {
            $contact->validateBusinessRules();
            $contact->syncChannels();
        });
    }

    public function validateBusinessRules()
    {
        // Credit limit only applies to customers
        if (!$this->is_customer && $this->credit_limit > 0) {
            throw ValidationException::withMessages([
                'credit_limit' => 'Credit limit can only be set for customers.'
            ]);
        }

        // Current credit cannot exceed credit limit
        if ($this->is_customer && $this->current_credit > $this->credit_limit) {
            throw ValidationException::withMessages([
                'current_credit' => 'Current credit cannot exceed credit limit.'
            ]);
        }

        // Legal name required for companies
        if ($this->contact_type === 'company' && empty($this->legal_name)) {
            $this->legal_name = $this->name;
        }

        // Validate Mexican RFC format if provided
        if ($this->tax_id && !$this->isValidMexicanRFC($this->tax_id)) {
            throw ValidationException::withMessages([
                'tax_id' => 'Invalid Mexican RFC format.'
            ]);
        }
    }

    /**
     * Correos y telefonos (2026-09-30): normaliza las listas y mantiene
     * `phone`/`phone_extension` como reflejo del primer telefono. Si otro
     * flujo (checkout, CRM) escribe solo `phone`, se arma la lista con el.
     */
    public function syncChannels(): void
    {
        if ($this->email !== null) {
            $this->email = trim($this->email) === '' ? null : trim($this->email);
        }
        $this->additional_emails = \Modules\Contacts\Support\ContactChannels::normalizeEmails(
            $this->additional_emails ?? [],
            $this->email
        ) ?: null;

        if ($this->isDirty('phones')) {
            $phones = \Modules\Contacts\Support\ContactChannels::normalizePhones($this->phones ?? []);
            $this->phones = $phones ?: null;
            $first = $phones[0] ?? null;
            $this->phone = $first ? \Modules\Contacts\Support\ContactChannels::formatPhone($first) : null;
            $this->phone_extension = $first['ext'] ?? null;
        } elseif ($this->isDirty('phone') && empty($this->phones) && $this->phone) {
            $legacy = \Modules\Contacts\Support\ContactChannels::fromLegacyPhone($this->phone, $this->phone_extension);
            $this->phones = $legacy ? [$legacy] : null;
        }
    }

    private function isValidMexicanRFC($rfc)
    {
        // Mexican RFC validation (basic pattern)
        $pattern = '/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/';
        return preg_match($pattern, strtoupper($rfc));
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCustomers($query)
    {
        return $query->where('is_customer', true);
    }

    public function scopeSuppliers($query)
    {
        return $query->where('is_supplier', true);
    }

    public function scopeMixed($query)
    {
        return $query->where('is_customer', true)->where('is_supplier', true);
    }

    public function scopeProspects($query)
    {
        return $query->where('is_customer', false)->where('is_supplier', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('contact_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByClassification($query, $classification)
    {
        return $query->where('classification', $classification);
    }

    // Business Methods
    public function isCustomer(): bool
    {
        return $this->is_customer;
    }

    public function isSupplier(): bool
    {
        return $this->is_supplier;
    }

    public function isMixed(): bool
    {
        return $this->is_customer && $this->is_supplier;
    }

    public function isProspect(): bool
    {
        return !$this->is_customer && !$this->is_supplier;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canPurchase(): bool
    {
        return $this->isCustomer() && $this->isActive();
    }

    public function canSupply(): bool
    {
        return $this->isSupplier() && $this->isActive();
    }

    public function getAvailableCredit(): float
    {
        if (!$this->isCustomer()) {
            return 0.00;
        }
        
        return max(0, $this->credit_limit - $this->current_credit);
    }

    public function hasAvailableCredit(float $amount = 0): bool
    {
        return $this->getAvailableCredit() >= $amount;
    }

    public function updateCredit(float $amount, string $operation = 'add'): void
    {
        if (!$this->isCustomer()) {
            throw ValidationException::withMessages([
                'credit' => 'Credit operations only apply to customers.'
            ]);
        }

        $newCredit = $operation === 'add' 
            ? $this->current_credit + $amount 
            : $this->current_credit - $amount;

        if ($newCredit > $this->credit_limit) {
            throw ValidationException::withMessages([
                'credit' => 'Operation would exceed credit limit.'
            ]);
        }

        if ($newCredit < 0) {
            throw ValidationException::withMessages([
                'credit' => 'Credit cannot be negative.'
            ]);
        }

        $this->current_credit = $newCredit;
        $this->save();
    }

    // Status Management
    public function activate(): void
    {
        $this->status = 'active';
        $this->save();
    }

    public function deactivate(): void
    {
        $this->status = 'inactive';
        $this->save();
    }

    public function suspend(): void
    {
        $this->status = 'suspended';
        $this->save();
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<ContactDocument, $this> */
    public function contactDocuments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ContactDocument::class);
    }

    /**
     * Acceso al portal: existe un usuario del sistema con el email del
     * contacto (el vinculo contacto-usuario es por email, no hay FK).
     * Computed para la ficha; el exists() usa el indice de users.email.
     */
    public function getHasPortalUserAttribute(): bool
    {
        if (!$this->email) {
            return false;
        }

        return \Modules\User\Models\User::where('email', $this->email)->exists();
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<ContactAddress, $this> */
    public function contactAddresses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ContactAddress::class);
    }

    /**
     * Direccion fiscal (2026-09-30): la de tipo fiscal; si no hay, la de
     * facturacion predeterminada, luego cualquier predeterminada, luego la
     * primera. Fuente unica para CFDI y PDFs.
     */
    public function fiscalAddress(): ?ContactAddress
    {
        $addresses = $this->relationLoaded('contactAddresses')
            ? $this->contactAddresses
            : $this->contactAddresses()->get();

        return $addresses->firstWhere('address_type', 'fiscal')
            ?? $addresses->first(fn ($a) => $a->is_default && in_array($a->address_type, ['billing', 'both'], true))
            ?? $addresses->firstWhere('is_default', true)
            ?? $addresses->first();
    }

    /** Razon social para documentos fiscales; si no hay, el nombre. */
    public function fiscalName(): string
    {
        return trim((string) $this->legal_name) !== '' ? $this->legal_name : (string) $this->name;
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<ContactPerson, $this> */
    public function contactPeople(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ContactPerson::class);
    }

    // WS5 Commissions relationships
    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Modules\User\Models\User, $this> */
    public function defaultSalesperson(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\Modules\User\Models\User::class, 'default_salesperson_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Modules\User\Models\User, $this> */
    public function collectionsAgent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\Modules\User\Models\User::class, 'collections_agent_id');
    }

    // Cross-module relationships
    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\Modules\Sales\Models\SalesOrder, $this> */
    public function salesOrders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Modules\Sales\Models\SalesOrder::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\Modules\Purchase\Models\PurchaseOrder, $this> */
    public function purchaseOrders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Modules\Purchase\Models\PurchaseOrder::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\Modules\Finance\Models\ARInvoice, $this> */
    public function arInvoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Modules\Finance\Models\ARInvoice::class, 'contact_id');
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Contacts\Database\Factories\ContactFactory::new();
    }
}
