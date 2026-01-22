<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportProductionData extends Command
{
    protected $signature = 'lwm:import-production
                            {--source= : Path to SQL dump file}
                            {--dry-run : Show what would be imported without making changes}
                            {--skip-products : Skip importing products}
                            {--skip-users : Skip importing users}';

    protected $description = 'Import production data from daniel_crm_api dump to new LWM system';

    private bool $dryRun = false;
    private array $stats = [
        'brands' => 0,
        'categories' => 0,
        'units' => 0,
        'products' => 0,
        'users' => 0,
        'errors' => [],
    ];

    public function handle(): int
    {
        $this->dryRun = $this->option('dry-run');
        $sourceFile = $this->option('source');

        if ($this->dryRun) {
            $this->warn('MODO DRY-RUN: No se realizaran cambios');
        }

        $this->info('Iniciando importacion de datos de produccion...');
        $this->newLine();

        // If source file provided, we need to parse it
        // For now, assume data is in a staging DB or we parse the SQL
        if ($sourceFile && file_exists($sourceFile)) {
            $this->info("Archivo fuente: {$sourceFile}");
            return $this->importFromSqlFile($sourceFile);
        }

        // Alternative: import from staging database
        $this->warn('No se proporciono archivo SQL. Usa --source=ruta/archivo.sql');
        return Command::FAILURE;
    }

    private function importFromSqlFile(string $filePath): int
    {
        $content = file_get_contents($filePath);

        if (!$content) {
            $this->error("No se pudo leer el archivo: {$filePath}");
            return Command::FAILURE;
        }

        $this->info('Parseando archivo SQL...');

        // Extract and import each table
        $this->importBrands($content);
        $this->importCategories($content);
        $this->importUnits($content);

        if (!$this->option('skip-products')) {
            $this->importProducts($content);
        }

        if (!$this->option('skip-users')) {
            $this->importUsers($content);
        }

        // Show summary
        $this->showSummary();

        return count($this->stats['errors']) > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function importBrands(string $content): void
    {
        $this->info('Importando marcas (brands)...');

        // Extract INSERT statement for brands
        if (preg_match("/INSERT INTO `brands`[^;]+;/s", $content, $matches)) {
            $data = $this->parseInsertStatement($matches[0], [
                'id', 'name', 'description', 'slug', 'created_at', 'updated_at'
            ]);

            foreach ($data as $row) {
                $this->stats['brands']++;

                if (!$this->dryRun) {
                    try {
                        DB::table('brands')->updateOrInsert(
                            ['id' => $row['id']],
                            [
                                'name' => $row['name'],
                                'description' => $row['description'],
                                'slug' => $row['slug'] ?: Str::slug($row['name']),
                                'created_at' => $row['created_at'],
                                'updated_at' => $row['updated_at'],
                            ]
                        );
                    } catch (\Exception $e) {
                        $this->stats['errors'][] = "Brand {$row['id']}: " . $e->getMessage();
                    }
                }

                $this->line("  - [{$row['id']}] {$row['name']}");
            }
        }

        $this->info("  Total: {$this->stats['brands']} marcas");
    }

    private function importCategories(string $content): void
    {
        $this->info('Importando categorias...');

        if (preg_match("/INSERT INTO `categories`[^;]+;/s", $content, $matches)) {
            $data = $this->parseInsertStatement($matches[0], [
                'id', 'name', 'description', 'slug', 'created_at', 'updated_at'
            ]);

            foreach ($data as $row) {
                $this->stats['categories']++;

                if (!$this->dryRun) {
                    try {
                        DB::table('categories')->updateOrInsert(
                            ['id' => $row['id']],
                            [
                                'name' => $row['name'],
                                'description' => $row['description'],
                                'slug' => $row['slug'] ?: Str::slug($row['name']),
                                'created_at' => $row['created_at'],
                                'updated_at' => $row['updated_at'],
                            ]
                        );
                    } catch (\Exception $e) {
                        $this->stats['errors'][] = "Category {$row['id']}: " . $e->getMessage();
                    }
                }

                $this->line("  - [{$row['id']}] {$row['name']}");
            }
        }

        $this->info("  Total: {$this->stats['categories']} categorias");
    }

    private function importUnits(string $content): void
    {
        $this->info('Importando unidades...');

        if (preg_match("/INSERT INTO `units`[^;]+;/s", $content, $matches)) {
            $data = $this->parseInsertStatement($matches[0], [
                'id', 'type', 'code', 'name', 'created_at', 'updated_at'
            ]);

            foreach ($data as $row) {
                $this->stats['units']++;

                if (!$this->dryRun) {
                    try {
                        DB::table('units')->updateOrInsert(
                            ['id' => $row['id']],
                            [
                                'type' => $row['type'],
                                'code' => $row['code'],
                                'name' => $row['name'],
                                'created_at' => $row['created_at'],
                                'updated_at' => $row['updated_at'],
                            ]
                        );
                    } catch (\Exception $e) {
                        $this->stats['errors'][] = "Unit {$row['id']}: " . $e->getMessage();
                    }
                }

                $this->line("  - [{$row['id']}] {$row['name']}");
            }
        }

        $this->info("  Total: {$this->stats['units']} unidades");
    }

    private function importProducts(string $content): void
    {
        $this->info('Importando productos...');

        // Products have more complex structure
        if (preg_match_all("/\((\d+),\s*'([^']*)',\s*'([^']*)',\s*'([^']*)',\s*'([^']*)',\s*([0-9.]+|NULL),\s*([0-9.]+|NULL),\s*(\d+),\s*'([^']*)',\s*'([^']*)',\s*(\d+),\s*(\d+),\s*(\d+),\s*'([^']*)',\s*'([^']*)'\)/s", $content, $matches, PREG_SET_ORDER)) {

            foreach ($matches as $match) {
                $this->stats['products']++;

                $productData = [
                    'id' => (int) $match[1],
                    'name' => $this->unescapeSql($match[2]),
                    'sku' => $this->unescapeSql($match[3]),
                    'description' => $this->unescapeSql($match[4]),
                    'full_description' => $this->unescapeSql($match[5]),
                    'price' => $match[6] === 'NULL' ? null : (float) $match[6],
                    'cost' => $match[7] === 'NULL' ? null : (float) $match[7],
                    'iva' => (int) $match[8],
                    'img_path' => $this->unescapeSql($match[9]),
                    'datasheet_path' => $this->unescapeSql($match[10]),
                    'unit_id' => (int) $match[11],
                    'category_id' => (int) $match[12],
                    'brand_id' => (int) $match[13],
                    'created_at' => $match[14],
                    'updated_at' => $match[15],
                ];

                if (!$this->dryRun) {
                    try {
                        // Build metadata JSON
                        $metadata = [
                            'full_description' => $productData['full_description'],
                            'iva' => $productData['iva'],
                            'img_path_original' => $productData['img_path'],
                            'datasheet_path_original' => $productData['datasheet_path'],
                            'migrated_from' => 'daniel_crm_api',
                            'migrated_at' => now()->toISOString(),
                        ];

                        // Check if products table has all required columns
                        $insertData = [
                            'name' => $productData['name'],
                            'sku' => $productData['sku'],
                            'description' => $productData['description'],
                            'price' => $productData['price'],
                            'cost' => $productData['cost'],
                            'unit_id' => $productData['unit_id'],
                            'category_id' => $productData['category_id'],
                            'brand_id' => $productData['brand_id'],
                            'created_at' => $productData['created_at'],
                            'updated_at' => $productData['updated_at'],
                        ];

                        // Add optional columns if they exist
                        if (Schema::hasColumn('products', 'is_active')) {
                            $insertData['is_active'] = true;
                        }
                        if (Schema::hasColumn('products', 'slug')) {
                            $insertData['slug'] = Str::slug($productData['sku'] ?: $productData['name']);
                        }
                        if (Schema::hasColumn('products', 'metadata')) {
                            $insertData['metadata'] = json_encode($metadata);
                        }

                        DB::table('products')->updateOrInsert(
                            ['id' => $productData['id']],
                            $insertData
                        );
                    } catch (\Exception $e) {
                        $this->stats['errors'][] = "Product {$productData['id']}: " . $e->getMessage();
                    }
                }

                // Show progress every 50 products
                if ($this->stats['products'] % 50 === 0) {
                    $this->line("  Procesados: {$this->stats['products']}...");
                }
            }
        }

        $this->info("  Total: {$this->stats['products']} productos");
    }

    private function importUsers(string $content): void
    {
        $this->info('Importando usuarios...');

        if (preg_match("/INSERT INTO `users`[^;]+;/s", $content, $matches)) {
            $data = $this->parseInsertStatement($matches[0], [
                'id', 'name', 'email', 'email_verified_at', 'password', 'remember_token', 'created_at', 'updated_at'
            ]);

            foreach ($data as $row) {
                $this->stats['users']++;

                if (!$this->dryRun) {
                    try {
                        DB::table('users')->updateOrInsert(
                            ['id' => $row['id']],
                            [
                                'name' => $row['name'],
                                'email' => $row['email'],
                                'email_verified_at' => $row['email_verified_at'] !== 'NULL' ? $row['email_verified_at'] : null,
                                'password' => $row['password'],
                                'created_at' => $row['created_at'],
                                'updated_at' => $row['updated_at'],
                            ]
                        );
                    } catch (\Exception $e) {
                        $this->stats['errors'][] = "User {$row['id']}: " . $e->getMessage();
                    }
                }

                $this->line("  - [{$row['id']}] {$row['name']} ({$row['email']})");
            }
        }

        $this->info("  Total: {$this->stats['users']} usuarios");
    }

    private function parseInsertStatement(string $sql, array $columns): array
    {
        $results = [];

        // Extract values between parentheses
        if (preg_match_all("/\(([^)]+)\)/", $sql, $matches)) {
            foreach ($matches[1] as $valueString) {
                // Parse individual values (handling quoted strings)
                $values = $this->parseValues($valueString);

                if (count($values) === count($columns)) {
                    $row = [];
                    foreach ($columns as $i => $col) {
                        $row[$col] = $this->cleanValue($values[$i] ?? null);
                    }
                    $results[] = $row;
                }
            }
        }

        return $results;
    }

    private function parseValues(string $valueString): array
    {
        $values = [];
        $current = '';
        $inQuote = false;
        $escapeNext = false;

        for ($i = 0; $i < strlen($valueString); $i++) {
            $char = $valueString[$i];

            if ($escapeNext) {
                $current .= $char;
                $escapeNext = false;
                continue;
            }

            if ($char === '\\') {
                $escapeNext = true;
                continue;
            }

            if ($char === "'" && !$inQuote) {
                $inQuote = true;
                continue;
            }

            if ($char === "'" && $inQuote) {
                $inQuote = false;
                continue;
            }

            if ($char === ',' && !$inQuote) {
                $values[] = trim($current);
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if ($current !== '') {
            $values[] = trim($current);
        }

        return $values;
    }

    private function cleanValue($value)
    {
        if ($value === null || $value === 'NULL') {
            return null;
        }
        return $this->unescapeSql($value);
    }

    private function unescapeSql(string $value): string
    {
        return str_replace(
            ["\\n", "\\r", "\\'", '\\"'],
            ["\n", "\r", "'", '"'],
            $value
        );
    }

    private function showSummary(): void
    {
        $this->newLine();
        $this->info('========================================');
        $this->info('RESUMEN DE IMPORTACION');
        $this->info('========================================');
        $this->newLine();

        $this->table(
            ['Entidad', 'Registros'],
            [
                ['Marcas (brands)', $this->stats['brands']],
                ['Categorias', $this->stats['categories']],
                ['Unidades', $this->stats['units']],
                ['Productos', $this->stats['products']],
                ['Usuarios', $this->stats['users']],
            ]
        );

        if (count($this->stats['errors']) > 0) {
            $this->newLine();
            $this->error('ERRORES (' . count($this->stats['errors']) . '):');
            foreach (array_slice($this->stats['errors'], 0, 10) as $error) {
                $this->line("  - {$error}");
            }
            if (count($this->stats['errors']) > 10) {
                $this->line("  ... y " . (count($this->stats['errors']) - 10) . " errores mas");
            }
        } else {
            $this->newLine();
            $this->info('Sin errores durante la importacion');
        }

        if ($this->dryRun) {
            $this->newLine();
            $this->warn('MODO DRY-RUN: No se realizaron cambios en la base de datos');
        }
    }
}
