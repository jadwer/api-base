<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\Models\Account;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\PaymentMethod;
use Modules\SatCatalogs\Models\SatFormaPago;

/**
 * Fase B CxC: registro de un pago directo sobre una factura AR.
 *
 * Crea el Payment (con la forma de pago SAT mapeada a PaymentMethod por code)
 * y lo aplica via PaymentApplicationService, que es quien actualiza saldos,
 * hace el GL posting y dispara ARInvoiceFullyPaid al liquidar. Las comisiones
 * (Modules/Commissions) dependen de ese evento, por eso NUNCA se bypassea.
 */
class ARInvoicePaymentRegistrationService
{
    /**
     * Estados de factura sobre los que no se puede registrar un pago.
     * El codebase usa 'voided' (SalesOrderCancelledListener) y 'cancelled'
     * (LatePenaltyService); la factory legada tambien genera 'void'.
     */
    private const BLOCKED_STATUSES = ['void', 'voided', 'cancelled'];

    public function __construct(
        private PaymentApplicationService $paymentApplicationService,
    ) {}

    /**
     * Registrar un pago sobre la factura.
     *
     * @param ARInvoice $invoice
     * @param array $data payment_date, amount, forma_pago, reference?, comments?, bank_account_id?
     * @return Payment
     * @throws ValidationException Si la factura esta cancelada/anulada o el monto excede el saldo
     */
    public function register(ARInvoice $invoice, array $data): Payment
    {
        $amount = round((float) $data['amount'], 2);

        $this->validateInvoice($invoice, $amount);

        return DB::transaction(function () use ($invoice, $data, $amount) {
            $formaPago = SatFormaPago::findOrFail($data['forma_pago']);

            $payment = Payment::create([
                'payment_number' => $this->generatePaymentNumber(),
                'payment_date' => $data['payment_date'],
                'contact_id' => $invoice->contact_id,
                'bank_account_id' => $data['bank_account_id'] ?? $this->resolveDefaultBankAccount($invoice)->id,
                'payment_method_id' => $this->resolvePaymentMethod($formaPago)->id,
                'amount' => $amount,
                'currency' => $invoice->currency ?? 'MXN',
                'applied_amount' => 0,
                'unapplied_amount' => $amount,
                'status' => 'unapplied',
                'reference' => $data['reference'] ?? null,
                'notes' => $data['comments'] ?? null,
                'metadata' => ['forma_pago' => $formaPago->clave],
                'is_active' => true,
            ]);

            $this->paymentApplicationService->applyPayment($payment, $invoice, $amount);

            return $payment->fresh();
        });
    }

    /**
     * Validaciones de negocio previas a la transaccion.
     */
    private function validateInvoice(ARInvoice $invoice, float $amount): void
    {
        if (in_array($invoice->status, self::BLOCKED_STATUSES, true)) {
            throw ValidationException::withMessages([
                'invoice' => "No se puede registrar un pago sobre una factura con estado '{$invoice->status}'.",
            ]);
        }

        if (!$invoice->is_active) {
            throw ValidationException::withMessages([
                'invoice' => 'No se puede registrar un pago sobre una factura inactiva.',
            ]);
        }

        $balance = round($invoice->total_amount - ($invoice->paid_amount ?? 0), 2);

        if ($amount > $balance + 0.01) {
            throw ValidationException::withMessages([
                'amount' => "El monto ({$amount}) excede el saldo pendiente de la factura ({$balance}).",
            ]);
        }
    }

    /**
     * La forma de pago SAT se persiste reutilizando el catalogo PaymentMethod
     * existente (payments.payment_method_id): se busca o crea por code = clave SAT.
     * Ademas queda la clave cruda en payments.metadata.forma_pago.
     */
    private function resolvePaymentMethod(SatFormaPago $formaPago): PaymentMethod
    {
        return PaymentMethod::firstOrCreate(
            ['code' => $formaPago->clave],
            [
                'name' => $formaPago->descripcion,
                'type' => 'sat_forma_pago',
                'requires_reference' => false,
                'is_active' => true,
            ]
        );
    }

    /**
     * payments.bank_account_id es NOT NULL: si el caller no manda uno se usa
     * la primera cuenta activa y, si no existe ninguna, se crea una caja general.
     */
    private function resolveDefaultBankAccount(ARInvoice $invoice): BankAccount
    {
        $account = BankAccount::active()->orderBy('id')->first();

        if ($account) {
            return $account;
        }

        return BankAccount::firstOrCreate(
            ['account_number' => 'CAJA-GENERAL'],
            [
                'account_name' => 'Caja general',
                'bank_name' => 'Caja',
                'currency' => $invoice->currency ?? 'MXN',
                'gl_account_id' => Account::where('code', '1102')->value('id') ?? 0,
                'status' => 'active',
                'is_active' => true,
            ]
        );
    }

    /**
     * Numero secuencial PAY-XXXXXX (mismo patron que ARInvoiceService::generateInvoiceNumber),
     * con guarda de unicidad porque la factory legada genera PAY-##### aleatorios.
     */
    private function generatePaymentNumber(): string
    {
        $last = Payment::lockForUpdate()->orderBy('id', 'desc')->first();

        $next = 1;
        if ($last && preg_match('/(\d+)$/', (string) $last->payment_number, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        do {
            $number = 'PAY-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (Payment::where('payment_number', $number)->exists());

        return $number;
    }
}
