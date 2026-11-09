<?php

namespace Modules\Billing\Support;

use Illuminate\Database\Eloquent\Builder;
use Modules\Billing\Models\PaymentTransaction;
use Modules\Contacts\Models\Contact;
use Modules\User\Support\UserAccess;

/**
 * Quien ve una transaccion de pago (Stripe): el personal con el permiso ve
 * todas; el cliente del portal solo las de SUS compras (la sesion de checkout
 * es suya o el pedido es de su contacto). Antes el listado no filtraba y un
 * cliente veia montos, tarjetas y pedidos de todos (regla 8, 2026-11-09).
 */
final class PaymentTransactionOwnership
{
    public static function scope($user, Builder $query): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }
        if (UserAccess::seesAll($user, 'billing.payment-transactions.index')) {
            return $query;
        }
        $contactId = Contact::where('email', $user->email)->value('id');

        return $query->where(function (Builder $q) use ($user, $contactId) {
            $q->whereHas('checkoutSession', fn (Builder $s) => $s->where('user_id', $user->id));
            if ($contactId) {
                $q->orWhereHas('salesOrder', fn (Builder $o) => $o->where('contact_id', $contactId));
            }
        });
    }

    public static function visible($user, PaymentTransaction $transaction): bool
    {
        if (! $user) {
            return false;
        }
        if (UserAccess::seesAll($user, 'billing.payment-transactions.index')) {
            return true;
        }
        if ($transaction->checkoutSession && (int) $transaction->checkoutSession->user_id === (int) $user->getAuthIdentifier()) {
            return true;
        }
        $contactId = $transaction->salesOrder?->contact_id;

        return $contactId !== null && Contact::where('email', $user->email)->where('id', $contactId)->exists();
    }
}
