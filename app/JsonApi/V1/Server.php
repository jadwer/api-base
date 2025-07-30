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
use Modules\Purchase\JsonApi\V1\Suppliers\SupplierSchema;
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

            // Purchase Module
            SupplierSchema::class,
            PurchaseOrderSchema::class,
            PurchaseOrderItemSchema::class,

            // Sales Module
            \Modules\Sales\JsonApi\V1\SalesOrders\SalesOrderSchema::class,
            \Modules\Sales\JsonApi\V1\Customers\CustomerSchema::class,
            \Modules\Sales\JsonApi\V1\SalesOrderItems\SalesOrderItemSchema::class,
        ];
        
        // 🧪 TESTING MODULES: Auto-register schemas if they exist
        // This prevents errors when modules are deleted/regenerated during development
        // To add new test modules, just add them to this conditional block
        $testSchemas = [];
        
        // Ecommerce Module (Testing)
        if (class_exists('\Modules\Ecommerce\JsonApi\V1\ShoppingCarts\ShoppingCartSchema')) {
            $testSchemas[] = \Modules\Ecommerce\JsonApi\V1\ShoppingCarts\ShoppingCartSchema::class;
        }
        if (class_exists('\Modules\Ecommerce\JsonApi\V1\CartItems\CartItemSchema')) {
            $testSchemas[] = \Modules\Ecommerce\JsonApi\V1\CartItems\CartItemSchema::class;
        }
        if (class_exists('\Modules\Ecommerce\JsonApi\V1\Coupons\CouponSchema')) {
            $testSchemas[] = \Modules\Ecommerce\JsonApi\V1\Coupons\CouponSchema::class;
        }
        
        return array_merge($schemas, $testSchemas);
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
            'suppliers' => \Modules\Purchase\JsonApi\V1\Suppliers\SupplierAuthorizer::class,
            'purchase-orders' => \Modules\Purchase\JsonApi\V1\PurchaseOrders\PurchaseOrderAuthorizer::class,
            'purchase-order-items' => \Modules\Purchase\JsonApi\V1\PurchaseOrderItems\PurchaseOrderItemAuthorizer::class,
            'sales-orders' => \Modules\Sales\JsonApi\V1\SalesOrders\SalesOrderAuthorizer::class,
            'customers' => \Modules\Sales\JsonApi\V1\Customers\CustomersAuthorizer::class,
            'sales-order-items' => \Modules\Sales\JsonApi\V1\SalesOrderItems\SalesOrderItemAuthorizer::class,
        ];
        
        // 🧪 TESTING MODULES: Auto-register authorizers if they exist
        // This prevents errors when modules are deleted/regenerated during development
        // To add new test modules, just add them to this conditional block
        
        // Ecommerce Module (Testing)
        if (class_exists('\Modules\Ecommerce\JsonApi\V1\ShoppingCarts\ShoppingCartAuthorizer')) {
            $authorizers['shopping-carts'] = \Modules\Ecommerce\JsonApi\V1\ShoppingCarts\ShoppingCartAuthorizer::class;
        }
        if (class_exists('\Modules\Ecommerce\JsonApi\V1\CartItems\CartItemAuthorizer')) {
            $authorizers['cart-items'] = \Modules\Ecommerce\JsonApi\V1\CartItems\CartItemAuthorizer::class;
        }
        if (class_exists('\Modules\Ecommerce\JsonApi\V1\Coupons\CouponAuthorizer')) {
            $authorizers['coupons'] = \Modules\Ecommerce\JsonApi\V1\Coupons\CouponAuthorizer::class;
        }
        
        return $authorizers;
    }
}
