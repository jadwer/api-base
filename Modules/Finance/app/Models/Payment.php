<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Modules\Accounting\Models\JournalEntry;
use Modules\Contacts\Models\Contact;

/**
 * @property int $id
 * @property string $payment_number
 * @property \Illuminate\Support\Carbon $payment_date
 * @property int $contact_id
 * @property int $bank_account_id
 * @property int $payment_method_id
 * @property float $amount
 * @property string $currency
 * @property float|null $applied_amount
 * @property float|null $unapplied_amount
 * @property string $status
 * @property int|null $journal_entry_id
 * @property string|null $reference
 * @property string|null $notes
 * @property array<string, mixed>|null $metadata
 * @property bool|null $is_active
 */
class Payment extends Model
{
    use HasFactory, HasPermissions, LogsActivity;

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'payment_number', 'status', 'payment_date', 'contact_id',
                'amount', 'applied_amount', 'unapplied_amount', 'payment_method_id'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $table = 'payments';
    
    protected $fillable = [
        'payment_number', 'payment_date', 'contact_id', 'bank_account_id', 'payment_method_id', 'amount', 'currency', 'applied_amount', 'unapplied_amount', 'status', 'journal_entry_id', 'reference', 'notes', 'metadata', 'is_active'
    ];

    protected $casts = [
                'payment_date' => 'date',
        'amount' => 'float',
        'applied_amount' => 'float',
        'unapplied_amount' => 'float',
        'metadata' => 'array',
        'is_active' => 'boolean'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * filter[direction]: ap = pagos a proveedor, ar = cobros a cliente.
     * La tabla no guarda la direccion; se toma de los flags del contacto, asi
     * que un contacto que es cliente y proveedor aparece en ambas listas.
     */
    public function scopeDirection($query, string $direction)
    {
        return match ($direction) {
            'ap' => $query->whereHas('contact', fn ($q) => $q->where('is_supplier', true)),
            'ar' => $query->whereHas('contact', fn ($q) => $q->where('is_customer', true)),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /** Siguiente folio PAY-000001 (mismo formato para el alta directa y el registro de cobro AR). */
    public static function nextPaymentNumber(): string
    {
        $last = static::query()->lockForUpdate()->orderBy('id', 'desc')->first();

        $next = 1;
        if ($last && preg_match('/(\d+)$/', (string) $last->payment_number, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        do {
            $number = 'PAY-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (static::query()->where('payment_number', $number)->exists());

        return $number;
    }

    protected static function booted(): void
    {
        // POST /payments sin paymentNumber: la columna es NOT NULL y unica
        static::creating(function (Payment $payment) {
            if (blank($payment->payment_number)) {
                $payment->payment_number = static::nextPaymentNumber();
            }
        });
    }


    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    // Legacy alias for backward compatibility
    public function customer()
    {
        return $this->contact();
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function paymentApplications()
    {
        return $this->hasMany(PaymentApplication::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Finance\Database\Factories\PaymentFactory::new();
    }
}
