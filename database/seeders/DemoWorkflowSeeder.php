<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Contacts\Models\Contact;
use Modules\Contacts\Models\ContactAddress;
use Modules\Contacts\Models\ContactPerson;
use Modules\Ecommerce\Models\CartItem;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Modules\Sales\Models\FolioSequence;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\User\Models\User;

/**
 * DemoWorkflowSeeder - Neutral demo data for the public product demo.
 *
 * Runs AFTER CleanDatabaseSeeder (roles, catalogs, folios) and
 * DemoAppSettingsSeeder (Demo Company branding). Creates persona users,
 * a small generic catalog, company contacts with different credit setups,
 * and in-flight sales flows so a prospect can walk quote -> order ->
 * invoice in about five minutes.
 *
 * Everything here is brand-neutral on purpose: no client names, no real
 * RFCs, SKUs DEMO-### (never TEST-### which belong to CleanDatabaseSeeder).
 */
class DemoWorkflowSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'Demo2026!';

    public function run(): void
    {
        $this->command->info('Seeding demo workflow data...');

        // Commissions on for the demo so the full cotizacion -> cobro -> comision
        // story plays out (the generic seeder ships it off by default).
        \Modules\AppConfig\Models\AppSetting::updateOrCreate(
            ['key' => 'commissions.enabled'],
            ['value' => 'true', 'type' => 'boolean', 'group' => 'commissions', 'label' => 'Comisiones activas']
        );

        $users = $this->seedPersonaUsers();
        $products = $this->seedCatalog();
        $contacts = $this->seedContacts($users['vendedor']);
        $this->seedQuotes($contacts, $products);
        $this->seedSalesOrders($contacts, $products, $users['vendedor']);
        $this->seedShoppingCart($users['cliente'], $products);

        $this->command->info('Demo workflow data ready.');
        $this->command->info('  Personas: admin@demo.mx / vendedor@demo.mx / cliente@demo.mx (password: ' . self::DEMO_PASSWORD . ')');
    }

    /**
     * Persona users a prospect can log in with (roles from CleanRolesAndPermissionsSeeder).
     *
     * @return array<string, User>
     */
    private function seedPersonaUsers(): array
    {
        $personas = [
            'admin' => ['email' => 'admin@demo.mx', 'name' => 'Ana Administradora', 'role' => 'admin'],
            'vendedor' => ['email' => 'vendedor@demo.mx', 'name' => 'Victor Vendedor', 'role' => 'tech'],
            'cliente' => ['email' => 'cliente@demo.mx', 'name' => 'Carla Cliente', 'role' => 'customer'],
        ];

        $users = [];
        foreach ($personas as $key => $persona) {
            $user = User::firstOrCreate(
                ['email' => $persona['email']],
                [
                    'name' => $persona['name'],
                    'password' => self::DEMO_PASSWORD, // hashed by the model cast
                    'status' => 'active',
                ]
            );

            if (!$user->hasRole($persona['role'])) {
                $user->assignRole($persona['role']);
            }

            $users[$key] = $user;
        }

        // The salesperson earns a 5% commission on collected sales.
        $users['vendedor']->forceFill(['commission_pct' => 5.0])->save();

        $this->command->info('  - 3 persona users (admin/vendedor/cliente @demo.mx)');

        return $users;
    }

    /**
     * Generic catalog: 3 categories, 3 brands, 14 products (SKU DEMO-001..014).
     *
     * @return array<string, Product> keyed by SKU
     */
    private function seedCatalog(): array
    {
        $categories = [];
        foreach ([
            'equipos' => ['name' => 'Equipos', 'description' => 'Equipos y maquinaria para operacion diaria'],
            'consumibles' => ['name' => 'Consumibles', 'description' => 'Material de consumo recurrente'],
            'accesorios' => ['name' => 'Accesorios', 'description' => 'Accesorios y refacciones compatibles'],
        ] as $slug => $data) {
            $categories[$slug] = Category::firstOrCreate(
                ['slug' => $slug],
                $data + ['is_active' => true]
            );
        }

        $brands = [];
        foreach (['ProLine', 'EcoMax', 'Nivex'] as $name) {
            $brands[$name] = Brand::firstOrCreate(
                ['name' => $name],
                ['description' => "Marca generica de demostracion {$name}", 'is_active' => true]
            );
        }

        // Units seeded by CleanCatalogsSeeder (SAT codes); fall back to piece.
        $piece = Unit::where('code', 'H87')->first() ?? Unit::factory()->create(['name' => 'Pieza', 'code' => 'H87']);
        $liter = Unit::where('code', 'LTR')->first() ?? $piece;
        $kilo = Unit::where('code', 'KGM')->first() ?? $piece;

        // [sku, name, category, brand, unit, price, iva, on_sale]
        $catalog = [
            ['DEMO-001', 'Mezcladora industrial 20L', 'equipos', 'ProLine', $piece, 12500.00, true, false],
            ['DEMO-002', 'Bomba dosificadora digital', 'equipos', 'ProLine', $piece, 8790.00, true, false],
            ['DEMO-003', 'Compresor silencioso 50L', 'equipos', 'EcoMax', $piece, 15900.00, true, true],
            ['DEMO-004', 'Bascula de precision 30kg', 'equipos', 'Nivex', $piece, 4350.00, true, false],
            ['DEMO-005', 'Horno de secado compacto', 'equipos', 'EcoMax', $piece, 22400.00, false, false],
            ['DEMO-006', 'Solvente multiusos 5L', 'consumibles', 'EcoMax', $liter, 385.00, true, false],
            ['DEMO-007', 'Desengrasante biodegradable 10L', 'consumibles', 'EcoMax', $liter, 720.00, true, true],
            ['DEMO-008', 'Lubricante grado alimenticio 1L', 'consumibles', 'ProLine', $liter, 265.00, true, false],
            ['DEMO-009', 'Resina epoxica bicomponente 4kg', 'consumibles', 'Nivex', $kilo, 1180.00, true, false],
            ['DEMO-010', 'Guantes de nitrilo caja 100', 'consumibles', 'Nivex', $piece, 189.00, false, false],
            ['DEMO-011', 'Manguera reforzada 10m', 'accesorios', 'ProLine', $piece, 540.00, true, false],
            ['DEMO-012', 'Kit de conexiones rapidas', 'accesorios', 'Nivex', $piece, 830.00, true, false],
            ['DEMO-013', 'Filtro de particulas HEPA', 'accesorios', 'EcoMax', $piece, 1450.00, false, false],
            ['DEMO-014', 'Valvula de seguridad 1/2"', 'accesorios', 'ProLine', $piece, 320.00, true, false],
        ];

        $products = [];
        foreach ($catalog as [$sku, $name, $category, $brand, $unit, $price, $iva, $onSale]) {
            $existing = Product::where('sku', $sku)->first();
            if ($existing) {
                $products[$sku] = $existing;
                continue;
            }

            $products[$sku] = Product::factory()->create([
                'sku' => $sku,
                'name' => $name,
                'description' => "Producto de demostracion: {$name}.",
                'full_description' => "Descripcion extendida de {$name}. Datos genericos para el demo del producto.",
                'price' => $price,
                'cost' => round($price * 0.62, 2),
                'iva' => $iva,
                'is_on_sale' => $onSale,
                'compare_at_price' => $onSale ? round($price * 1.2, 2) : null,
                'sale_starts_at' => $onSale ? now()->subDays(7) : null,
                'sale_ends_at' => $onSale ? now()->addDays(30) : null,
                'category_id' => $categories[$category]->id,
                'brand_id' => $brands[$brand]->id,
                'unit_id' => $unit->id,
                'img_path' => null,
                'datasheet_path' => null,
                'is_active' => true,
                'average_rating' => null,
                'total_reviews' => 0,
                'total_sales' => 0,
            ]);
        }

        $this->command->info('  - 3 categories, 3 brands, ' . count($products) . ' products (DEMO-001..014)');

        return $products;
    }

    /**
     * Four company contacts with different credit setups (one on credit hold).
     *
     * @return array<int, Contact>
     */
    private function seedContacts(User $vendedor): array
    {
        $companies = [
            [
                'name' => 'Comercial del Centro',
                'legal_name' => 'Comercial del Centro SA de CV',
                'tax_id' => 'CCE850101AB1',
                'email' => 'compras@comercialcentro.demo',
                'credit_limit' => 100000.00,
                'current_credit' => 24500.00,
                'payment_terms' => 30,
                'classification' => 'premium',
                'credit_status' => 'active',
                'state' => 'Ciudad de México',
                'person' => ['name' => 'Laura Mendez', 'position' => 'Compradora', 'department' => 'Compras'],
            ],
            [
                'name' => 'Distribuidora del Norte',
                'legal_name' => 'Distribuidora del Norte SA de CV',
                'tax_id' => 'DNO900215CD2',
                'email' => 'admin@disnorte.demo',
                'credit_limit' => 50000.00,
                'current_credit' => 8200.00,
                'payment_terms' => 15,
                'classification' => 'standard',
                'credit_status' => 'active',
                'state' => 'Nuevo León',
                'person' => ['name' => 'Jorge Salinas', 'position' => 'Gerente General', 'department' => 'Administración'],
            ],
            [
                'name' => 'Servicios Industriales del Bajio',
                'legal_name' => 'Servicios Industriales del Bajio SA de CV',
                'tax_id' => 'SIB080730EF3',
                'email' => 'contacto@sibajio.demo',
                'credit_limit' => 25000.00,
                'current_credit' => 23800.00,
                'payment_terms' => 30,
                'classification' => 'standard',
                'credit_status' => 'hold', // demo: credit hold scenario
                'state' => 'Guanajuato',
                'person' => ['name' => 'Patricia Rios', 'position' => 'Contador', 'department' => 'Contabilidad'],
            ],
            [
                'name' => 'Grupo Maquila del Pacifico',
                'legal_name' => 'Grupo Maquila del Pacifico SA de CV',
                'tax_id' => 'GMP121105GH4',
                'email' => 'ventas@gmpacifico.demo',
                'credit_limit' => 0.00, // cash-only customer
                'current_credit' => 0.00,
                'payment_terms' => 0,
                'classification' => 'basic',
                'credit_status' => 'active',
                'state' => 'Jalisco',
                'person' => ['name' => 'Raul Camacho', 'position' => 'Comprador', 'department' => 'Compras'],
            ],
        ];

        $contacts = [];
        foreach ($companies as $company) {
            $existing = Contact::where('tax_id', $company['tax_id'])->first();
            if ($existing) {
                $contacts[] = $existing;
                continue;
            }

            $contact = Contact::create([
                'contact_type' => 'company',
                'name' => $company['name'],
                'legal_name' => $company['legal_name'],
                'tax_id' => $company['tax_id'],
                'email' => $company['email'],
                'phone' => '55 5555 01' . str_pad((string) (count($contacts) + 10), 2, '0', STR_PAD_LEFT),
                'status' => 'active',
                'is_customer' => true,
                'is_supplier' => false,
                'credit_limit' => $company['credit_limit'],
                'current_credit' => $company['current_credit'],
                'credit_status' => $company['credit_status'],
                'credit_hold_at' => $company['credit_status'] === 'hold' ? now()->subDays(5) : null,
                'credit_hold_reason' => $company['credit_status'] === 'hold'
                    ? 'Limite de credito casi agotado; pendiente pago de factura anterior.'
                    : null,
                'classification' => $company['classification'],
                'payment_terms' => $company['payment_terms'],
                'default_salesperson_id' => $vendedor->id,
                'notes' => 'Contacto de demostracion generado por DemoWorkflowSeeder.',
            ]);

            ContactAddress::factory()->create([
                'contact_id' => $contact->id,
                'address_type' => 'both',
                'state' => $company['state'],
                'is_default' => true,
                'metadata' => null,
            ]);

            ContactPerson::factory()->create([
                'contact_id' => $contact->id,
                'name' => $company['person']['name'],
                'position' => $company['person']['position'],
                'department' => $company['person']['department'],
                'email' => $company['email'],
                'is_primary' => true,
                'metadata' => null,
            ]);

            $contacts[] = $contact;
        }

        $this->command->info('  - 4 company contacts (1 on credit hold) with addresses and persons');

        return $contacts;
    }

    /**
     * Quotes in real workflow states: 2 draft, 1 sent, 1 accepted.
     * Folios autogenerate via FolioSequence (quote_number => null).
     *
     * @param array<int, Contact> $contacts
     * @param array<string, Product> $products
     */
    private function seedQuotes(array $contacts, array $products): void
    {
        $plans = [
            ['state' => 'draft', 'contact' => 0, 'skus' => ['DEMO-001' => 1, 'DEMO-006' => 4]],
            ['state' => 'draft', 'contact' => 3, 'skus' => ['DEMO-010' => 10, 'DEMO-014' => 6]],
            ['state' => 'sent', 'contact' => 1, 'skus' => ['DEMO-003' => 1, 'DEMO-011' => 2, 'DEMO-013' => 2]],
            ['state' => 'accepted', 'contact' => 0, 'skus' => ['DEMO-002' => 2, 'DEMO-007' => 3]],
        ];

        foreach ($plans as $plan) {
            $quote = Quote::factory()
                ->{$plan['state']}()
                ->create([
                    'contact_id' => $contacts[$plan['contact']]->id,
                    'quote_number' => null, // autogenerated: COT-yy-#####
                    'currency' => 'MXN',
                    'notes' => 'Cotizacion de demostracion.',
                    'subtotal_amount' => 0,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'total_amount' => 0,
                ]);

            foreach ($plan['skus'] as $sku => $qty) {
                $product = $products[$sku];

                // QuoteItem::saving computes line totals; saved recalculates the quote.
                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                    'quoted_price' => $product->price,
                    'discount_percentage' => 0,
                    'tax_rate' => $product->iva ? 16 : 0,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                ]);
            }
        }

        $this->command->info('  - 4 quotes (2 draft, 1 sent, 1 accepted) with items and folios COT');
    }

    /**
     * Sales orders: 1 confirmed ready to invoice, 1 delivered.
     * Folios via FolioSequence sales_order (OV prefix, seeded by CleanCatalogsSeeder).
     *
     * @param array<int, Contact> $contacts
     * @param array<string, Product> $products
     */
    private function seedSalesOrders(array $contacts, array $products, User $vendedor): void
    {
        $orders = [
            [
                'status' => 'confirmed',
                'contact' => 1,
                'order_date' => now()->subDays(3),
                'approved_at' => now()->subDays(2),
                'delivered_at' => null,
                'skus' => ['DEMO-004' => 2, 'DEMO-009' => 5, 'DEMO-012' => 1],
                'notes' => 'Orden confirmada, lista para facturar.',
            ],
            [
                'status' => 'delivered',
                'contact' => 0,
                'order_date' => now()->subDays(12),
                'approved_at' => now()->subDays(11),
                'delivered_at' => now()->subDays(6),
                'skus' => ['DEMO-005' => 1, 'DEMO-008' => 12],
                'notes' => 'Orden entregada al cliente.',
            ],
        ];

        foreach ($orders as $plan) {
            $subtotal = 0.0;
            $tax = 0.0;
            $lines = [];

            foreach ($plan['skus'] as $sku => $qty) {
                $product = $products[$sku];
                $lineSubtotal = round($product->price * $qty, 2);
                $lineTax = $product->iva ? round($lineSubtotal * 0.16, 2) : 0.0;

                $subtotal += $lineSubtotal;
                $tax += $lineTax;
                $lines[] = [$product, $qty, $lineSubtotal];
            }

            $order = SalesOrder::create([
                'contact_id' => $contacts[$plan['contact']]->id,
                'assigned_to' => $vendedor->id, // salesperson for commission
                'order_number' => FolioSequence::getNextFolio('sales_order'), // OV-yy-#####
                'status' => $plan['status'],
                'order_date' => $plan['order_date']->format('Y-m-d'),
                'approved_at' => $plan['approved_at'],
                'delivered_at' => $plan['delivered_at'],
                'subtotal' => round($subtotal, 2),
                'discount_total' => 0,
                'tax_amount' => round($tax, 2),
                'total_amount' => round($subtotal + $tax, 2),
                'currency' => 'MXN',
                'notes' => $plan['notes'],
            ]);

            foreach ($lines as [$product, $qty, $lineSubtotal]) {
                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                    'discount' => 0,
                    'total' => $lineSubtotal,
                ]);
            }
        }

        $this->command->info('  - 2 sales orders (1 confirmed ready to invoice, 1 delivered) with folios OV');
    }

    /**
     * Active shopping cart for cliente@demo.mx with a couple of items.
     */
    private function seedShoppingCart(User $cliente, array $products): void
    {
        $items = ['DEMO-006' => 2, 'DEMO-010' => 3, 'DEMO-013' => 1];

        $subtotal = 0.0;
        $tax = 0.0;
        $lines = [];

        foreach ($items as $sku => $qty) {
            $product = $products[$sku];
            $lineSubtotal = round($product->price * $qty, 2);
            $lineTaxRate = $product->iva ? 16.0 : 0.0;
            $lineTax = round($lineSubtotal * $lineTaxRate / 100, 2);

            $subtotal += $lineSubtotal;
            $tax += $lineTax;
            $lines[] = [$product, $qty, $lineSubtotal, $lineTaxRate, $lineTax];
        }

        $cart = ShoppingCart::create([
            'user_id' => $cliente->id,
            'status' => 'active',
            'expires_at' => now()->addDays(30),
            'currency' => 'MXN',
            'discount_amount' => 0,
            'tax_amount' => round($tax, 2),
            'shipping_amount' => 0,
            'total_amount' => round($subtotal + $tax, 2),
        ]);

        foreach ($lines as [$product, $qty, $lineSubtotal, $lineTaxRate, $lineTax]) {
            CartItem::create([
                'shopping_cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $qty,
                'unit_price' => $product->price,
                'original_price' => $product->price,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'subtotal' => $lineSubtotal,
                'tax_rate' => $lineTaxRate,
                'tax_amount' => $lineTax,
                'total' => round($lineSubtotal + $lineTax, 2),
                'status' => 'active',
            ]);
        }

        $this->command->info('  - 1 active shopping cart for cliente@demo.mx (3 items)');
    }
}
