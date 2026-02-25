<?php

namespace App\Console\Commands\ModuleGeneration;

class EntityConfig
{
    public readonly string $modelName;
    public readonly string $tableName;
    public readonly string $jsonApiType;
    public readonly string $pluralName;
    public readonly string $permissionPrefix;

    /** @var FieldConfig[] */
    public readonly array $fields;

    /** @var array */
    public readonly array $relationships;

    public function __construct(string $name, array $data)
    {
        $this->modelName = $name;
        $this->tableName = $data['table'] ?? NamingHelper::toTableName($name);
        $this->jsonApiType = NamingHelper::toJsonApiType($name);
        $this->pluralName = NamingHelper::toPlural($name);
        $this->permissionPrefix = $this->jsonApiType;

        $this->fields = array_map(
            fn(array $field) => new FieldConfig($field),
            $data['fields'] ?? []
        );

        $this->relationships = $data['relationships'] ?? [];
    }

    /**
     * Get only belongsTo relationships
     * @return array
     */
    public function getBelongsToRelationships(): array
    {
        return array_filter($this->relationships, fn($r) => $r['type'] === 'belongsTo');
    }

    /**
     * Get only hasMany relationships
     * @return array
     */
    public function getHasManyRelationships(): array
    {
        return array_filter($this->relationships, fn($r) => $r['type'] === 'hasMany');
    }

    /**
     * Get only hasOne relationships
     * @return array
     */
    public function getHasOneRelationships(): array
    {
        return array_filter($this->relationships, fn($r) => $r['type'] === 'hasOne');
    }

    /**
     * Check if a relationship is to an entity within the same module
     */
    public function isInternalRelationship(array $relationship): bool
    {
        return isset($relationship['entity']);
    }

    /**
     * Check if a relationship is to an entity in another module
     */
    public function isExternalRelationship(array $relationship): bool
    {
        return isset($relationship['module']);
    }

    /**
     * Get fillable field names (snake_case, no foreign keys)
     * @return string[]
     */
    public function getFillableFields(): array
    {
        return array_map(fn(FieldConfig $f) => $f->dbName, $this->fields);
    }

    /**
     * Get foreign key columns for belongsTo relationships
     * @return string[]
     */
    public function getForeignKeyColumns(): array
    {
        return array_map(
            fn($r) => NamingHelper::toForeignKey($r['model']),
            $this->getBelongsToRelationships()
        );
    }

    /**
     * Get all fillable columns (fields + foreign keys)
     * @return string[]
     */
    public function getAllFillable(): array
    {
        return array_merge($this->getFillableFields(), $this->getForeignKeyColumns());
    }

    /**
     * Get fields that have a cast type
     * @return array [dbName => castType]
     */
    public function getCasts(): array
    {
        $casts = ['id' => "'integer'"];
        foreach ($this->fields as $field) {
            $cast = $field->getCastType();
            if ($cast !== null) {
                $casts[$field->dbName] = $cast;
            }
        }
        // FK casts
        foreach ($this->getBelongsToRelationships() as $rel) {
            $fk = NamingHelper::toForeignKey($rel['model']);
            $casts[$fk] = "'integer'";
        }
        return $casts;
    }

    /**
     * Whether any field has a default value
     */
    public function hasDefaults(): bool
    {
        foreach ($this->fields as $field) {
            if ($field->hasDefault) return true;
        }
        return false;
    }

    /**
     * Get fields with defaults for Request withDefaults()
     * @return FieldConfig[]
     */
    public function getFieldsWithDefaults(): array
    {
        return array_filter($this->fields, fn(FieldConfig $f) => $f->hasDefault);
    }

    /**
     * Get the first required string field for use in tests (sort, filter)
     */
    public function getFirstSortableField(): ?FieldConfig
    {
        foreach ($this->fields as $field) {
            if ($field->isSortable) return $field;
        }
        return $this->fields[0] ?? null;
    }

    /**
     * Get the first filterable field for use in tests
     */
    public function getFirstFilterableField(): ?FieldConfig
    {
        foreach ($this->fields as $field) {
            if ($field->isFilterable) return $field;
        }
        return null;
    }
}
