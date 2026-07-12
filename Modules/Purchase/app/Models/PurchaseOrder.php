<?php

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Modules\Purchase\Database\Factories\PurchaseOrderFactory;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\APInvoice;
use Modules\Inventory\Models\Warehouse;
use Modules\User\Models\User;
use Modules\Purchase\Services\PurchaseOrderApprovalService;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\FolioSequence;

class PurchaseOrder extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'order_number', 'status', 'order_date', 'contact_id',
                'total_amount', 'approval_status', 'invoicing_status', 'financial_status'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'contact_id' => 'integer',
            'quote_id' => 'integer',
            'warehouse_id' => 'integer',
            'order_date' => 'date',
            'total_amount' => 'float',
            'ap_invoice_id' => 'integer',
            'invoicing_status' => 'string',
            'financial_status' => 'string',
            'approval_status' => 'string',
            'approved_at' => 'datetime',
            'approved_by_id' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PurchaseOrderFactory
    {
        return PurchaseOrderFactory::new();
    }

    /**
     * Boot method to set initial approval status
     */
    protected static function boot()
    {
        parent::boot();

        // PU-001: Automatically set approval status on creation
        static::creating(function ($order) {
            // Auto-generate order number if not set
            if (empty($order->order_number)) {
                try {
                    $order->order_number = static::generateOrderNumber();
                } catch (\Exception $e) {
                    // Fallback if FolioSequence is not available (e.g., in tests)
                    $order->order_number = 'OC-' . date('y') . '-' . str_pad(static::count() + 1, 5, '0', STR_PAD_LEFT);
                }
            }

            if (!isset($order->approval_status)) {
                // Simple check during creation based on amount threshold only
                // Full approval check (including first-time supplier, high-value items)
                // should be done explicitly via requiresApproval() method
                $threshold = config('purchase.approval_threshold', 50000);

                if ($order->total_amount > $threshold) {
                    $order->approval_status = 'pending';
                } else {
                    $order->approval_status = 'not_required';
                }
            }
        });
    }

    // ========== RELATIONSHIPS ==========

    /**
     * Get the contact for this purchase order.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    /**
     * Backward compatibility alias for supplier
     */
    public function supplier(): BelongsTo
    {
        return $this->contact();
    }

    /**
     * Get the quote that originated this purchase order.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }

    /**
     * Get the warehouse where received items will be stored.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Get the purchase order items for the purchase order.
     */
    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Get the AP invoice for this purchase order.
     */
    public function apInvoice(): BelongsTo
    {
        return $this->belongsTo(APInvoice::class, 'ap_invoice_id');
    }

    /**
     * Get all AP invoices generated from this purchase order.
     */
    public function apInvoices(): HasMany
    {
        return $this->hasMany(APInvoice::class, 'purchase_order_id');
    }

    /**
     * Get the user who approved this order.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    // ========== APPROVAL METHODS (PU-001) ==========

    /**
     * Check if this purchase order requires approval
     *
     * @return bool
     */
    public function requiresApproval(): bool
    {
        $approvalService = app(PurchaseOrderApprovalService::class);
        return $approvalService->requiresApproval($this);
    }

    /**
     * Approve this purchase order
     *
     * @param int $userId
     * @param string $notes
     * @return bool
     * @throws \Exception
     */
    public function approve(int $userId, string $notes = ''): bool
    {
        $approvalService = app(PurchaseOrderApprovalService::class);
        return $approvalService->approve($this, $userId, $notes);
    }

    /**
     * Reject this purchase order
     *
     * @param int $userId
     * @param string $reason
     * @return bool
     * @throws \Exception
     */
    public function reject(int $userId, string $reason): bool
    {
        $approvalService = app(PurchaseOrderApprovalService::class);
        return $approvalService->reject($this, $userId, $reason);
    }

    /**
     * Get approval history for this order
     *
     * @return \Illuminate\Support\Collection
     */
    public function getApprovalHistory(): \Illuminate\Support\Collection
    {
        $approvalService = app(PurchaseOrderApprovalService::class);
        return $approvalService->getApprovalHistory($this);
    }

    // ========== RECEIVING METHODS (PU-005) ==========

    /**
     * Receive items for this purchase order
     *
     * @param array $items Array of ['id' => item_id, 'quantity' => received_quantity]
     * @return void
     * @throws \Exception
     */
    public function receive(array $items): void
    {
        \DB::transaction(function () use ($items) {
            foreach ($items as $itemData) {
                $item = $this->purchaseOrderItems()->find($itemData['id']);

                if (!$item) {
                    throw new \Exception("Purchase order item {$itemData['id']} not found");
                }

                $newReceived = $item->received_quantity + $itemData['quantity'];
                $tolerance = $item->quantity * 1.05; // +5% tolerance

                if ($newReceived > $tolerance) {
                    throw new \Exception(
                        "Over-receiving beyond 5% tolerance for item {$item->id}. " .
                        "Ordered: {$item->quantity}, Already received: {$item->received_quantity}, " .
                        "Attempting to receive: {$itemData['quantity']}, Total: {$newReceived}, Max allowed: {$tolerance}"
                    );
                }

                $item->update(['received_quantity' => $newReceived]);
            }

            // Check if fully received
            if ($this->isFullyReceived()) {
                $this->update(['status' => 'received']);

                // Dispatch event if needed
                // event(new PurchaseOrderReceived($this));
            }
        });
    }

    /**
     * Check if all items have been fully received
     *
     * @return bool
     */
    public function isFullyReceived(): bool
    {
        // Load items if not loaded
        if (!$this->relationLoaded('purchaseOrderItems')) {
            $this->load('purchaseOrderItems');
        }

        foreach ($this->purchaseOrderItems as $item) {
            if ($item->received_quantity < $item->quantity) {
                return false;
            }
        }

        return true;
    }

    // ========== ORDER NUMBER GENERATION ==========

    /**
     * Generate unique purchase order number using FolioSequence
     *
     * Format is configurable via folio_sequences table.
     * Default: OC-26000001 (prefix + year short + sequence)
     */
    public static function generateOrderNumber(): string
    {
        return FolioSequence::getNextFolio('purchase_order');
    }

    // ========== SCOPES ==========

    /**
     * Nota cliente #11: compras "por surtir" (pendientes de recibir).
     *
     * El enum status de purchase_orders es [pending, approved, received,
     * cancelled]. "Por surtir" = ordenes abiertas que aun no se reciben ni se
     * cancelan, es decir status IN (pending, approved). Se expone como filtro
     * de un solo parametro (filter[pending_receipt]=1) para que el frontend
     * haga una sola llamada en lugar de dos filtros por status.
     *
     * Acepta booleano; valor "falsy" no aplica el filtro.
     */
    public function scopePendingReceipt(Builder $query, $value = true): Builder
    {
        $enabled = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $enabled = $enabled ?? true;

        if (!$enabled) {
            return $query;
        }

        return $query->whereIn('status', ['pending', 'approved']);
    }

    /**
     * Apply filters to the query.
     */
    public function scopeFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('contact_id'), function ($query) use ($request) {
                return $query->where('contact_id', $request->contact_id);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->when($request->filled('order_date_from'), function ($query) use ($request) {
                return $query->where('order_date', '>=', $request->order_date_from);
            })
            ->when($request->filled('order_date_to'), function ($query) use ($request) {
                return $query->where('order_date', '<=', $request->order_date_to);
            });
    }
}
