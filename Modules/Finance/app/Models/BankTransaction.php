<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Database\Factories\BankTransactionFactory;
use App\Models\User;

class BankTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_account_id',
        'transaction_date',
        'amount',
        'transaction_type',
        'reference',
        'description',
        'reconciliation_status',
        'reconciled_by_id',
        'reconciled_at',
        'reconciliation_notes',
        'statement_number',
        'running_balance',
        'is_active',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'float',
        'running_balance' => 'float',
        'is_active' => 'boolean',
        'reconciled_at' => 'datetime',
    ];

    /**
     * Bank account relationship
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * User who reconciled this transaction
     */
    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by_id');
    }

    /**
     * Mark transaction as reconciled
     */
    public function markAsReconciled(User $user, string $notes = null): void
    {
        $this->update([
            'reconciliation_status' => 'reconciled',
            'reconciled_by_id' => $user->id,
            'reconciled_at' => now(),
            'reconciliation_notes' => $notes,
        ]);
    }

    /**
     * Scope: Unreconciled transactions
     */
    public function scopeUnreconciled($query)
    {
        return $query->where('reconciliation_status', 'unreconciled');
    }

    /**
     * Scope: Reconciled transactions
     */
    public function scopeReconciled($query)
    {
        return $query->where('reconciliation_status', 'reconciled');
    }

    /**
     * Scope: Active transactions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: For specific bank account
     */
    public function scopeForBankAccount($query, int $bankAccountId)
    {
        return $query->where('bank_account_id', $bankAccountId);
    }

    /**
     * Scope: Within date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return BankTransactionFactory::new();
    }
}
