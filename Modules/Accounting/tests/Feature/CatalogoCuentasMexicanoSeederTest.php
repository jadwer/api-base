<?php

namespace Modules\Accounting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Database\Seeders\CatalogoCuentasMexicanoSeeder;
use Modules\Accounting\Models\Account;
use Tests\TestCase;

/**
 * El catalogo se puede sembrar sobre una base donde una migracion ya creo
 * cuentas (2109, 2110 de la migracion 2026_10_31_130000, con otra empresa) y
 * tambien dos veces seguidas: `code` es unico y el seeder no debe duplicarlo.
 */
class CatalogoCuentasMexicanoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_duplica_cuentas_que_ya_creo_la_migracion_ni_al_correr_dos_veces(): void
    {
        // La migracion 2026_10_31_130000 ya dejo 2109 y 2110 (con otra empresa que el seeder).
        $this->assertSame(1, Account::where('code', '2110')->count(), 'La migracion debe haber creado la 2110');
        $companyBefore = DB::table('accounts')->where('code', '2110')->value('company_id');

        $this->seed(CatalogoCuentasMexicanoSeeder::class);
        $this->seed(CatalogoCuentasMexicanoSeeder::class);

        $this->assertSame(1, Account::where('code', '2110')->count());
        $this->assertSame(1, Account::where('code', '2109')->count());
        $this->assertEquals($companyBefore, DB::table('accounts')->where('code', '2110')->value('company_id'), 'La cuenta existente se respeta');
        $this->assertGreaterThan(50, Account::count());
    }
}
