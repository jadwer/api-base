<?php

namespace Modules\HR\Tests\Feature;

use Modules\HR\Database\Seeders\OrganizationChartSeeder;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;
use Tests\TestCase;

class OrganizationChartSeederTest extends TestCase
{
    public function test_seeder_creates_the_org_chart_and_is_idempotent(): void
    {
        $departmentsBefore = Department::count();
        $positionsBefore = Position::count();

        $this->seed(OrganizationChartSeeder::class);
        $this->seed(OrganizationChartSeeder::class);

        $this->assertSame($departmentsBefore + 4, Department::count());
        $this->assertSame($positionsBefore + 10, Position::count());

        $sales = Department::where('name', 'Ventas y Telemarketing')->firstOrFail();
        $this->assertSame(2, $sales->positions()->count());
        // Se consulta por departamento: los seeds demo de HR crean puestos con titulos al azar.
        $this->assertSame('ventas-jr', $sales->positions()->where('title', 'Ejecutivo de Ventas')->firstOrFail()->default_role);
        $finance = Department::where('name', 'Finanzas')->firstOrFail();
        $this->assertNull($finance->positions()->where('title', 'Facturación')->firstOrFail()->default_role);
    }

    public function test_seeder_does_not_override_a_manually_set_role(): void
    {
        $this->seed(OrganizationChartSeeder::class);
        $sales = Department::where('name', 'Ventas y Telemarketing')->firstOrFail();
        $sales->positions()->where('title', 'Telemarketing')->update(['default_role' => 'tech']);

        $this->seed(OrganizationChartSeeder::class);

        $this->assertSame('tech', $sales->positions()->where('title', 'Telemarketing')->firstOrFail()->default_role);
    }
}
