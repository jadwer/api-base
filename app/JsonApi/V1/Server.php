<?php

namespace App\JsonApi\V1;

use Illuminate\Support\Facades\Auth;
use LaravelJsonApi\Core\Server\Server as BaseServer;
use Modules\Audit\JsonApi\V1\Audits\AuditAuthorizer;
use Modules\Audit\JsonApi\V1\Audits\AuditSchema;
use Modules\Inventory\JsonApi\V1\Warehouses\WarehouseSchema;
use Modules\Inventory\JsonApi\V1\WarehouseLocations\WarehouseLocationSchema;
use Modules\Inventory\JsonApi\V1\Stocks\StockSchema;
use Modules\Inventory\JsonApi\V1\ProductBatches\ProductBatchSchema;
use Modules\Inventory\JsonApi\V1\InventoryMovements\InventoryMovementSchema;
use Modules\Purchase\JsonApi\V1\Suppliers\SupplierSchema;
use Modules\Purchase\JsonApi\V1\PurchaseOrders\PurchaseOrderSchema;
use Modules\Purchase\JsonApi\V1\PurchaseOrderItems\PurchaseOrderItemSchema;
use Modules\User\JsonApi\V1\Users\UserSchema;
use Modules\PageBuilder\JsonApi\V1\Pages\PageSchema;
use Modules\PermissionManager\JsonApi\V1\Permissions\PermissionSchema;
use Modules\PermissionManager\JsonApi\V1\Roles\RoleSchema;
use Modules\Product\JsonApi\V1\Products\ProductSchema;
use Modules\Product\JsonApi\V1\PublicProducts\PublicProductSchema;
use Modules\Product\JsonApi\V1\Units\UnitSchema;
use Modules\Product\JsonApi\V1\Categories\CategorySchema;
use Modules\Product\JsonApi\V1\Brands\BrandSchema;
use Modules\Ecommerce\JsonApi\V1\ShoppingCarts\ShoppingCartSchema;
use Modules\Ecommerce\JsonApi\V1\CartItems\CartItemSchema;
use Modules\Ecommerce\JsonApi\V1\Coupons\CouponSchema;
use Modules\VerificationTest\JsonApi\V1\VerificationItems\VerificationItemSchema;
use Modules\Contacts\JsonApi\V1\Contacts\ContactSchema;
use Modules\Contacts\JsonApi\V1\ContactDocuments\ContactDocumentSchema;
use Modules\Contacts\JsonApi\V1\ContactAddresses\ContactAddressSchema;
use Modules\Contacts\JsonApi\V1\ContactPeople\ContactPersonSchema;
use Modules\Accounting\JsonApi\V1\Accounts\AccountSchema;
use Modules\Accounting\JsonApi\V1\FiscalPeriods\FiscalPeriodSchema;
use Modules\Accounting\JsonApi\V1\Journals\JournalSchema;
use Modules\Accounting\JsonApi\V1\JournalEntries\JournalEntrySchema;
use Modules\Accounting\JsonApi\V1\JournalLines\JournalLineSchema;
use Modules\Accounting\JsonApi\V1\ExchangeRates\ExchangeRateSchema;
use Modules\Finance\JsonApi\V1\BankAccounts\BankAccountSchema;
use Modules\Finance\JsonApi\V1\BankStatements\BankStatementSchema;
use Modules\Finance\JsonApi\V1\BankStatementLines\BankStatementLineSchema;
use Modules\Finance\JsonApi\V1\APInvoices\APInvoiceSchema;
use Modules\Finance\JsonApi\V1\APInvoiceLines\APInvoiceLineSchema;
use Modules\Finance\JsonApi\V1\APPayments\APPaymentSchema;
use Modules\Finance\JsonApi\V1\APInvoicePayments\APInvoicePaymentSchema;
use Modules\Finance\JsonApi\V1\ARInvoices\ARInvoiceSchema;
use Modules\Finance\JsonApi\V1\ARInvoiceLines\ARInvoiceLineSchema;
use Modules\Finance\JsonApi\V1\ARReceipts\ARReceiptSchema;
use Modules\Finance\JsonApi\V1\ARInvoiceReceipts\ARInvoiceReceiptSchema;

class Server extends BaseServer
{

    /**
     * The base URI namespace for this server.
     *
     * @var string
     */
    protected string $baseUri = '/api/v1';

    /**
     * Bootstrap the server when it is handling an HTTP request.
     *
     * @return void
     */
    public function serving(): void
    {
        // no-op
        Auth::shouldUse('sanctum');
    }

    /**
     * Get the server's list of schemas.
     *
     * @return array
     */
    protected function allSchemas(): array
    {
        $schemas = [
            UserSchema::class,
            AuditSchema::class,
            PageSchema::class,
            RoleSchema::class,
            PermissionSchema::class,

            // Product Module
            ProductSchema::class,
            UnitSchema::class,
            CategorySchema::class,
            BrandSchema::class,

            // Inventory Module
            WarehouseSchema::class,
            WarehouseLocationSchema::class,
            StockSchema::class,
            ProductBatchSchema::class,
            InventoryMovementSchema::class,

            // Purchase Module
            PurchaseOrderSchema::class,
            PurchaseOrderItemSchema::class,

            // Sales Module
            \Modules\Sales\JsonApi\V1\SalesOrders\SalesOrderSchema::class,
            \Modules\Sales\JsonApi\V1\SalesOrderItems\SalesOrderItemSchema::class,

            // Ecommerce Module
            ShoppingCartSchema::class,
            CartItemSchema::class,
            CouponSchema::class,

            // Contacts Module
            ContactSchema::class,
            ContactDocumentSchema::class,
            ContactAddressSchema::class,
            ContactPersonSchema::class,



            // Accounting Module
            AccountSchema::class,
            FiscalPeriodSchema::class,
            JournalSchema::class,
            JournalEntrySchema::class,
            JournalLineSchema::class,
            ExchangeRateSchema::class,


            // Finance Module
            BankAccountSchema::class,
            BankStatementSchema::class,
            BankStatementLineSchema::class,
            APInvoiceSchema::class,
            APInvoiceLineSchema::class,
            APPaymentSchema::class,
            APInvoicePaymentSchema::class,
            ARInvoiceSchema::class,
            ARInvoiceLineSchema::class,
            ARReceiptSchema::class,
            ARInvoiceReceiptSchema::class,

        ];
        
        return $schemas;
    }

    protected function authorizers(): array
    {
        $authorizers = [
            'audits' => AuditAuthorizer::class,
            'products' => \Modules\Product\JsonApi\V1\Products\ProductAuthorizer::class,
            'units' => \Modules\Product\JsonApi\V1\Units\UnitAuthorizer::class,
            'categories' => \Modules\Product\JsonApi\V1\Categories\CategoryAuthorizer::class,
            'brands' => \Modules\Product\JsonApi\V1\Brands\BrandAuthorizer::class,
            'warehouses' => \Modules\Inventory\JsonApi\V1\Warehouses\WarehouseAuthorizer::class,
            'warehouse-locations' => \Modules\Inventory\JsonApi\V1\WarehouseLocations\WarehouseLocationAuthorizer::class,
            'stocks' => \Modules\Inventory\JsonApi\V1\Stocks\StockAuthorizer::class,
            'product-batches' => \Modules\Inventory\JsonApi\V1\ProductBatches\ProductBatchAuthorizer::class,
            'inventory-movements' => \Modules\Inventory\JsonApi\V1\InventoryMovements\InventoryMovementAuthorizer::class,
            'purchase-orders' => \Modules\Purchase\JsonApi\V1\PurchaseOrders\PurchaseOrderAuthorizer::class,
            'purchase-order-items' => \Modules\Purchase\JsonApi\V1\PurchaseOrderItems\PurchaseOrderItemAuthorizer::class,
            'sales-orders' => \Modules\Sales\JsonApi\V1\SalesOrders\SalesOrderAuthorizer::class,
            'sales-order-items' => \Modules\Sales\JsonApi\V1\SalesOrderItems\SalesOrderItemAuthorizer::class,
            
            // Ecommerce Module
            'shopping-carts' => \Modules\Ecommerce\JsonApi\V1\ShoppingCarts\ShoppingCartAuthorizer::class,
            'cart-items' => \Modules\Ecommerce\JsonApi\V1\CartItems\CartItemAuthorizer::class,
            'coupons' => \Modules\Ecommerce\JsonApi\V1\Coupons\CouponAuthorizer::class,

            // Contacts Module
            'contacts' => \Modules\Contacts\JsonApi\V1\Contacts\ContactAuthorizer::class,
            'contact-documents' => \Modules\Contacts\JsonApi\V1\ContactDocuments\ContactDocumentAuthorizer::class,
            'contact-addresses' => \Modules\Contacts\JsonApi\V1\ContactAddresses\ContactAddressAuthorizer::class,
            'contact-people' => \Modules\Contacts\JsonApi\V1\ContactPeople\ContactPersonAuthorizer::class,

            
            // Accounting Module
            'accounts' => \Modules\Accounting\JsonApi\V1\Accounts\AccountAuthorizer::class,
            'fiscal-periods' => \Modules\Accounting\JsonApi\V1\FiscalPeriods\FiscalPeriodAuthorizer::class,
            'journals' => \Modules\Accounting\JsonApi\V1\Journals\JournalAuthorizer::class,
            'journal-entries' => \Modules\Accounting\JsonApi\V1\JournalEntries\JournalEntryAuthorizer::class,
            'journal-lines' => \Modules\Accounting\JsonApi\V1\JournalLines\JournalLineAuthorizer::class,
            'exchange-rates' => \Modules\Accounting\JsonApi\V1\ExchangeRates\ExchangeRateAuthorizer::class,
            
            // Finance Module
            'bank-accounts' => \Modules\Finance\JsonApi\V1\BankAccounts\BankAccountAuthorizer::class,
            'bank-statements' => \Modules\Finance\JsonApi\V1\BankStatements\BankStatementAuthorizer::class,
            'bank-statement-lines' => \Modules\Finance\JsonApi\V1\BankStatementLines\BankStatementLineAuthorizer::class,
            'a-p-invoices' => \Modules\Finance\JsonApi\V1\APInvoices\APInvoiceAuthorizer::class,
            'a-p-invoice-lines' => \Modules\Finance\JsonApi\V1\APInvoiceLines\APInvoiceLineAuthorizer::class,
            'a-p-payments' => \Modules\Finance\JsonApi\V1\APPayments\APPaymentAuthorizer::class,
            'a-p-invoice-payments' => \Modules\Finance\JsonApi\V1\APInvoicePayments\APInvoicePaymentAuthorizer::class,
            'a-r-invoices' => \Modules\Finance\JsonApi\V1\ARInvoices\ARInvoiceAuthorizer::class,
            'a-r-invoice-lines' => \Modules\Finance\JsonApi\V1\ARInvoiceLines\ARInvoiceLineAuthorizer::class,
            'a-r-receipts' => \Modules\Finance\JsonApi\V1\ARReceipts\ARReceiptAuthorizer::class,
            'a-r-invoice-receipts' => \Modules\Finance\JsonApi\V1\ARInvoiceReceipts\ARInvoiceReceiptAuthorizer::class,
        ];
        
        return $authorizers;
    }
}
