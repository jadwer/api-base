<?php

namespace Modules\Reports\Tests\Feature\IncomeStatements;

use Tests\TestCase;

class IncomeStatementIndexTest extends TestCase
{

    /** @test */
    public function admin_can_fetch_income_statements()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('income-statements')
            ->get('/api/v1/reports/income-statements');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
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
            ],
        ]);
    }

    /** @test */
    public function tech_user_can_fetch_income_statements()
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('income-statements')
            ->get('/api/v1/reports/income-statements');

        $response->assertOk();
    }

    /** @test */
    public function customer_cannot_fetch_income_statements()
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('income-statements')
            ->get('/api/v1/reports/income-statements');

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_fetch_income_statements()
    {
        $response = $this->jsonApi()
            ->expects('income-statements')
            ->get('/api/v1/reports/income-statements');

        $response->assertStatus(401);
    }

    /** @test */
    public function can_filter_income_statement_by_date()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('income-statements')
            ->filter(['startDate' => '2025-10-01'])
            ->get('/api/v1/reports/income-statements');

        $response->assertOk();
        $response->assertJsonPath('data.0.attributes.startDate', '2025-10-01');
    }

    /** @test */
    public function income_statement_includes_all_sections()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('income-statements')
            ->get('/api/v1/reports/income-statements');

        $response->assertOk();

        $data = $response->json('data.0.attributes');

        $this->assertArrayHasKey('revenues', $data);
        $this->assertArrayHasKey('expenses', $data);
        $this->assertArrayHasKey('netIncome', $data);
        $this->assertIsArray($data['revenues']);
        $this->assertIsArray($data['expenses']);
    }

    /** @test */
    public function income_statement_includes_totals()
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('income-statements')
            ->get('/api/v1/reports/income-statements');

        $response->assertOk();

        $data = $response->json('data.0.attributes');

        $this->assertArrayHasKey('totalRevenues', $data);
        $this->assertArrayHasKey('totalExpenses', $data);
        $this->assertArrayHasKey('netIncome', $data);
        $this->assertIsNumeric($data['totalRevenues']);
        $this->assertIsNumeric($data['totalExpenses']);
        $this->assertIsNumeric($data['netIncome']);
    }
}
