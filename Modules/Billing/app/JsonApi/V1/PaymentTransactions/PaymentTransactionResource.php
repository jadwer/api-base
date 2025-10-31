<?php

namespace Modules\Billing\JsonApi\V1\PaymentTransactions;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class PaymentTransactionResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'gateway' => $this->resource->gateway,
            'paymentIntentId' => $this->resource->payment_intent_id,
            'transactionId' => $this->resource->transaction_id,
            // clientSecret is intentionally excluded for security
            'amount' => $this->resource->amount,
            'currency' => $this->resource->currency,
            'status' => $this->resource->status,
            'paymentMethod' => $this->resource->payment_method,
            'cardBrand' => $this->resource->card_brand,
            'cardLast4' => $this->resource->card_last4,
            'gatewayResponse' => $this->resource->gateway_response,
            'errorMessage' => $this->resource->error_message,
            'authorizedAt' => $this->resource->authorized_at,
            'capturedAt' => $this->resource->captured_at,
            'failedAt' => $this->resource->failed_at,
            'refundedAt' => $this->resource->refunded_at,
            'metadata' => $this->resource->metadata,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            $this->relation('checkoutSession'),
            $this->relation('salesOrder'),
            $this->relation('arInvoice'),
        ];
    }
}
