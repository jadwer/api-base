<?php

namespace Modules\Finance\JsonApi\V1\ARInvoiceReceipts;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class ARInvoiceReceiptAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        Log::info('ARInvoiceReceiptAuthorizer@index called', [
            'user_id' => $request->user()?->id,
            'model_class' => $modelClass,
        ]);
        
        $user = $request->user();
        return $user?->can('finance.a-r-invoice-receipts.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        Log::info('ARInvoiceReceiptAuthorizer@store called', [
            'user_id' => $request->user()?->id,
            'model_class' => $modelClass,
        ]);
        
        $user = $request->user();
        return $user?->can('finance.a-r-invoice-receipts.store') ?? false;
    }
    
    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('finance.a-r-invoice-receipts.show') ?? false;
    }
    
    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('finance.a-r-invoice-receipts.update') ?? false;
    }
    
    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('finance.a-r-invoice-receipts.destroy') ?? false;
    }
    
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }
    
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }
    
    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }
    
    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }
    
    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }
}
