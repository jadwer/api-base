<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

use Modules\Inventory\Models\Warehouse;

$warehouse = Warehouse::factory()->make();

echo "=== FACTORY DATA DEBUG ===\n";
echo "max_capacity: ";
var_dump($warehouse->max_capacity);
echo "Type: " . gettype($warehouse->max_capacity) . "\n\n";

echo "operating_hours: ";
var_dump($warehouse->operating_hours);
echo "Type: " . gettype($warehouse->operating_hours) . "\n\n";

echo "metadata: ";
var_dump($warehouse->metadata);
echo "Type: " . gettype($warehouse->metadata) . "\n\n";

echo "=== RAW ATTRIBUTES ===\n";
var_dump($warehouse->getAttributes());
