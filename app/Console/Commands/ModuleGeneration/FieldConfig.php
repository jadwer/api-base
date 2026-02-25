<?php

namespace App\Console\Commands\ModuleGeneration;

class FieldConfig
{
    public readonly string $dbName;
    public readonly string $apiName;
    public readonly string $type;
    public readonly string $rules;
    public readonly bool $isRequired;
    public readonly bool $isNullable;
    public readonly bool $isUnique;
    public readonly bool $isSortable;
    public readonly bool $isFilterable;
    public readonly mixed $default;
    public readonly bool $hasDefault;

    public function __construct(array $data)
    {
        $this->dbName = $data['name'];
        $this->apiName = NamingHelper::toCamelCase($data['name']);
        $this->type = $data['type'];
        $this->rules = $data['rules'] ?? '';
        $this->isSortable = $data['sortable'] ?? false;
        $this->isFilterable = $data['filterable'] ?? false;
        $this->hasDefault = array_key_exists('default', $data);
        $this->default = $data['default'] ?? null;

        $rulesParts = explode('|', $this->rules);
        $this->isRequired = in_array('required', $rulesParts);
        $this->isNullable = in_array('nullable', $rulesParts);
        $this->isUnique = in_array('unique', $rulesParts);
    }

    /**
     * Get the migration column type call
     */
    public function getMigrationColumn(): string
    {
        return match ($this->type) {
            'string' => $this->getMigrationMaxLength(),
            'text' => "\$table->text('{$this->dbName}')",
            'integer' => "\$table->integer('{$this->dbName}')",
            'bigInteger' => "\$table->bigInteger('{$this->dbName}')",
            'decimal' => "\$table->decimal('{$this->dbName}', 10, 2)",
            'boolean' => "\$table->boolean('{$this->dbName}')",
            'datetime' => "\$table->dateTime('{$this->dbName}')",
            'date' => "\$table->date('{$this->dbName}')",
            'json' => "\$table->json('{$this->dbName}')",
            'enum' => "\$table->string('{$this->dbName}')",
            default => "\$table->string('{$this->dbName}')",
        };
    }

    /**
     * Get Eloquent $casts type (null if no cast needed)
     */
    public function getCastType(): ?string
    {
        return match ($this->type) {
            'integer', 'bigInteger' => "'integer'",
            'decimal' => "'float'",
            'boolean' => "'boolean'",
            'datetime' => "'datetime'",
            'date' => "'date'",
            'json' => "'array'",
            default => null,
        };
    }

    /**
     * Get JSON:API Schema field class and call
     */
    public function getSchemaField(): string
    {
        $fieldClass = match ($this->type) {
            'integer', 'bigInteger', 'decimal' => 'Number',
            'boolean' => 'Boolean',
            'datetime', 'date' => 'DateTime',
            'json' => 'ArrayHash',
            default => 'Str',
        };

        $needsMapping = $this->dbName !== $this->apiName;
        $args = $needsMapping
            ? "'{$this->apiName}', '{$this->dbName}'"
            : "'{$this->apiName}'";

        $chain = "{$fieldClass}::make({$args})";

        if ($this->isSortable) {
            $chain .= '->sortable()';
        }

        return $chain;
    }

    /**
     * Get faker expression for factory
     */
    public function getFakerExpression(): string
    {
        // Check for 'in:' rule to get enum values
        $inValues = $this->getInValues();
        if ($inValues) {
            return "\$this->faker->randomElement(['" . implode("', '", $inValues) . "'])";
        }

        return match ($this->type) {
            'string' => $this->isUnique
                ? "strtoupper(\$this->faker->unique()->bothify('???-###'))"
                : "\$this->faker->words(3, true)",
            'text' => "\$this->faker->paragraph()",
            'integer', 'bigInteger' => "\$this->faker->numberBetween(1, 100)",
            'decimal' => "\$this->faker->randomFloat(2, 0, 999)",
            'boolean' => "\$this->faker->boolean(80)",
            'datetime' => "\$this->faker->dateTime()",
            'date' => "\$this->faker->date()",
            'json' => "[]",
            default => "\$this->faker->words(3, true)",
        };
    }

    /**
     * Get validation rules as a PHP array string for RequestGenerator
     */
    public function getValidationRules(string $tableName, bool $isUpdate): string
    {
        $parts = explode('|', $this->rules);
        $phpRules = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') continue;

            // Handle 'required' -> use 'sometimes' in update context
            if ($part === 'required') {
                $phpRules[] = $isUpdate ? "'sometimes'" : "'required'";
                continue;
            }

            // Skip 'unique' - handled separately below
            if ($part === 'unique') continue;

            // Handle 'in:val1,val2' -> Rule::in()
            if (str_starts_with($part, 'in:')) {
                $values = substr($part, 3);
                $phpRules[] = "Rule::in(['" . str_replace(',', "', '", $values) . "'])";
                continue;
            }

            $phpRules[] = "'{$part}'";
        }

        // Add unique rule with ignore on update
        if ($this->isUnique) {
            if ($isUpdate) {
                $phpRules[] = "Rule::unique('{$tableName}', '{$this->dbName}')->ignore(\$model)";
            } else {
                $phpRules[] = "Rule::unique('{$tableName}', '{$this->dbName}')";
            }
        }

        return '[' . implode(', ', $phpRules) . ']';
    }

    /**
     * Get test value for this field type
     */
    public function getTestValue(): string
    {
        $inValues = $this->getInValues();
        if ($inValues) {
            return "'" . $inValues[0] . "'";
        }

        return match ($this->type) {
            'string' => "'Test " . ucfirst(str_replace('_', ' ', $this->dbName)) . "'",
            'text' => "'Test description for " . $this->dbName . "'",
            'integer', 'bigInteger' => '42',
            'decimal' => '99.99',
            'boolean' => 'true',
            'datetime' => "now()->toISOString()",
            'date' => "now()->format('Y-m-d')",
            'json' => "['key' => 'value']",
            default => "'test string'",
        };
    }

    /**
     * Get invalid test value for this field type
     */
    public function getInvalidTestValue(): string
    {
        return match ($this->type) {
            'string', 'text', 'enum' => "''",
            'integer', 'bigInteger' => "'not_a_number'",
            'decimal' => "'not_a_number'",
            'boolean' => "'not_boolean'",
            'datetime', 'date' => "'invalid-date'",
            'json' => "'not_json'",
            default => "''",
        };
    }

    // -- Private helpers --

    private function getMigrationMaxLength(): string
    {
        // Extract max length from rules like 'max:100'
        if (preg_match('/max:(\d+)/', $this->rules, $matches)) {
            return "\$table->string('{$this->dbName}', {$matches[1]})";
        }
        return "\$table->string('{$this->dbName}')";
    }

    /**
     * @return string[]|null
     */
    private function getInValues(): ?array
    {
        if (preg_match('/(?:^|\|)in:([^|]+)/', $this->rules, $matches)) {
            return explode(',', $matches[1]);
        }
        return null;
    }
}
