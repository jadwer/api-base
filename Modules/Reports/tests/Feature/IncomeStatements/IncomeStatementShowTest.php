<?php

namespace Modules\Reports\Tests\Feature\IncomeStatements;

use Tests\TestCase;

class IncomeStatementShowTest extends TestCase
{

    /** @test */
    public function admin_can_show_income_statement()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('income-statements')
            ->get('/api/v1/reports/income-statements/1');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'startDate',
                    'endDate',
                    'currency',
                    'revenues',
                    'totalRevenues',
                    'expenses',
                    'totalExpenses',
                    'netIncome',
                    'generatedAt',
                ],
            ],
        ]);
    }

    /** @test */
    public function tech_user_can_show_income_statement()
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('income-statements')
            ->get('/api/v1/reports/income-statements/1');

        $response->assertOk();
    }

    /** @test */
    public function customer_cannot_show_income_statement()
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('income-statements')
            ->get('/api/v1/reports/income-statements/1');

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_show_income_statement()
    {
        $response = $this->jsonApi()
            ->expects('income-statements')
            ->get('/api/v1/reports/income-statements/1');

        $response->assertStatus(401);
    }

    /** @test */
    public function can_show_income_statement_with_date_filter()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('income-statements')
            ->filter(['startDate' => '2025-10-01'])
            ->get('/api/v1/reports/income-statements/1');

        $response->assertOk();
        $response->assertJsonPath('data.attributes.startDate', '2025-10-01');
    }

    /** @test */
    public function income_statement_show_includes_all_required_fields()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('income-statements')
            ->get('/api/v1/reports/income-statements/1');

        $response->assertOk();

        $data = $response->json('data.attributes');

        $this->assertArrayHasKey('startDate', $data);
        $this->assertArrayHasKey('endDate', $data);
        $this->assertArrayHasKey('currency', $data);
        $this->assertArrayHasKey('revenues', $data);
        $this->assertArrayHasKey('totalRevenues', $data);
        $this->assertArrayHasKey('expenses', $data);
        $this->assertArrayHasKey('totalExpenses', $data);
        $this->assertArrayHasKey('netIncome', $data);
        $this->assertArrayHasKey('generatedAt', $data);
    }
}
