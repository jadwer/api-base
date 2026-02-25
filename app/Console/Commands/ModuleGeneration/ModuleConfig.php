<?php

namespace App\Console\Commands\ModuleGeneration;

class ModuleConfig
{
    public readonly string $name;

    /** @var EntityConfig[] */
    public readonly array $entities;

    public function __construct(string $name, array $entities)
    {
        $this->name = $name;
        $this->entities = $entities;
    }

    /**
     * Get entities sorted by dependencies (belongsTo targets first)
     * This ensures migrations run in the correct order.
     * @return EntityConfig[]
     */
    public function getEntitiesSortedByDependency(): array
    {
        $entities = $this->entities;
        $entityNames = array_map(fn(EntityConfig $e) => $e->modelName, $entities);
        $sorted = [];
        $remaining = $entities;
        $resolved = [];
        $maxIterations = count($entities) * count($entities);
        $iteration = 0;

        while (count($remaining) > 0 && $iteration < $maxIterations) {
            $iteration++;
            foreach ($remaining as $key => $entity) {
                $deps = [];
                foreach ($entity->getBelongsToRelationships() as $rel) {
                    // Only track internal dependencies
                    if (isset($rel['entity']) && in_array($rel['entity'], $entityNames)) {
                        $deps[] = $rel['entity'];
                    }
                }

                $allResolved = true;
                foreach ($deps as $dep) {
                    if (!in_array($dep, $resolved)) {
                        $allResolved = false;
                        break;
                    }
                }

                if ($allResolved) {
                    $sorted[] = $entity;
                    $resolved[] = $entity->modelName;
                    unset($remaining[$key]);
                }
            }
            $remaining = array_values($remaining);
        }

        // Append any unresolved entities (circular deps fallback)
        foreach ($remaining as $entity) {
            $sorted[] = $entity;
        }

        return $sorted;
    }

    /**
     * Get all JSON:API types for all entities
     * @return string[]
     */
    public function getAllJsonApiTypes(): array
    {
        return array_map(fn(EntityConfig $e) => $e->jsonApiType, $this->entities);
    }

    /**
     * Get all permission names for all entities
     * @return string[]
     */
    public function getAllPermissions(): array
    {
        $permissions = [];
        foreach ($this->entities as $entity) {
            foreach (['index', 'show', 'store', 'update', 'destroy'] as $action) {
                $permissions[] = "{$entity->permissionPrefix}.{$action}";
            }
        }
        return $permissions;
    }
}
