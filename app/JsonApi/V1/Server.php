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
use Modules\Inventory\JsonApi\V1\CycleCounts\CycleCountSchema;
use Modules\Inventory\JsonApi\V1\CycleCounts\CycleCountAuthorizer;
use Modules\Purchase\JsonApi\V1\PurchaseOrders\PurchaseOrderSchema;
use Modules\Purchase\JsonApi\V1\PurchaseOrderItems\PurchaseOrderItemSchema;
use Modules\User\JsonApi\V1\Users\UserSchema;
use Modules\PageBuilder\JsonApi\V1\Pages\PageSchema;
use Modules\PermissionManager\JsonApi\V1\Permissions\PermissionSchema;
use Modules\PermissionManager\JsonApi\V1\Roles\RoleSchema;
use Modules\Product\JsonApi\V1\Products\ProductSchema;
use Modules\Product\JsonApi\V1\Units\UnitSchema;
use Modules\Product\JsonApi\V1\Categories\CategorySchema;
use Modules\Product\JsonApi\V1\Brands\BrandSchema;
use Modules\Ecommerce\JsonApi\V1\ShoppingCarts\ShoppingCartSchema;
use Modules\Ecommerce\JsonApi\V1\CartItems\CartItemSchema;
use Modules\Ecommerce\JsonApi\V1\Coupons\CouponSchema;
use Modules\Ecommerce\JsonApi\V1\ProductReviews\ProductReviewSchema;
use Modules\Ecommerce\JsonApi\V1\Wishlists\WishlistSchema;
use Modules\Ecommerce\JsonApi\V1\WishlistItems\WishlistItemSchema;
use Modules\Ecommerce\JsonApi\V1\Currencies\CurrencySchema;
use Modules\Ecommerce\JsonApi\V1\ProductComparisons\ProductComparisonSchema;
use Modules\Ecommerce\JsonApi\V1\ProductComparisonItems\ProductComparisonItemSchema;
use Modules\Ecommerce\JsonApi\V1\ProductQuestions\ProductQuestionSchema;
use Modules\Ecommerce\JsonApi\V1\ProductAnswers\ProductAnswerSchema;
use Modules\Contacts\JsonApi\V1\Contacts\ContactSchema;
use Modules\Contacts\JsonApi\V1\ContactDocuments\ContactDocumentSchema;
use Modules\Contacts\JsonApi\V1\ContactAddresses\ContactAddressSchema;
use Modules\Contacts\JsonApi\V1\ContactPeople\ContactPersonSchema;
use Modules\Accounting\JsonApi\V1\IdempotencyKeys\IdempotencyKeySchema;
use Modules\Accounting\JsonApi\V1\AccountMappings\AccountMappingSchema;
use Modules\Accounting\JsonApi\V1\AccountBalances\AccountBalanceSchema;
use Modules\Accounting\JsonApi\V1\ExchangeRatePolicies\ExchangeRatePolicySchema;
use Modules\Accounting\JsonApi\V1\AuditLogs\AuditLogSchema;
use Modules\Accounting\JsonApi\V1\Accounts\AccountSchema;
use Modules\Accounting\JsonApi\V1\FiscalPeriods\FiscalPeriodSchema;
use Modules\Accounting\JsonApi\V1\Journals\JournalSchema;
use Modules\Accounting\JsonApi\V1\JournalSequences\JournalSequenceSchema;
use Modules\Accounting\JsonApi\V1\JournalEntries\JournalEntrySchema;
use Modules\Accounting\JsonApi\V1\JournalLines\JournalLineSchema;
use Modules\Accounting\JsonApi\V1\ExchangeRates\ExchangeRateSchema;
use Modules\Finance\JsonApi\V1\ARInvoices\ARInvoiceSchema;
use Modules\Finance\JsonApi\V1\APInvoices\APInvoiceSchema;
use Modules\Finance\JsonApi\V1\Payments\PaymentSchema;
use Modules\Finance\JsonApi\V1\PaymentApplications\PaymentApplicationSchema;
use Modules\Finance\JsonApi\V1\BankAccounts\BankAccountSchema;
use Modules\Finance\JsonApi\V1\PaymentMethods\PaymentMethodSchema;
use Modules\Finance\JsonApi\V1\ARPayments\ARPaymentSchema;
use Modules\Reports\JsonApi\V1\BalanceSheets\BalanceSheetSchema;
use Modules\Reports\JsonApi\V1\IncomeStatements\IncomeStatementSchema;
use Modules\Reports\JsonApi\V1\CashFlows\CashFlowSchema;
use Modules\Reports\JsonApi\V1\TrialBalances\TrialBalanceSchema;
use Modules\Reports\JsonApi\V1\ARAgingReports\ARAgingReportSchema;
use Modules\Reports\JsonApi\V1\APAgingReports\APAgingReportSchema;
use Modules\Reports\JsonApi\V1\SalesByCustomerReports\SalesByCustomerReportSchema;
use Modules\Reports\JsonApi\V1\SalesByProductReports\SalesByProductReportSchema;
use Modules\Reports\JsonApi\V1\PurchaseBySupplierReports\PurchaseBySupplierReportSchema;
use Modules\Reports\JsonApi\V1\PurchaseByProductReports\PurchaseByProductReportSchema;

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
            \Modules\Product\JsonApi\V1\VariantAttributes\VariantAttributeSchema::class,
            \Modules\Product\JsonApi\V1\VariantAttributeValues\VariantAttributeValueSchema::class,
            \Modules\Product\JsonApi\V1\ProductVariants\ProductVariantSchema::class,

            // Inventory Module
            WarehouseSchema::class,
            WarehouseLocationSchema::class,
            StockSchema::class,
            ProductBatchSchema::class,
            InventoryMovementSchema::class,
            CycleCountSchema::class,

            // Purchase Module
            PurchaseOrderSchema::class,
            PurchaseOrderItemSchema::class,
            \Modules\Purchase\JsonApi\V1\Budgets\BudgetSchema::class,

            // Sales Module
            \Modules\Sales\JsonApi\V1\SalesOrders\SalesOrderSchema::class,
            \Modules\Sales\JsonApi\V1\SalesOrderItems\SalesOrderItemSchema::class,
            \Modules\Sales\JsonApi\V1\Shipments\ShipmentSchema::class,
            \Modules\Sales\JsonApi\V1\ShipmentItems\ShipmentItemSchema::class,
            \Modules\Sales\JsonApi\V1\Backorders\BackorderSchema::class,
            \Modules\Sales\JsonApi\V1\DiscountRules\DiscountRuleSchema::class,
            \Modules\Sales\JsonApi\V1\Quotes\QuoteSchema::class,
            \Modules\Sales\JsonApi\V1\QuoteItems\QuoteItemSchema::class,
            \Modules\Sales\JsonApi\V1\Remissions\RemissionSchema::class,
            \Modules\Sales\JsonApi\V1\RemissionItems\RemissionItemSchema::class,

            // Ecommerce Module
            ShoppingCartSchema::class,
            CartItemSchema::class,
            CouponSchema::class,
            \Modules\Ecommerce\JsonApi\V1\CheckoutSessions\CheckoutSessionSchema::class,
            \Modules\Ecommerce\JsonApi\V1\InventoryReservations\InventoryReservationSchema::class,
            \Modules\Ecommerce\JsonApi\V1\ShippingMethods\ShippingMethodSchema::class,
            ProductReviewSchema::class, // Phase 4.3.1
            WishlistSchema::class, // Phase 4.3.2
            WishlistItemSchema::class, // Phase 4.3.2
            CurrencySchema::class, // Phase 4.3.4
            ProductComparisonSchema::class, // Phase 4.3.5
            ProductComparisonItemSchema::class, // Phase 4.3.5
            ProductQuestionSchema::class, // Phase 4.3.6
            ProductAnswerSchema::class, // Phase 4.3.6

            // Contacts Module
            ContactSchema::class,
            ContactDocumentSchema::class,
            ContactAddressSchema::class,
            ContactPersonSchema::class,


            // Accounting Module
            IdempotencyKeySchema::class,
            AccountMappingSchema::class,
            AccountBalanceSchema::class,
            ExchangeRatePolicySchema::class,
            AuditLogSchema::class,
            AccountSchema::class,
            FiscalPeriodSchema::class,
            JournalSchema::class,
            JournalSequenceSchema::class,
            JournalEntrySchema::class,
            JournalLineSchema::class,
            ExchangeRateSchema::class,


            // Finance Module
            ARInvoiceSchema::class,
            APInvoiceSchema::class,
            PaymentSchema::class,
            PaymentApplicationSchema::class,
            BankAccountSchema::class,
            PaymentMethodSchema::class,
            ARPaymentSchema::class,
            \Modules\Finance\JsonApi\V1\BankTransactions\BankTransactionSchema::class,

            // Reports Module (Phase 4.2 - Corrected)
            BalanceSheetSchema::class,
            IncomeStatementSchema::class,
            CashFlowSchema::class,
            TrialBalanceSchema::class,
            ARAgingReportSchema::class,
            APAgingReportSchema::class,
            SalesByCustomerReportSchema::class,
            SalesByProductReportSchema::class,
            PurchaseBySupplierReportSchema::class,
            PurchaseByProductReportSchema::class,

            // HR Module (Phase 4.4)
            \Modules\HR\JsonApi\V1\Departments\DepartmentSchema::class,
            \Modules\HR\JsonApi\V1\Positions\PositionSchema::class,
            \Modules\HR\JsonApi\V1\Employees\EmployeeSchema::class,
            \Modules\HR\JsonApi\V1\Attendances\AttendanceSchema::class,
            \Modules\HR\JsonApi\V1\LeaveTypes\LeaveTypeSchema::class,
            \Modules\HR\JsonApi\V1\Leaves\LeaveSchema::class,
            \Modules\HR\JsonApi\V1\PayrollPeriods\PayrollPeriodSchema::class,
            \Modules\HR\JsonApi\V1\PayrollItems\PayrollItemSchema::class,
            \Modules\HR\JsonApi\V1\PerformanceReviews\PerformanceReviewSchema::class,

            // Billing Module (Phase 5.1 - Stripe + CFDI)
            \Modules\Billing\JsonApi\V1\PaymentTransactions\PaymentTransactionSchema::class,
            \Modules\Billing\JsonApi\V1\CompanySettings\CompanySettingSchema::class,
            \Modules\Billing\JsonApi\V1\CFDIInvoices\CFDIInvoiceSchema::class,
            \Modules\Billing\JsonApi\V1\CFDIItems\CFDIItemSchema::class,
            \Modules\Billing\JsonApi\V1\InvoiceSeries\InvoiceSeriesSchema::class,

            // CRM Module (Phase 4.5)
            \Modules\CRM\JsonApi\V1\Leads\LeadSchema::class,
            \Modules\CRM\JsonApi\V1\Activities\ActivitySchema::class,
            \Modules\CRM\JsonApi\V1\Campaigns\CampaignSchema::class,
            \Modules\CRM\JsonApi\V1\PipelineStages\PipelineStageSchema::class,
            \Modules\CRM\JsonApi\V1\Opportunities\OpportunitySchema::class,
            // \Modules\CRM\JsonApi\V1\Quotes\QuoteSchema::class,
            // \Modules\CRM\JsonApi\V1\QuoteItems\QuoteItemSchema::class,

        ];

        return $schemas;
    }

    protected function authorizers(): array
    {
        $authorizers = [
            'audits' => AuditAuthorizer::class,
            'users' => \Modules\User\JsonApi\V1\Users\UserAuthorizer::class,
            'pages' => \Modules\PageBuilder\JsonApi\V1\Pages\PageAuthorizer::class,
            'roles' => \Modules\PermissionManager\JsonApi\V1\Roles\RoleAuthorizer::class,
            'permissions' => \Modules\PermissionManager\JsonApi\V1\Permissions\PermissionAuthorizer::class,
            'products' => \Modules\Product\JsonApi\V1\Products\ProductAuthorizer::class,
            'units' => \Modules\Product\JsonApi\V1\Units\UnitAuthorizer::class,
            'categories' => \Modules\Product\JsonApi\V1\Categories\CategoryAuthorizer::class,
            'brands' => \Modules\Product\JsonApi\V1\Brands\BrandAuthorizer::class,
            'variant-attributes' => \Modules\Product\JsonApi\V1\VariantAttributes\VariantAttributeAuthorizer::class,
            'variant-attribute-values' => \Modules\Product\JsonApi\V1\VariantAttributeValues\VariantAttributeValueAuthorizer::class,
            'product-variants' => \Modules\Product\JsonApi\V1\ProductVariants\ProductVariantAuthorizer::class,
            'warehouses' => \Modules\Inventory\JsonApi\V1\Warehouses\WarehouseAuthorizer::class,
            'warehouse-locations' => \Modules\Inventory\JsonApi\V1\WarehouseLocations\WarehouseLocationAuthorizer::class,
            'stocks' => \Modules\Inventory\JsonApi\V1\Stocks\StockAuthorizer::class,
            'product-batches' => \Modules\Inventory\JsonApi\V1\ProductBatches\ProductBatchAuthorizer::class,
            'inventory-movements' => \Modules\Inventory\JsonApi\V1\InventoryMovements\InventoryMovementAuthorizer::class,
            'cycle-counts' => CycleCountAuthorizer::class,
            'purchase-orders' => \Modules\Purchase\JsonApi\V1\PurchaseOrders\PurchaseOrderAuthorizer::class,
            'purchase-order-items' => \Modules\Purchase\JsonApi\V1\PurchaseOrderItems\PurchaseOrderItemAuthorizer::class,
            'budgets' => \Modules\Purchase\JsonApi\V1\Budgets\BudgetAuthorizer::class,
            'sales-orders' => \Modules\Sales\JsonApi\V1\SalesOrders\SalesOrderAuthorizer::class,
            'sales-order-items' => \Modules\Sales\JsonApi\V1\SalesOrderItems\SalesOrderItemAuthorizer::class,
            'shipments' => \Modules\Sales\JsonApi\V1\Shipments\ShipmentAuthorizer::class,
            'shipment-items' => \Modules\Sales\JsonApi\V1\ShipmentItems\ShipmentItemAuthorizer::class,
            'backorders' => \Modules\Sales\JsonApi\V1\Backorders\BackorderAuthorizer::class,
            'discount-rules' => \Modules\Sales\JsonApi\V1\DiscountRules\DiscountRuleAuthorizer::class,
            'quotes' => \Modules\Sales\JsonApi\V1\Quotes\QuoteAuthorizer::class,
            'quote-items' => \Modules\Sales\JsonApi\V1\QuoteItems\QuoteItemAuthorizer::class,
            'remissions' => \Modules\Sales\JsonApi\V1\Remissions\RemissionAuthorizer::class,
            'remission-items' => \Modules\Sales\JsonApi\V1\RemissionItems\RemissionItemAuthorizer::class,
            
            // Ecommerce Module
            'shopping-carts' => \Modules\Ecommerce\JsonApi\V1\ShoppingCarts\ShoppingCartAuthorizer::class,
            'cart-items' => \Modules\Ecommerce\JsonApi\V1\CartItems\CartItemAuthorizer::class,
            'coupons' => \Modules\Ecommerce\JsonApi\V1\Coupons\CouponAuthorizer::class,
            'product-reviews' => \Modules\Ecommerce\JsonApi\V1\ProductReviews\ProductReviewAuthorizer::class,
            'wishlists' => \Modules\Ecommerce\JsonApi\V1\Wishlists\WishlistAuthorizer::class,
            'wishlist-items' => \Modules\Ecommerce\JsonApi\V1\WishlistItems\WishlistItemAuthorizer::class,
            'currencies' => \Modules\Ecommerce\JsonApi\V1\Currencies\CurrencyAuthorizer::class,
            'checkout-sessions' => \Modules\Ecommerce\JsonApi\V1\CheckoutSessions\CheckoutSessionAuthorizer::class,
            'inventory-reservations' => \Modules\Ecommerce\JsonApi\V1\InventoryReservations\InventoryReservationAuthorizer::class,
            'shipping-methods' => \Modules\Ecommerce\JsonApi\V1\ShippingMethods\ShippingMethodAuthorizer::class,
            'product-comparisons' => \Modules\Ecommerce\JsonApi\V1\ProductComparisons\ProductComparisonAuthorizer::class,
            'product-comparison-items' => \Modules\Ecommerce\JsonApi\V1\ProductComparisonItems\ProductComparisonItemAuthorizer::class,
            'product-questions' => \Modules\Ecommerce\JsonApi\V1\ProductQuestions\ProductQuestionAuthorizer::class,
            'product-answers' => \Modules\Ecommerce\JsonApi\V1\ProductAnswers\ProductAnswerAuthorizer::class,

            // Contacts Module
            'contacts' => \Modules\Contacts\JsonApi\V1\Contacts\ContactAuthorizer::class,
            'contact-documents' => \Modules\Contacts\JsonApi\V1\ContactDocuments\ContactDocumentAuthorizer::class,
            'contact-addresses' => \Modules\Contacts\JsonApi\V1\ContactAddresses\ContactAddressAuthorizer::class,
            'contact-people' => \Modules\Contacts\JsonApi\V1\ContactPeople\ContactPersonAuthorizer::class,

            
            // Accounting Module
            'idempotency-keys' => \Modules\Accounting\JsonApi\V1\IdempotencyKeys\IdempotencyKeyAuthorizer::class,
            'account-mappings' => \Modules\Accounting\JsonApi\V1\AccountMappings\AccountMappingAuthorizer::class,
            'account-balances' => \Modules\Accounting\JsonApi\V1\AccountBalances\AccountBalanceAuthorizer::class,
            'exchange-rate-policies' => \Modules\Accounting\JsonApi\V1\ExchangeRatePolicies\ExchangeRatePolicyAuthorizer::class,
            'audit-logs' => \Modules\Accounting\JsonApi\V1\AuditLogs\AuditLogAuthorizer::class,
            'accounts' => \Modules\Accounting\JsonApi\V1\Accounts\AccountAuthorizer::class,
            'fiscal-periods' => \Modules\Accounting\JsonApi\V1\FiscalPeriods\FiscalPeriodAuthorizer::class,
            'journals' => \Modules\Accounting\JsonApi\V1\Journals\JournalAuthorizer::class,
            'journal-sequences' => \Modules\Accounting\JsonApi\V1\JournalSequences\JournalSequenceAuthorizer::class,
            'journal-entries' => \Modules\Accounting\JsonApi\V1\JournalEntries\JournalEntryAuthorizer::class,
            'journal-lines' => \Modules\Accounting\JsonApi\V1\JournalLines\JournalLineAuthorizer::class,
            'exchange-rates' => \Modules\Accounting\JsonApi\V1\ExchangeRates\ExchangeRateAuthorizer::class,
            
            // Finance Module
            'ar-invoices' => \Modules\Finance\JsonApi\V1\ARInvoices\ARInvoiceAuthorizer::class,
            'ap-invoices' => \Modules\Finance\JsonApi\V1\APInvoices\APInvoiceAuthorizer::class,
            'payments' => \Modules\Finance\JsonApi\V1\Payments\PaymentAuthorizer::class,
            'payment-applications' => \Modules\Finance\JsonApi\V1\PaymentApplications\PaymentApplicationAuthorizer::class,
            'bank-accounts' => \Modules\Finance\JsonApi\V1\BankAccounts\BankAccountAuthorizer::class,
            'payment-methods' => \Modules\Finance\JsonApi\V1\PaymentMethods\PaymentMethodAuthorizer::class,
            'ar-payments' => \Modules\Finance\JsonApi\V1\ARPayments\ARPaymentAuthorizer::class,
            'bank-transactions' => \Modules\Finance\JsonApi\V1\BankTransactions\BankTransactionAuthorizer::class,

            // Reports Module (Phase 4.2 - Corrected)
            'balance-sheets' => \Modules\Reports\JsonApi\V1\BalanceSheets\BalanceSheetAuthorizer::class,
            'income-statements' => \Modules\Reports\JsonApi\V1\IncomeStatements\IncomeStatementAuthorizer::class,
            'cash-flows' => \Modules\Reports\JsonApi\V1\CashFlows\CashFlowAuthorizer::class,
            'trial-balances' => \Modules\Reports\JsonApi\V1\TrialBalances\TrialBalanceAuthorizer::class,
            'ar-aging-reports' => \Modules\Reports\JsonApi\V1\ARAgingReports\ARAgingReportAuthorizer::class,
            'ap-aging-reports' => \Modules\Reports\JsonApi\V1\APAgingReports\APAgingReportAuthorizer::class,
            'sales-by-customer-reports' => \Modules\Reports\JsonApi\V1\SalesByCustomerReports\SalesByCustomerReportAuthorizer::class,
            'sales-by-product-reports' => \Modules\Reports\JsonApi\V1\SalesByProductReports\SalesByProductReportAuthorizer::class,
            'purchase-by-supplier-reports' => \Modules\Reports\JsonApi\V1\PurchaseBySupplierReports\PurchaseBySupplierReportAuthorizer::class,
            'purchase-by-product-reports' => \Modules\Reports\JsonApi\V1\PurchaseByProductReports\PurchaseByProductReportAuthorizer::class,

            // HR Module (Phase 4.4)
            'departments' => \Modules\HR\JsonApi\V1\Departments\DepartmentAuthorizer::class,
            'positions' => \Modules\HR\JsonApi\V1\Positions\PositionAuthorizer::class,
            'employees' => \Modules\HR\JsonApi\V1\Employees\EmployeeAuthorizer::class,
            'attendances' => \Modules\HR\JsonApi\V1\Attendances\AttendanceAuthorizer::class,
            'leave-types' => \Modules\HR\JsonApi\V1\LeaveTypes\LeaveTypeAuthorizer::class,
            'leaves' => \Modules\HR\JsonApi\V1\Leaves\LeaveAuthorizer::class,
            'payroll-periods' => \Modules\HR\JsonApi\V1\PayrollPeriods\PayrollPeriodAuthorizer::class,
            'payroll-items' => \Modules\HR\JsonApi\V1\PayrollItems\PayrollItemAuthorizer::class,
            'performance-reviews' => \Modules\HR\JsonApi\V1\PerformanceReviews\PerformanceReviewAuthorizer::class,

            // Billing Module (Phase 5.1 - Stripe + CFDI)
            'payment-transactions' => \Modules\Billing\JsonApi\V1\PaymentTransactions\PaymentTransactionAuthorizer::class,
            'company-settings' => \Modules\Billing\JsonApi\V1\CompanySettings\CompanySettingAuthorizer::class,
            'cfdi-invoices' => \Modules\Billing\JsonApi\V1\CFDIInvoices\CFDIInvoiceAuthorizer::class,
            'cfdi-items' => \Modules\Billing\JsonApi\V1\CFDIItems\CFDIItemAuthorizer::class,
            'invoice-series' => \Modules\Billing\JsonApi\V1\InvoiceSeries\InvoiceSeriesAuthorizer::class,

            // CRM Module (Phase 4.5)
            'leads' => \Modules\CRM\JsonApi\V1\Leads\LeadAuthorizer::class,
            'activities' => \Modules\CRM\JsonApi\V1\Activities\ActivityAuthorizer::class,
            'campaigns' => \Modules\CRM\JsonApi\V1\Campaigns\CampaignAuthorizer::class,
            'pipeline-stages' => \Modules\CRM\JsonApi\V1\PipelineStages\PipelineStageAuthorizer::class,
            'opportunities' => \Modules\CRM\JsonApi\V1\Opportunities\OpportunityAuthorizer::class,
            // 'quotes' => \Modules\CRM\JsonApi\V1\Quotes\QuoteAuthorizer::class,
            // 'quote-items' => \Modules\CRM\JsonApi\V1\QuoteItems\QuoteItemAuthorizer::class,
        ];

        return $authorizers;
    }
}
