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
            PublicProductSchema::class,
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
            SupplierSchema::class,
            PurchaseOrderSchema::class,
            PurchaseOrderItemSchema::class,

            // Sales Module
            \Modules\Sales\JsonApi\V1\SalesOrders\SalesOrderSchema::class,
            \Modules\Sales\JsonApi\V1\Customers\CustomerSchema::class,
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

        ];
        
        return $schemas;
    }

    protected function authorizers(): array
    {
        $authorizers = [
            'audits' => AuditAuthorizer::class,
            'products' => \Modules\Product\JsonApi\V1\Products\ProductAuthorizer::class,
            'public-products' => \Modules\Product\JsonApi\V1\PublicProducts\PublicProductAuthorizer::class,
            'units' => \Modules\Product\JsonApi\V1\Units\UnitAuthorizer::class,
            'categories' => \Modules\Product\JsonApi\V1\Categories\CategoryAuthorizer::class,
            'brands' => \Modules\Product\JsonApi\V1\Brands\BrandAuthorizer::class,
            'warehouses' => \Modules\Inventory\JsonApi\V1\Warehouses\WarehouseAuthorizer::class,
            'warehouse-locations' => \Modules\Inventory\JsonApi\V1\WarehouseLocations\WarehouseLocationAuthorizer::class,
            'stocks' => \Modules\Inventory\JsonApi\V1\Stocks\StockAuthorizer::class,
            'product-batches' => \Modules\Inventory\JsonApi\V1\ProductBatches\ProductBatchAuthorizer::class,
            'inventory-movements' => \Modules\Inventory\JsonApi\V1\InventoryMovements\InventoryMovementAuthorizer::class,
            'suppliers' => \Modules\Purchase\JsonApi\V1\Suppliers\SupplierAuthorizer::class,
            'purchase-orders' => \Modules\Purchase\JsonApi\V1\PurchaseOrders\PurchaseOrderAuthorizer::class,
            'purchase-order-items' => \Modules\Purchase\JsonApi\V1\PurchaseOrderItems\PurchaseOrderItemAuthorizer::class,
            'sales-orders' => \Modules\Sales\JsonApi\V1\SalesOrders\SalesOrderAuthorizer::class,
            'customers' => \Modules\Sales\JsonApi\V1\Customers\CustomersAuthorizer::class,
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
        ];
        
        return $authorizers;
    }
}
