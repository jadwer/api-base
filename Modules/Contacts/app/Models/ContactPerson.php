<?php

namespace Modules\Contacts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Illuminate\Validation\ValidationException;

class ContactPerson extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'contact_persons';
    
    protected $fillable = [
        'contact_id', 'name', 'position', 'department', 'email', 'phone', 'mobile', 'is_primary', 'metadata'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'metadata' => 'array'
    ];

    protected $attributes = [
        'is_primary' => false,
    ];

    // Business Logic Validation
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($person) {
            $person->validateBusinessRules();
        });

        static::saved(function ($person) {
            if ($person->is_primary) {
                $person->ensureOnlyOnePrimary();
            }
        });
    }

    public function validateBusinessRules()
    {
        // Validate email format if provided
        if ($this->email && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => 'Invalid email format.'
            ]);
        }

        // If this is the first person for the contact, make it primary
        if (!$this->exists && !$this->is_primary) {
            $existingCount = self::where('contact_id', $this->contact_id)->count();
            if ($existingCount === 0) {
                $this->is_primary = true;
            }
        }
    }

    private function ensureOnlyOnePrimary()
    {
        // Remove primary flag from other persons for this contact
        self::where('contact_id', $this->contact_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);
    }

    // Scopes
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeByPosition($query, $position)
    {
        return $query->where('position', 'like', "%{$position}%");
    }

    public function scopeWithContact($query)
    {
        return $query->whereNotNull('contact_id');
    }

    // Business Methods
    public function isPrimary(): bool
    {
        return $this->is_primary;
    }

    public function makePrimary(): void
    {
        $this->is_primary = true;
        $this->save();
    }

    public function removePrimary(): void
    {
        $this->is_primary = false;
        $this->save();
    }

    public function getFullContactInfo(): string
    {
        $parts = array_filter([
            $this->name,
            $this->position,
            $this->department,
        ]);

        $info = implode(' - ', $parts);
        
        if ($this->email) {
            $info .= " ({$this->email})";
        }
        
        return $info;
    }

    public function hasContactInfo(): bool
    {
        return !empty($this->email) || !empty($this->phone) || !empty($this->mobile);
    }

    public function getPreferredContactMethod(): ?string
    {
        if ($this->mobile) return 'mobile';
        if ($this->phone) return 'phone';
        if ($this->email) return 'email';
        return null;
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Contacts\Database\Factories\ContactPersonFactory::new();
    }
}
