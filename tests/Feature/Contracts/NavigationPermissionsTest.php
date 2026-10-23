<?php

namespace Tests\Feature\Contracts;

use Database\Seeders\CleanRolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Candado de permisos del menu (23-10): cada permiso que pide el menu del
 * frontend debe existir tras una instalacion limpia y tenerlo el rol admin.
 * Antes 14 entradas usaban nombres inexistentes y la instalacion limpia no
 * sembraba Reportes ni Correos: las secciones se escondian a todo rol que no
 * fuera god (LWM prod y la demo marca blanca).
 */
class NavigationPermissionsTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function navigationFiles(): array
    {
        return [
            'template' => ['webapp-base/apps/template/src/config/navigationConfig.ts'],
            'laborwasser' => ['webapp-base/clients/laborwasser/webapp/src/config/navigationConfig.ts'],
        ];
    }

    /** @dataProvider navigationFiles */
    public function test_every_menu_permission_exists_after_clean_install(string $relative): void
    {
        $file = dirname(base_path()) . '/' . $relative;
        if (!is_file($file)) {
            $this->markTestSkipped("Sin el monorepo del frontend junto a api-base ({$relative}).");
        }

        $this->seed(CleanRolesAndPermissionsSeeder::class);

        preg_match_all('/permissions:\s*\[([^\]]*)\]/', (string) file_get_contents($file), $blocks);
        $required = [];
        foreach ($blocks[1] as $block) {
            preg_match_all("/'([^']+)'/", $block, $names);
            array_push($required, ...$names[1]);
        }
        $required = array_values(array_unique($required));
        $this->assertNotEmpty($required);

        $existing = Permission::where('guard_name', 'api')->pluck('name')->all();
        $this->assertSame([], array_values(array_diff($required, $existing)), 'Permisos del menu que no existen tras la instalacion limpia');

        $adminHas = Role::findByName('admin', 'api')->permissions->pluck('name')->all();
        $customerOnly = ['my-orders.index', 'my-quotes.index', 'my-cart.index'];
        $this->assertSame([], array_values(array_diff($required, $adminHas, $customerOnly)), 'Permisos del menu que el rol admin no tiene');
    }
}
