<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VerifyMigrationIntegrity extends Command
{
    protected $signature = 'system:verify-migration {--fix : Attempt to fix issues found}';

    protected $description = 'Verify data integrity after migration to the new system';

    private array $issues = [];
    private array $warnings = [];

    public function handle(): int
    {
        $this->info('Verificando integridad de datos...');
        $this->newLine();

        // Core tables
        $this->verifyUsers();
        $this->verifyContacts();
        $this->verifyProducts();
        $this->verifyCategories();
        $this->verifyBrands();

        // Inventory
        $this->verifyWarehouses();
        $this->verifyStock();

        // Transactions
        $this->verifySalesOrders();
        $this->verifyPurchaseOrders();
        $this->verifyQuotes();

        // Relationships
        $this->verifyForeignKeys();

        // Report
        $this->reportResults();

        return count($this->issues) > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function verifyUsers(): void
    {
        $this->info('Verificando usuarios...');

        $count = DB::table('users')->count();
        $this->line("  Total usuarios: {$count}");

        // Check for users without roles
        $usersWithoutRoles = DB::table('users')
            ->leftJoin('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
            })
            ->whereNull('model_has_roles.role_id')
            ->count();

        if ($usersWithoutRoles > 0) {
            $this->warnings[] = "Hay {$usersWithoutRoles} usuarios sin roles asignados";
        }

        // Check for duplicate emails
        $duplicateEmails = DB::table('users')
            ->select('email', DB::raw('COUNT(*) as count'))
            ->groupBy('email')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicateEmails as $dup) {
            $this->issues[] = "Email duplicado: {$dup->email} ({$dup->count} veces)";
        }
    }

    private function verifyContacts(): void
    {
        $this->info('Verificando contactos...');

        $total = DB::table('contacts')->count();
        $customers = DB::table('contacts')->where('is_customer', true)->count();
        $suppliers = DB::table('contacts')->where('is_supplier', true)->count();

        $this->line("  Total contactos: {$total}");
        $this->line("  Clientes: {$customers}");
        $this->line("  Proveedores: {$suppliers}");

        // Check for contacts without type
        $noType = DB::table('contacts')
            ->where('is_customer', false)
            ->where('is_supplier', false)
            ->count();

        if ($noType > 0) {
            $this->warnings[] = "Hay {$noType} contactos sin tipo (ni cliente ni proveedor)";
        }

        // Check for RFC duplicates (if required)
        $duplicateRfc = DB::table('contacts')
            ->select('tax_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('tax_id')
            ->where('tax_id', '!=', '')
            ->groupBy('tax_id')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicateRfc as $dup) {
            $this->warnings[] = "RFC duplicado: {$dup->tax_id} ({$dup->count} contactos)";
        }
    }

    private function verifyProducts(): void
    {
        $this->info('Verificando productos...');

        $total = DB::table('products')->count();
        $active = DB::table('products')->where('is_active', true)->count();

        $this->line("  Total productos: {$total}");
        $this->line("  Activos: {$active}");

        // Check for products without category
        $noCategory = DB::table('products')->whereNull('category_id')->count();
        if ($noCategory > 0) {
            $this->warnings[] = "Hay {$noCategory} productos sin categoria";
        }

        // Check for products without brand
        $noBrand = DB::table('products')->whereNull('brand_id')->count();
        if ($noBrand > 0) {
            $this->warnings[] = "Hay {$noBrand} productos sin marca";
        }

        // Check for duplicate SKUs
        $duplicateSkus = DB::table('products')
            ->select('sku', DB::raw('COUNT(*) as count'))
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->groupBy('sku')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicateSkus as $dup) {
            $this->issues[] = "SKU duplicado: {$dup->sku} ({$dup->count} productos)";
        }

        // Check for products with zero or null price
        $noPrice = DB::table('products')
            ->where(function ($q) {
                $q->whereNull('price')->orWhere('price', '<=', 0);
            })
            ->where('is_active', true)
            ->count();

        if ($noPrice > 0) {
            $this->warnings[] = "Hay {$noPrice} productos activos sin precio";
        }
    }

    private function verifyCategories(): void
    {
        $this->info('Verificando categorias...');

        $total = DB::table('categories')->count();
        $this->line("  Total categorias: {$total}");

        // Check for orphan categories (parent doesn't exist)
        if (Schema::hasColumn('categories', 'parent_id')) {
            $orphans = DB::table('categories as c')
                ->leftJoin('categories as p', 'c.parent_id', '=', 'p.id')
                ->whereNotNull('c.parent_id')
                ->whereNull('p.id')
                ->count();

            if ($orphans > 0) {
                $this->issues[] = "Hay {$orphans} categorias con parent_id invalido";
            }
        }
    }

    private function verifyBrands(): void
    {
        $this->info('Verificando marcas...');

        $total = DB::table('brands')->count();
        $withLeadTime = DB::table('brands')->whereNotNull('default_lead_time')->count();

        $this->line("  Total marcas: {$total}");
        $this->line("  Con tiempo de entrega: {$withLeadTime}");
    }

    private function verifyWarehouses(): void
    {
        $this->info('Verificando almacenes...');

        $total = DB::table('warehouses')->count();
        $this->line("  Total almacenes: {$total}");

        if ($total === 0) {
            $this->warnings[] = "No hay almacenes configurados";
        }

        // Check for default warehouse (if column exists)
        if (Schema::hasColumn('warehouses', 'is_default')) {
            $default = DB::table('warehouses')->where('is_default', true)->count();
            if ($default === 0 && $total > 0) {
                $this->warnings[] = "No hay almacen marcado como default";
            }
        }
    }

    private function verifyStock(): void
    {
        $this->info('Verificando stock...');

        $total = DB::table('stock')->count();
        $negative = DB::table('stock')->where('quantity', '<', 0)->count();

        $this->line("  Registros de stock: {$total}");

        if ($negative > 0) {
            $this->warnings[] = "Hay {$negative} registros con stock negativo";
        }

        // Check for stock without warehouse
        $noWarehouse = DB::table('stock')->whereNull('warehouse_id')->count();
        if ($noWarehouse > 0) {
            $this->issues[] = "Hay {$noWarehouse} registros de stock sin almacen";
        }
    }

    private function verifySalesOrders(): void
    {
        $this->info('Verificando ordenes de venta...');

        $total = DB::table('sales_orders')->count();
        $this->line("  Total ordenes: {$total}");

        // Check for orders without contact
        $noContact = DB::table('sales_orders')->whereNull('contact_id')->count();
        if ($noContact > 0) {
            $this->issues[] = "Hay {$noContact} ordenes de venta sin cliente asignado";
        }

        // Check for orders without items
        $ordersWithoutItems = DB::table('sales_orders')
            ->leftJoin('sales_order_items', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->whereNull('sales_order_items.id')
            ->count();

        if ($ordersWithoutItems > 0) {
            $this->warnings[] = "Hay {$ordersWithoutItems} ordenes sin items";
        }
    }

    private function verifyPurchaseOrders(): void
    {
        $this->info('Verificando ordenes de compra...');

        if (!Schema::hasTable('purchase_orders')) {
            $this->line("  Tabla purchase_orders no existe");
            return;
        }

        $total = DB::table('purchase_orders')->count();
        $this->line("  Total ordenes: {$total}");

        // Check for orders without warehouse (new field)
        if (Schema::hasColumn('purchase_orders', 'warehouse_id')) {
            $noWarehouse = DB::table('purchase_orders')->whereNull('warehouse_id')->count();
            if ($noWarehouse > 0) {
                $this->warnings[] = "Hay {$noWarehouse} ordenes de compra sin almacen asignado";

                if ($this->option('fix')) {
                    $defaultWarehouse = DB::table('warehouses')->where('is_default', true)->first();
                    if ($defaultWarehouse) {
                        DB::table('purchase_orders')
                            ->whereNull('warehouse_id')
                            ->update(['warehouse_id' => $defaultWarehouse->id]);
                        $this->info("  -> Corregido: Asignado almacen default a ordenes sin almacen");
                    }
                }
            }
        }
    }

    private function verifyQuotes(): void
    {
        $this->info('Verificando cotizaciones...');

        if (!Schema::hasTable('quotes')) {
            $this->line("  Tabla quotes no existe (normal si es sistema nuevo)");
            return;
        }

        $total = DB::table('quotes')->count();
        $byStatus = DB::table('quotes')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->line("  Total cotizaciones: {$total}");
        foreach ($byStatus as $status => $count) {
            $this->line("    - {$status}: {$count}");
        }

        // Check for expired quotes not marked
        $expired = DB::table('quotes')
            ->where('valid_until', '<', now())
            ->where('status', 'sent')
            ->count();

        if ($expired > 0) {
            $this->warnings[] = "Hay {$expired} cotizaciones expiradas aun marcadas como 'sent'";
        }
    }

    private function verifyForeignKeys(): void
    {
        $this->info('Verificando integridad referencial...');

        // Products -> Categories
        $invalidCategory = DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereNotNull('products.category_id')
            ->whereNull('categories.id')
            ->count();

        if ($invalidCategory > 0) {
            $this->issues[] = "Hay {$invalidCategory} productos con category_id invalido";
        }

        // Products -> Brands
        $invalidBrand = DB::table('products')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->whereNotNull('products.brand_id')
            ->whereNull('brands.id')
            ->count();

        if ($invalidBrand > 0) {
            $this->issues[] = "Hay {$invalidBrand} productos con brand_id invalido";
        }

        // Sales Orders -> Contacts
        $invalidContact = DB::table('sales_orders')
            ->leftJoin('contacts', 'sales_orders.contact_id', '=', 'contacts.id')
            ->whereNotNull('sales_orders.contact_id')
            ->whereNull('contacts.id')
            ->count();

        if ($invalidContact > 0) {
            $this->issues[] = "Hay {$invalidContact} ordenes de venta con contact_id invalido";
        }
    }

    private function reportResults(): void
    {
        $this->newLine();
        $this->line('========================================');
        $this->info('RESUMEN DE VERIFICACION');
        $this->line('========================================');

        if (count($this->issues) === 0 && count($this->warnings) === 0) {
            $this->info('Todo OK - No se encontraron problemas');
            return;
        }

        if (count($this->issues) > 0) {
            $this->newLine();
            $this->error('ERRORES CRITICOS (' . count($this->issues) . '):');
            foreach ($this->issues as $issue) {
                $this->line("  [X] {$issue}");
            }
        }

        if (count($this->warnings) > 0) {
            $this->newLine();
            $this->warn('ADVERTENCIAS (' . count($this->warnings) . '):');
            foreach ($this->warnings as $warning) {
                $this->line("  [!] {$warning}");
            }
        }

        $this->newLine();
        if (count($this->issues) > 0) {
            $this->error('La migracion tiene errores criticos que deben corregirse');
        } else {
            $this->info('La migracion tiene advertencias menores pero puede continuar');
        }
    }
}
