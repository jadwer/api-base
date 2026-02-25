<?php

namespace App\Console\Commands\ModuleGeneration;

use Illuminate\Support\Facades\File;

class ConfigurationParser
{
    /**
     * Parse JSON config file and return ModuleConfig
     */
    public function parse(string $configFile): ModuleConfig
    {
        if (!File::exists($configFile)) {
            throw new \InvalidArgumentException("Configuration file not found: {$configFile}");
        }

        $raw = json_decode(File::get($configFile), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException("Invalid JSON: " . json_last_error_msg());
        }

        $this->validate($raw);

        return $this->buildModuleConfig($raw);
    }

    /**
     * Validate the raw config array
     */
    private function validate(array $config): void
    {
        if (empty($config['module'])) {
            throw new \InvalidArgumentException("Missing required key: 'module'");
        }

        if (!is_string($config['module'])) {
            throw new \InvalidArgumentException("'module' must be a string");
        }

        if (empty($config['entities']) || !is_array($config['entities'])) {
            throw new \InvalidArgumentException("Missing or invalid 'entities' key");
        }

        foreach ($config['entities'] as $entityName => $entityData) {
            $this->validateEntity($entityName, $entityData);
        }
    }

    /**
     * Validate individual entity config
     */
    private function validateEntity(string $name, array $data): void
    {
        if (!preg_match('/^[A-Z][a-zA-Z0-9]+$/', $name)) {
            throw new \InvalidArgumentException("Entity name '{$name}' must be PascalCase (e.g., ShipmentItem)");
        }

        if (empty($data['fields']) || !is_array($data['fields'])) {
            throw new \InvalidArgumentException("Entity '{$name}' must have at least one field");
        }

        $validTypes = ['string', 'text', 'integer', 'bigInteger', 'decimal', 'boolean', 'date', 'datetime', 'json', 'enum'];

        foreach ($data['fields'] as $i => $field) {
            if (empty($field['name'])) {
                throw new \InvalidArgumentException("Entity '{$name}' field #{$i} missing 'name'");
            }
            if (empty($field['type'])) {
                throw new \InvalidArgumentException("Entity '{$name}' field '{$field['name']}' missing 'type'");
            }
            if (!in_array($field['type'], $validTypes)) {
                throw new \InvalidArgumentException("Entity '{$name}' field '{$field['name']}' has invalid type: '{$field['type']}'. Valid types: " . implode(', ', $validTypes));
            }
        }

        if (isset($data['relationships'])) {
            foreach ($data['relationships'] as $j => $rel) {
                if (empty($rel['type'])) {
                    throw new \InvalidArgumentException("Entity '{$name}' relationship #{$j} missing 'type'");
                }
                if (!in_array($rel['type'], ['belongsTo', 'hasMany', 'hasOne'])) {
                    throw new \InvalidArgumentException("Entity '{$name}' relationship #{$j} invalid type: '{$rel['type']}'");
                }
                if (empty($rel['model'])) {
                    throw new \InvalidArgumentException("Entity '{$name}' relationship #{$j} missing 'model'");
                }
                // Must have either 'entity' (same module) or 'module' (cross-module)
                if (empty($rel['entity']) && empty($rel['module'])) {
                    throw new \InvalidArgumentException("Entity '{$name}' relationship to '{$rel['model']}' must specify 'entity' (same module) or 'module' (cross-module)");
                }
            }
        }
    }

    /**
     * Build ModuleConfig from validated raw config
     */
    private function buildModuleConfig(array $config): ModuleConfig
    {
        $entities = [];
        foreach ($config['entities'] as $name => $data) {
            $entities[] = new EntityConfig($name, $data);
        }

        return new ModuleConfig($config['module'], $entities);
    }
}
