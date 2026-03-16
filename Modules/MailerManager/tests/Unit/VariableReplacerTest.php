<?php

namespace Modules\MailerManager\Tests\Unit;

use Modules\MailerManager\Services\VariableReplacer;
use Tests\TestCase;

class VariableReplacerTest extends TestCase
{
    protected VariableReplacer $replacer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->replacer = new VariableReplacer();
    }

    public function test_simple_variable_replacement(): void
    {
        $result = $this->replacer->replace(
            'Hola {{customer_name}}, tu orden es {{order_number}}.',
            ['customer_name' => 'Juan', 'order_number' => 'OV-001']
        );

        $this->assertEquals('Hola Juan, tu orden es OV-001.', $result);
    }

    public function test_missing_variables_are_removed(): void
    {
        $result = $this->replacer->replace(
            'Hola {{name}}, tu {{missing}} esta listo.',
            ['name' => 'Juan']
        );

        $this->assertEquals('Hola Juan, tu  esta listo.', $result);
    }

    public function test_currency_format(): void
    {
        $result = $this->replacer->replace(
            'Total: {{total|currency}}',
            ['total' => 3015.98]
        );

        $this->assertEquals('Total: $3,015.98', $result);
    }

    public function test_number_format(): void
    {
        $result = $this->replacer->replace(
            'Cantidad: {{quantity|number}}',
            ['quantity' => 1500.5]
        );

        $this->assertEquals('Cantidad: 1,500.50', $result);
    }

    public function test_date_format(): void
    {
        $result = $this->replacer->replace(
            'Fecha: {{date|date}}',
            ['date' => '2026-03-16']
        );

        $this->assertEquals('Fecha: 16/03/2026', $result);
    }

    public function test_conditional_truthy(): void
    {
        $result = $this->replacer->replace(
            '{{#if notes}}Notas: {{notes}}{{/if}}',
            ['notes' => 'Importante']
        );

        $this->assertEquals('Notas: Importante', $result);
    }

    public function test_conditional_falsy(): void
    {
        $result = $this->replacer->replace(
            '{{#if notes}}Notas: {{notes}}{{/if}}',
            ['notes' => '']
        );

        $this->assertEquals('', $result);
    }

    public function test_conditional_null(): void
    {
        $result = $this->replacer->replace(
            'Antes{{#if notes}} - Notas: {{notes}}{{/if}} Despues',
            ['notes' => null]
        );

        $this->assertEquals('Antes Despues', $result);
    }

    public function test_loop_with_array_items(): void
    {
        $result = $this->replacer->replace(
            '<ul>{{#each items}}<li>{{name}} x{{quantity}}</li>{{/each}}</ul>',
            [
                'items' => [
                    ['name' => 'iPhone', 'quantity' => 2],
                    ['name' => 'Case', 'quantity' => 1],
                ],
            ]
        );

        $this->assertEquals('<ul><li>iPhone x2</li><li>Case x1</li></ul>', $result);
    }

    public function test_loop_with_index(): void
    {
        $result = $this->replacer->replace(
            '{{#each items}}{{@index}}. {{name}} {{/each}}',
            [
                'items' => [
                    ['name' => 'A'],
                    ['name' => 'B'],
                ],
            ]
        );

        $this->assertEquals('1. A 2. B ', $result);
    }

    public function test_loop_empty_array(): void
    {
        $result = $this->replacer->replace(
            'Items: {{#each items}}{{name}}{{/each}}',
            ['items' => []]
        );

        $this->assertEquals('Items: ', $result);
    }

    public function test_null_value_renders_empty(): void
    {
        $result = $this->replacer->replace(
            'Value: {{val}}',
            ['val' => null]
        );

        $this->assertEquals('Value: ', $result);
    }

    public function test_boolean_renders_si_no(): void
    {
        $result = $this->replacer->replace(
            'Activo: {{active}}',
            ['active' => true]
        );

        $this->assertEquals('Activo: Si', $result);
    }

    public function test_complex_template(): void
    {
        $template = '<h1>Cotizacion {{quote_number}}</h1>'
            . '<p>Cliente: {{customer_name}}</p>'
            . '<p>Total: {{total|currency}} {{currency}}</p>'
            . '{{#if notes}}<p>Notas: {{notes}}</p>{{/if}}'
            . '<table>{{#each items}}<tr><td>{{name}}</td><td>{{quantity}}</td><td>{{subtotal|currency}}</td></tr>{{/each}}</table>';

        $data = [
            'quote_number' => 'COT-001',
            'customer_name' => 'ACME Corp',
            'total' => 5000,
            'currency' => 'MXN',
            'notes' => 'Vigencia 30 dias',
            'items' => [
                ['name' => 'Producto A', 'quantity' => 10, 'subtotal' => 3000],
                ['name' => 'Producto B', 'quantity' => 5, 'subtotal' => 2000],
            ],
        ];

        $result = $this->replacer->replace($template, $data);

        $this->assertStringContainsString('COT-001', $result);
        $this->assertStringContainsString('ACME Corp', $result);
        $this->assertStringContainsString('$5,000.00', $result);
        $this->assertStringContainsString('Notas: Vigencia 30 dias', $result);
        $this->assertStringContainsString('Producto A', $result);
        $this->assertStringContainsString('$3,000.00', $result);
    }
}
