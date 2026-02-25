<?php

namespace App\Console\Commands\ModuleGeneration;

use Illuminate\Support\Str;

class NamingHelper
{
    /**
     * PascalCase to snake_case: 'ShipmentItem' -> 'shipment_item'
     */
    public static function toSnakeCase(string $name): string
    {
        return Str::snake($name);
    }

    /**
     * snake_case to camelCase: 'tracking_number' -> 'trackingNumber'
     */
    public static function toCamelCase(string $name): string
    {
        return Str::camel($name);
    }

    /**
     * PascalCase to kebab-case: 'ShipmentItem' -> 'shipment-item'
     */
    public static function toKebabCase(string $name): string
    {
        return Str::kebab($name);
    }

    /**
     * Pluralize: 'ShipmentItem' -> 'ShipmentItems'
     */
    public static function toPlural(string $name): string
    {
        return Str::plural($name);
    }

    /**
     * PascalCase to table name: 'ShipmentItem' -> 'shipment_items'
     */
    public static function toTableName(string $name): string
    {
        return Str::snake(Str::plural($name));
    }

    /**
     * PascalCase to JSON:API type: 'ShipmentItem' -> 'shipment-items'
     */
    public static function toJsonApiType(string $name): string
    {
        return Str::kebab(Str::plural($name));
    }

    /**
     * Relationship method name: 'Contact' -> 'contact', 'ShipmentItem' -> 'shipmentItem'
     */
    public static function toRelationMethod(string $name): string
    {
        return Str::camel($name);
    }

    /**
     * Foreign key column: 'Contact' -> 'contact_id', 'ShipmentItem' -> 'shipment_item_id'
     */
    public static function toForeignKey(string $name): string
    {
        return Str::snake($name) . '_id';
    }
}
