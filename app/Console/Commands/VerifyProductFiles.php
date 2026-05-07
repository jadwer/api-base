<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VerifyProductFiles extends Command
{
    protected $signature = 'system:verify-files
                            {--fix : Generate report of missing files}
                            {--images-path=products : Subdirectory for images}
                            {--datasheets-path=datasheets : Subdirectory for datasheets}';

    protected $description = 'Verify that all product images and datasheets exist in storage';

    private array $missingImages = [];
    private array $missingDatasheets = [];
    private int $totalProducts = 0;
    private int $productsWithImages = 0;
    private int $productsWithDatasheets = 0;

    public function handle(): int
    {
        $imagesPath = $this->option('images-path');
        $datasheetsPath = $this->option('datasheets-path');

        $this->info('Verificando archivos de productos...');
        $this->newLine();

        // Get all products with file references
        $products = DB::table('products')->select([
            'id',
            'sku',
            'name',
            'metadata'
        ])->get();

        $this->totalProducts = $products->count();
        $this->info("Total productos: {$this->totalProducts}");
        $this->newLine();

        $bar = $this->output->createProgressBar($this->totalProducts);
        $bar->start();

        foreach ($products as $product) {
            $metadata = json_decode($product->metadata ?? '{}', true) ?: [];

            // Check image
            $imgPath = $metadata['img_path_original'] ?? null;
            if ($imgPath) {
                $this->productsWithImages++;
                $fullPath = "public/{$imagesPath}/{$imgPath}";

                if (!Storage::exists($fullPath)) {
                    $this->missingImages[] = [
                        'id' => $product->id,
                        'sku' => $product->sku,
                        'file' => $imgPath,
                        'expected_path' => storage_path("app/{$fullPath}"),
                    ];
                }
            }

            // Check datasheet
            $datasheetPath = $metadata['datasheet_path_original'] ?? null;
            if ($datasheetPath) {
                $this->productsWithDatasheets++;
                $fullPath = "public/{$datasheetsPath}/{$datasheetPath}";

                if (!Storage::exists($fullPath)) {
                    $this->missingDatasheets[] = [
                        'id' => $product->id,
                        'sku' => $product->sku,
                        'file' => $datasheetPath,
                        'expected_path' => storage_path("app/{$fullPath}"),
                    ];
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Show results
        $this->showResults();

        // Generate report if requested
        if ($this->option('fix')) {
            $this->generateReport();
        }

        return (count($this->missingImages) + count($this->missingDatasheets)) > 0
            ? Command::FAILURE
            : Command::SUCCESS;
    }

    private function showResults(): void
    {
        $this->info('========================================');
        $this->info('RESUMEN DE VERIFICACION DE ARCHIVOS');
        $this->info('========================================');
        $this->newLine();

        // Images
        $foundImages = $this->productsWithImages - count($this->missingImages);
        $this->line("IMAGENES:");
        $this->line("  Productos con imagen referenciada: {$this->productsWithImages}");
        $this->line("  Archivos encontrados: {$foundImages}");

        if (count($this->missingImages) > 0) {
            $this->error("  Archivos faltantes: " . count($this->missingImages));
        } else {
            $this->info("  Archivos faltantes: 0");
        }

        $this->newLine();

        // Datasheets
        $foundDatasheets = $this->productsWithDatasheets - count($this->missingDatasheets);
        $this->line("FICHAS TECNICAS (PDF):");
        $this->line("  Productos con ficha referenciada: {$this->productsWithDatasheets}");
        $this->line("  Archivos encontrados: {$foundDatasheets}");

        if (count($this->missingDatasheets) > 0) {
            $this->error("  Archivos faltantes: " . count($this->missingDatasheets));
        } else {
            $this->info("  Archivos faltantes: 0");
        }

        $this->newLine();

        // Show some missing files
        if (count($this->missingImages) > 0) {
            $this->warn('Primeras 10 imagenes faltantes:');
            foreach (array_slice($this->missingImages, 0, 10) as $missing) {
                $this->line("  [{$missing['sku']}] {$missing['file']}");
            }
            if (count($this->missingImages) > 10) {
                $this->line("  ... y " . (count($this->missingImages) - 10) . " mas");
            }
            $this->newLine();
        }

        if (count($this->missingDatasheets) > 0) {
            $this->warn('Primeras 10 fichas tecnicas faltantes:');
            foreach (array_slice($this->missingDatasheets, 0, 10) as $missing) {
                $this->line("  [{$missing['sku']}] {$missing['file']}");
            }
            if (count($this->missingDatasheets) > 10) {
                $this->line("  ... y " . (count($this->missingDatasheets) - 10) . " mas");
            }
            $this->newLine();
        }

        // Storage paths
        $this->info('Rutas de almacenamiento verificadas:');
        $this->line("  Imagenes: " . storage_path('app/public/' . $this->option('images-path')));
        $this->line("  Fichas tecnicas: " . storage_path('app/public/' . $this->option('datasheets-path')));
    }

    private function generateReport(): void
    {
        $reportPath = storage_path('app/missing_files_report_' . date('Y-m-d_His') . '.txt');

        $content = "REPORTE DE ARCHIVOS FALTANTES\n";
        $content .= "Generado: " . now()->toDateTimeString() . "\n";
        $content .= str_repeat('=', 60) . "\n\n";

        $content .= "IMAGENES FALTANTES (" . count($this->missingImages) . "):\n";
        $content .= str_repeat('-', 40) . "\n";
        foreach ($this->missingImages as $missing) {
            $content .= "ID: {$missing['id']} | SKU: {$missing['sku']} | Archivo: {$missing['file']}\n";
        }

        $content .= "\n\nFICHAS TECNICAS FALTANTES (" . count($this->missingDatasheets) . "):\n";
        $content .= str_repeat('-', 40) . "\n";
        foreach ($this->missingDatasheets as $missing) {
            $content .= "ID: {$missing['id']} | SKU: {$missing['sku']} | Archivo: {$missing['file']}\n";
        }

        file_put_contents($reportPath, $content);

        $this->info("Reporte generado: {$reportPath}");
    }
}
