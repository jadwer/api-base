<?php

namespace Modules\Contacts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Validation\ValidationException;

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
        'type', 'name', 'legal_name', 'tax_id', 'email', 'phone', 'website', 'status', 'is_customer', 'is_supplier', 'credit_limit', 'credit_status', 'credit_hold_at', 'credit_hold_reason', 'minimum_payment_score', 'current_credit', 'classification', 'payment_terms', 'notes', 'metadata'
    ];

    protected $casts = [
        'is_customer' => 'boolean',
        'is_supplier' => 'boolean',
        'credit_limit' => 'float',
        'minimum_payment_score' => 'float',
        'current_credit' => 'float',
        'credit_hold_at' => 'datetime',
        'metadata' => 'array'
    ];

    protected $attributes = [
        'type' => 'company',
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
        });
    }

    public function validateBusinessRules()
    {
        // Must be either customer, supplier, or both
        if (!$this->is_customer && !$this->is_supplier) {
            throw ValidationException::withMessages([
                'is_customer' => 'Contact must be either a customer, supplier, or both.'
            ]);
        }

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
        if ($this->type === 'company' && empty($this->legal_name)) {
            $this->legal_name = $this->name;
        }

        // Validate Mexican RFC format if provided
        if ($this->tax_id && !$this->isValidMexicanRFC($this->tax_id)) {
            throw ValidationException::withMessages([
                'tax_id' => 'Invalid Mexican RFC format.'
            ]);
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

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
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

    public function contactDocuments()
    {
        return $this->hasMany(ContactDocument::class);
    }

    public function contactAddresses()
    {
        return $this->hasMany(ContactAddress::class);
    }

    public function contactPeople()
    {
        return $this->hasMany(ContactPerson::class);
    }

    // Cross-module relationships
    public function salesOrders()
    {
        return $this->hasMany(\Modules\Sales\Models\SalesOrder::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(\Modules\Purchase\Models\PurchaseOrder::class);
    }

    public function arInvoices()
    {
        return $this->hasMany(\Modules\Finance\Models\ARInvoice::class, 'contact_id');
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Contacts\Database\Factories\ContactFactory::new();
    }
}
