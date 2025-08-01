<?php

namespace App\Console\Commands\ModuleGeneration;

use Illuminate\Support\Facades\File;

class ConfigurationParser
{
    /**
     * Parse configuration from JSON file
     */
    public function parseFromFile(string $configFile): array
    {
        if (!File::exists($configFile)) {
            throw new \Exception("Configuration file not found: {$configFile}");
        }

        $config = json_decode(File::get($configFile), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON in configuration file: " . json_last_error_msg());
        }

        return $this->normalizeConfiguration($config);
    }

    /**
     * Transform entities from object to array format expected by generator
     */
    public function normalizeConfiguration(array $config): array
    {
        $entitiesConfig = $config['entities'] ?? [];
        $entities = [];
        
        foreach ($entitiesConfig as $entityName => $entityData) {
            $entity = [
                'name' => $entityData['name'],
                'tableName' => $entityData['tableName'],
                'fillable' => array_column($entityData['fields'], 'name'),
                'fields' => $entityData['fields'],
                'relationships' => []
            ];
            
            $entities[] = $entity;
        }

        return [
            'entities' => $entities,
            'relationships' => $config['relationships'] ?? [],
            'permissions' => $config['permissions'] ?? [],
            'entitiesConfig' => $entitiesConfig
        ];
    }

    /**
     * Validate required configuration structure
     */
    public function validateConfiguration(array $config): void
    {
        $required = ['entities'];
        
        foreach ($required as $key) {
            if (!isset($config[$key])) {
                throw new \Exception("Missing required configuration key: {$key}");
            }
        }

        // Validate entities structure
        foreach ($config['entities'] as $entityName => $entityData) {
            $this->validateEntity($entityName, $entityData);
        }
    }

    /**
     * Validate individual entity configuration
     */
    private function validateEntity(string $entityName, array $entityData): void
    {
        $required = ['name', 'tableName', 'fields'];
        
        foreach ($required as $key) {
            if (!isset($entityData[$key])) {
                throw new \Exception("Entity '{$entityName}' missing required key: {$key}");
            }
        }

        // Validate fields
        if (!is_array($entityData['fields']) || empty($entityData['fields'])) {
            throw new \Exception("Entity '{$entityName}' must have at least one field");
        }

        foreach ($entityData['fields'] as $index => $field) {
            $this->validateField($entityName, $index, $field);
        }
    }

    /**
     * Validate individual field configuration
     */
    private function validateField(string $entityName, int $index, array $field): void
    {
        $required = ['name', 'type'];
        
        foreach ($required as $key) {
            if (!isset($field[$key])) {
                throw new \Exception("Entity '{$entityName}' field {$index} missing required key: {$key}");
            }
        }

        // Validate field type
        $validTypes = [
            'string', 'text', 'integer', 'bigInteger', 'decimal', 
            'boolean', 'date', 'datetime', 'timestamp', 'json', 'foreignId'
        ];

        if (!in_array($field['type'], $validTypes)) {
            throw new \Exception("Entity '{$entityName}' field '{$field['name']}' has invalid type: {$field['type']}");
        }
    }
}