<?php

namespace Modules\Contacts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Illuminate\Validation\ValidationException;

class ContactAddress extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'contact_addresses';
    
    protected $fillable = [
        'contact_id', 'address_type', 'address_line_1', 'address_line_2', 'city', 'state', 'country', 'postal_code', 'is_default', 'metadata'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'metadata' => 'array'
    ];

    protected $attributes = [
        'country' => 'MX',
        'is_default' => false,
    ];

    // Business Logic Validation
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($address) {
            $address->validateBusinessRules();
        });

        static::saved(function ($address) {
            if ($address->is_default) {
                $address->ensureOnlyOneDefault();
            }
        });
    }

    public function validateBusinessRules()
    {
        // Validate postal code format for Mexico
        if ($this->country === 'MX' && $this->postal_code) {
            if (!preg_match('/^[0-9]{5}$/', $this->postal_code)) {
                throw ValidationException::withMessages([
                    'postal_code' => 'Mexican postal code must be exactly 5 digits.'
                ]);
            }
        }

        // If this is the first address for the contact, make it default
        if (!$this->exists && !$this->is_default) {
            $existingCount = self::where('contact_id', $this->contact_id)->count();
            if ($existingCount === 0) {
                $this->is_default = true;
            }
        }
    }

    private function ensureOnlyOneDefault()
    {
        // Remove default flag from other addresses for this contact
        self::where('contact_id', $this->contact_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);
    }

    // Scopes
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('address_type', $type);
    }

    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    public function scopeByCity($query, $city)
    {
        return $query->where('city', 'like', "%{$city}%");
    }

    // Business Methods
    public function isDefault(): bool
    {
        return $this->is_default;
    }

    public function isBilling(): bool
    {
        return in_array($this->address_type, ['billing', 'both']);
    }

    public function isShipping(): bool
    {
        return in_array($this->address_type, ['shipping', 'both']);
    }

    public function isFiscal(): bool
    {
        return $this->address_type === 'fiscal';
    }

    public function makeDefault(): void
    {
        $this->is_default = true;
        $this->save();
    }

    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country
        ]);

        return implode(', ', $parts);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Contacts\Database\Factories\ContactAddressFactory::new();
    }
}
