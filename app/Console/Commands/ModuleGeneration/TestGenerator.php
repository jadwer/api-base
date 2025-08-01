<?php

namespace App\Console\Commands\ModuleGeneration;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TestGenerator
{
    private string $moduleName;
    private string $moduleNamespace;
    private $command;
    
    public function __construct(string $moduleName, $command = null)
    {
        $this->moduleName = $moduleName;
        $this->moduleNamespace = "Modules\\{$moduleName}";
        $this->command = $command;
    }
    
    /**
     * Generate comprehensive tests for an entity
     */
    public function generateAdvancedTests(array $entity): void
    {
        $entityName = $entity['name'];
        $testPath = base_path("Modules/{$this->moduleName}/Tests/Feature");
        
        if (!File::isDirectory($testPath)) {
            File::makeDirectory($testPath, 0755, true);
        }
        
        $testTypes = ['Index', 'Show', 'Store', 'Update', 'Destroy'];
        
        foreach ($testTypes as $testType) {
            $this->generateSingleTest($entityName, $testType, $entity);
        }
    }
    
    /**
     * Generate a single test file
     */
    private function generateSingleTest(string $entityName, string $testType, array $entity): void
    {
        $testFileName = "{$entityName}{$testType}Test.php";
        $testPath = base_path("Modules/{$this->moduleName}/Tests/Feature/{$testFileName}");
        
        $stub = $this->getStub("test-{$testType}");
        $content = $this->replacePlaceholders($stub, $entityName, $entity, $testType);
        
        File::put($testPath, $content);
    }
    
    /**
     * Replace placeholders in test templates
     */
    private function replacePlaceholders(string $stub, string $entityName, array $entity, string $testType): string
    {
        $resourceType = Str::kebab(Str::plural($entityName));
        $modelVariable = Str::camel($entityName);
        $modelVariablePlural = Str::camel(Str::plural($entityName));
        
        $replacements = [
            '{{moduleName}}' => $this->moduleName,
            '{{moduleNamespace}}' => $this->moduleNamespace,
            '{{entityName}}' => $entityName,
            '{{modelVariable}}' => $modelVariable,
            '{{modelVariablePlural}}' => $modelVariablePlural,
            '{{resourceType}}' => $resourceType,
            '{{testableFields}}' => $this->getTestableFields($entity),
            '{{factoryTestFields}}' => $this->getFactoryTestFields($entity),
            '{{sortTestData}}' => $this->getSortTestData($entity),
            '{{filterTestData}}' => $this->getFilterTestData($entity),
            '{{storeTestFields}}' => $this->getStoreTestFields($entity),
            '{{storeTestDbFields}}' => $this->getStoreTestDbFields($entity),
            '{{minimalStoreTestFields}}' => $this->getMinimalStoreTestFields($entity),
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }
    
    /**
     * Get testable fields for the entity
     */
    private function getTestableFields(array $entity): string
    {
        $fields = [];
        foreach ($entity['fields'] as $field) {
            if ($field['fillable'] && !in_array($field['name'], ['id', 'created_at', 'updated_at'])) {
                $testValue = $this->getTestValueForField($field['name'], $field['type']);
                $fields[] = "            '{$field['name']}' => {$testValue}";
            }
        }
        
        return implode(",\n", $fields);
    }
    
    /**
     * Get factory test fields for the entity
     */
    private function getFactoryTestFields(array $entity): string
    {
        $fields = [];
        foreach ($entity['fields'] as $field) {
            if ($field['fillable'] && !in_array($field['name'], ['id', 'created_at', 'updated_at'])) {
                $factoryValue = $this->getFactoryValueForField($field['name'], $field['type']);
                $fields[] = "            '{$field['name']}' => {$factoryValue}";
            }
        }
        
        return implode(",\n", $fields);
    }
    
    /**
     * Get sort test data for the entity
     */
    private function getSortTestData(array $entity): string
    {
        $sortableFields = array_filter($entity['fields'], fn($field) => $field['sortable'] ?? false);
        
        if (empty($sortableFields)) {
            return "'name'"; // Fallback
        }
        
        $firstSortableField = array_values($sortableFields)[0];
        return "'{$firstSortableField['name']}'";
    }
    
    /**
     * Get filter test data for the entity
     */
    private function getFilterTestData(array $entity): string
    {
        $filterableFields = array_filter($entity['fields'], fn($field) => $field['filterable'] ?? false);
        
        if (empty($filterableFields)) {
            return [
                'field' => 'name',
                'value' => "'test'",
                'factory_value' => 'fake()->word()'
            ];
        }
        
        $firstFilterableField = array_values($filterableFields)[0];
        $testValue = $this->getTestValueForField($firstFilterableField['name'], $firstFilterableField['type']);
        $factoryValue = $this->getFactoryValueForField($firstFilterableField['name'], $firstFilterableField['type']);
        
        return [
            'field' => $firstFilterableField['name'],
            'value' => $testValue,
            'factory_value' => $factoryValue
        ];
    }
    
    /**
     * Get store test fields for the entity
     */
    private function getStoreTestFields(array $entity): string
    {
        $fields = [];
        foreach ($entity['fields'] as $field) {
            if ($field['fillable'] && $field['required'] && !in_array($field['name'], ['id', 'created_at', 'updated_at'])) {
                $testValue = $this->getJsonApiTestValueForField($field['name'], $field['type']);
                $fields[] = "                    '{$field['name']}' => {$testValue}";
            }
        }
        
        return implode(",\n", $fields);
    }
    
    /**
     * Get store test database fields for the entity
     */
    private function getStoreTestDbFields(array $entity): string
    {
        $fields = [];
        foreach ($entity['fields'] as $field) {
            if ($field['fillable'] && $field['required'] && !in_array($field['name'], ['id', 'created_at', 'updated_at'])) {
                $testValue = $this->getDbTestValueForField($field['name'], $field['type']);
                $fields[] = "            '{$field['name']}' => {$testValue}";
            }
        }
        
        return implode(",\n", $fields);
    }
    
    /**
     * Get minimal store test fields for the entity
     */
    private function getMinimalStoreTestFields(array $entity): string
    {
        $requiredFields = array_filter($entity['fields'], fn($field) => 
            $field['fillable'] && $field['required'] && !in_array($field['name'], ['id', 'created_at', 'updated_at'])
        );
        
        if (empty($requiredFields)) {
            return "                    'name' => 'Test Item'";
        }
        
        $fields = [];
        foreach ($requiredFields as $field) {
            $testValue = $this->getJsonApiTestValueForField($field['name'], $field['type']);
            $fields[] = "                    '{$field['name']}' => {$testValue}";
        }
        
        return implode(",\n", $fields);
    }
    
    /**
     * Get test value for field type
     */
    private function getTestValueForField(string $fieldName, string $fieldType): string
    {
        return match($fieldType) {
            'string' => "'Test " . ucfirst($fieldName) . "'",
            'text' => "'Test " . ucfirst($fieldName) . " Description'",
            'integer' => '100',
            'float', 'decimal' => '99.99',
            'boolean' => 'true',
            'date' => "'2024-01-01'",
            'datetime', 'timestamp' => "'2024-01-01 12:00:00'",
            'json' => "['key' => 'value']",
            'foreignId' => '1',
            default => "'test_value'"
        };
    }
    
    /**
     * Get factory value for field type
     */
    private function getFactoryValueForField(string $fieldName, string $fieldType): string
    {
        return match($fieldType) {
            'string' => "fake()->words(2, true)",
            'text' => "fake()->paragraph()",
            'integer' => 'fake()->numberBetween(1, 1000)',
            'float', 'decimal' => 'fake()->randomFloat(2, 1, 1000)',
            'boolean' => 'fake()->boolean()',
            'date' => "fake()->date()",
            'datetime', 'timestamp' => "fake()->dateTime()",
            'json' => "['key' => fake()->word()]",
            'foreignId' => '1',
            default => "fake()->word()"
        };
    }
    
    /**
     * Get JSON:API test value for field type
     */
    private function getJsonApiTestValueForField(string $fieldName, string $fieldType): string
    {
        return match($fieldType) {
            'string' => "'New " . ucfirst($fieldName) . "'",
            'text' => "'New " . ucfirst($fieldName) . " Description'",
            'integer' => '200',
            'float', 'decimal' => '199.99',
            'boolean' => 'false',
            'date' => "'2024-12-31'",
            'datetime', 'timestamp' => "'2024-12-31 23:59:59'",
            'json' => "['updated' => 'value']",
            'foreignId' => '2',
            default => "'updated_value'"
        };
    }
    
    /**
     * Get database test value for field type
     */
    private function getDbTestValueForField(string $fieldName, string $fieldType): string
    {
        return match($fieldType) {
            'string' => "'New " . ucfirst($fieldName) . "'",
            'text' => "'New " . ucfirst($fieldName) . " Description'",
            'integer' => '200',
            'float', 'decimal' => '199.99',
            'boolean' => 'false',
            'date' => "'2024-12-31'",
            'datetime', 'timestamp' => "'2024-12-31 23:59:59'",
            'json' => "json_encode(['updated' => 'value'])",
            'foreignId' => '2',
            default => "'updated_value'"
        };
    }
    
    /**
     * Get stub content using command's getStub method
     */
    private function getStub(string $stubName): string
    {
        if ($this->command && method_exists($this->command, 'getStub')) {
            return $this->command->getStub($stubName);
        }
        
        // Fallback to direct file access
        $stubPath = base_path("stubs/nwidart-stubs/{$stubName}.stub");
        
        if (!File::exists($stubPath)) {
            throw new \Exception("Stub file not found: {$stubPath}");
        }
        
        return File::get($stubPath);
    }
}