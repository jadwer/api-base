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
                                'unit_type' => $row['type'],  // Campo renombrado: type -> unit_type
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

        // Columnas en el orden del dump SQL de producción:
        // id, name, sku, description, full_description, price, cost, iva, img_path, datasheet_path, unit_id, category_id, brand_id, created_at, updated_at
        $columns = ['id', 'name', 'sku', 'description', 'full_description', 'price', 'cost', 'iva', 'img_path', 'datasheet_path', 'unit_id', 'category_id', 'brand_id', 'created_at', 'updated_at'];

        // phpMyAdmin puede generar múltiples INSERT statements, capturar todos
        if (preg_match_all("/INSERT INTO `products`[^;]+;/s", $content, $allMatches)) {
            foreach ($allMatches[0] as $insertStatement) {
                $data = $this->parseInsertStatement($insertStatement, $columns);

                foreach ($data as $row) {
                    $this->stats['products']++;

                    if (!$this->dryRun) {
                        try {
                            $insertData = [
                                'name' => $row['name'] ?: 'Sin nombre',
                                'sku' => $row['sku'] ?: null,
                                'description' => $row['description'] ?: '',
                                'full_description' => $row['full_description'] ?: '',
                                'price' => is_numeric($row['price']) ? (float) $row['price'] : null,
                                'cost' => is_numeric($row['cost']) ? (float) $row['cost'] : null,
                                'iva' => (int) ($row['iva'] ?? 0),
                                'img_path' => $row['img_path'] ?: null,
                                'datasheet_path' => $row['datasheet_path'] ?: null,
                                'unit_id' => (int) $row['unit_id'],
                                'category_id' => (int) $row['category_id'],
                                'brand_id' => (int) $row['brand_id'],
                                'is_active' => true,
                                'created_at' => $row['created_at'],
                                'updated_at' => $row['updated_at'],
                            ];

                            DB::table('products')->updateOrInsert(
                                ['id' => (int) $row['id']],
                                $insertData
                            );
                        } catch (\Exception $e) {
                            $this->stats['errors'][] = "Product {$row['id']}: " . $e->getMessage();
                        }
                    }

                    // Show progress every 100 products
                    if ($this->stats['products'] % 100 === 0) {
                        $this->line("  Procesados: {$this->stats['products']}...");
                    }
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

        // Encontrar el inicio de VALUES
        $valuesPos = stripos($sql, 'VALUES');
        if ($valuesPos === false) {
            return $results;
        }

        $valuesPart = substr($sql, $valuesPos + 6); // Skip "VALUES"

        // Parsear cada grupo de valores (...)
        $rows = $this->extractRowsFromValues($valuesPart);

        foreach ($rows as $valueString) {
            $values = $this->parseValues($valueString);

            if (count($values) === count($columns)) {
                $row = [];
                foreach ($columns as $i => $col) {
                    $row[$col] = $this->cleanValue($values[$i] ?? null);
                }
                $results[] = $row;
            }
        }

        return $results;
    }

    private function extractRowsFromValues(string $valuesPart): array
    {
        $rows = [];
        $current = '';
        $depth = 0;
        $inQuote = false;
        $escapeNext = false;

        for ($i = 0; $i < strlen($valuesPart); $i++) {
            $char = $valuesPart[$i];

            if ($escapeNext) {
                $current .= $char;
                $escapeNext = false;
                continue;
            }

            if ($char === '\\') {
                $current .= $char;
                $escapeNext = true;
                continue;
            }

            // Manejar comillas dobles escapadas ''
            if ($char === "'" && !$escapeNext) {
                if ($i + 1 < strlen($valuesPart) && $valuesPart[$i + 1] === "'") {
                    // Comilla escapada ''
                    $current .= "''";
                    $i++;
                    continue;
                }
                $inQuote = !$inQuote;
                $current .= $char;
                continue;
            }

            if (!$inQuote) {
                if ($char === '(') {
                    $depth++;
                    if ($depth === 1) {
                        $current = ''; // Empezar nuevo row
                        continue;
                    }
                }

                if ($char === ')') {
                    $depth--;
                    if ($depth === 0) {
                        $rows[] = $current;
                        $current = '';
                        continue;
                    }
                }

                if ($char === ';' && $depth === 0) {
                    break; // Fin del INSERT
                }
            }

            if ($depth > 0) {
                $current .= $char;
            }
        }

        return $rows;
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
                $current .= $char; // Mantener el backslash para unescape después
                continue;
            }

            // Manejar comillas dobles escapadas ''
            if ($char === "'" && $inQuote && $i + 1 < strlen($valueString) && $valueString[$i + 1] === "'") {
                $current .= "'"; // Una sola comilla
                $i++;
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
            ["\\n", "\\r", "\\'", '\\"', "\\\\"],
            ["\n", "\r", "'", '"', "\\"],
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
