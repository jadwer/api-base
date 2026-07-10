<?php

namespace Modules\SatCatalogs\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Modules\SatCatalogs\Models\SatClaveProdServ;
use Modules\SatCatalogs\Models\SatClaveUnidad;
use Modules\SatCatalogs\Models\SatFormaPago;
use Modules\SatCatalogs\Models\SatTasaOCuota;

/**
 * Sync the SAT catalogs from phpcfdi/resources-sat-catalogs.
 *
 * Downloads the latest release asset (catalogs.db.bz2, a SQLite database),
 * decompresses it and upserts ONLY the 4 tables this project uses:
 *
 *   Source (catalogs.db)            Target (our DB)
 *   cfdi_40_productos_servicios ->  sat_clave_prod_serv
 *   cfdi_40_claves_unidades     ->  sat_clave_unidad
 *   cfdi_40_formas_pago         ->  sat_forma_pago
 *   cfdi_40_reglas_tasa_cuota   ->  sat_tasa_o_cuota (only "Fijo" rows)
 *
 * Use --path to point to a local catalogs.db (tests, air-gapped installs).
 */
class SyncSatCatalogs extends Command
{
    protected $signature = 'sat:sync-catalogs
                            {--path= : Local catalogs.db file (skips download)}';

    protected $description = 'Sync SAT catalogs (ClaveProdServ, ClaveUnidad, FormaPago, TasaOCuota) from phpcfdi/resources-sat-catalogs';

    protected int $batchSize;

    public function handle(): int
    {
        $this->batchSize = (int) config('satcatalogs.sync.batch_size', 500);
        $cleanup = [];

        try {
            $path = $this->option('path');

            if ($path) {
                if (!is_file($path)) {
                    $this->error("File not found: {$path}");

                    return self::FAILURE;
                }
                $dbPath = $path;
            } else {
                $bz2Path = $this->downloadLatestRelease();
                $cleanup[] = $bz2Path;

                $dbPath = $this->decompress($bz2Path);
                $cleanup[] = $dbPath;
            }

            $source = new \PDO('sqlite:' . $dbPath, null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);

            $counts = [
                'sat_clave_prod_serv' => $this->syncClaveProdServ($source),
                'sat_clave_unidad' => $this->syncClaveUnidad($source),
                'sat_forma_pago' => $this->syncFormaPago($source),
                'sat_tasa_o_cuota' => $this->syncTasaOCuota($source),
            ];

            $this->newLine();
            $this->table(
                ['Tabla', 'Filas sincronizadas'],
                collect($counts)->map(fn ($count, $table) => [$table, $count])->values()->all()
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Sync failed: ' . $e->getMessage());

            return self::FAILURE;
        } finally {
            foreach ($cleanup as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * Download the catalogs.db.bz2 asset from the latest GitHub release.
     */
    protected function downloadLatestRelease(): string
    {
        $apiUrl = config('satcatalogs.sync.release_api_url');
        $assetName = config('satcatalogs.sync.asset_name', 'catalogs.db.bz2');

        $this->info("Fetching latest release from {$apiUrl}...");

        $release = Http::withHeaders(['User-Agent' => 'api-base sat:sync-catalogs'])
            ->timeout(30)
            ->get($apiUrl)
            ->throw()
            ->json();

        $asset = collect($release['assets'] ?? [])->firstWhere('name', $assetName);

        if (!$asset) {
            throw new \RuntimeException("Asset {$assetName} not found in release " . ($release['tag_name'] ?? '?'));
        }

        $this->info("Downloading {$assetName} (release " . ($release['tag_name'] ?? '?') . ')...');

        $target = tempnam(sys_get_temp_dir(), 'sat_catalogs_') . '.db.bz2';

        $response = Http::withHeaders(['User-Agent' => 'api-base sat:sync-catalogs'])
            ->timeout(300)
            ->sink($target)
            ->get($asset['browser_download_url']);

        $response->throw();

        return $target;
    }

    /**
     * Decompress the .bz2 file. Prefers ext-bz2, falls back to the bunzip2
     * binary, and fails with a clear message when neither is available.
     */
    protected function decompress(string $bz2Path): string
    {
        $dbPath = preg_replace('/\.bz2$/', '', $bz2Path) ?: $bz2Path . '.db';

        if (function_exists('bzopen')) {
            $this->info('Decompressing with ext-bz2...');

            $in = bzopen($bz2Path, 'r');
            $out = fopen($dbPath, 'wb');

            if ($in === false || $out === false) {
                throw new \RuntimeException('Could not open files for decompression');
            }

            while (!feof($in)) {
                $chunk = bzread($in, 8192);
                if ($chunk === false) {
                    throw new \RuntimeException('bzread failed while decompressing');
                }
                fwrite($out, $chunk);
            }

            bzclose($in);
            fclose($out);

            return $dbPath;
        }

        $bunzip2 = trim((string) shell_exec('command -v bunzip2 2>/dev/null'));

        if ($bunzip2 !== '') {
            $this->info('Decompressing with bunzip2 (ext-bz2 not available)...');

            $result = null;
            // -k keeps the .bz2 (we clean it up ourselves), -f overwrites.
            exec(sprintf('%s -kf %s 2>&1', escapeshellcmd($bunzip2), escapeshellarg($bz2Path)), $output, $result);

            if ($result !== 0 || !is_file($dbPath)) {
                throw new \RuntimeException('bunzip2 failed: ' . implode("\n", $output));
            }

            return $dbPath;
        }

        throw new \RuntimeException(
            'Cannot decompress catalogs.db.bz2: neither PHP ext-bz2 nor the bunzip2 binary are available. '
            . 'Install one of them, or decompress the file manually and run sat:sync-catalogs --path=/ruta/catalogs.db'
        );
    }

    /**
     * cfdi_40_productos_servicios -> sat_clave_prod_serv
     * Columns: id, texto, iva_trasladado, ieps_trasladado, complemento,
     *          vigencia_desde, vigencia_hasta, estimulo_frontera, similares
     */
    protected function syncClaveProdServ(\PDO $source): int
    {
        return $this->upsertInBatches(
            $source,
            'SELECT id, texto, iva_trasladado, ieps_trasladado, similares, vigencia_hasta FROM cfdi_40_productos_servicios',
            function (array $row): array {
                return [
                    'clave' => $row['id'],
                    'descripcion' => mb_substr($row['texto'], 0, 255),
                    'incluye_iva' => $this->toNullableBool($row['iva_trasladado']),
                    'incluye_ieps' => $this->toNullableBool($row['ieps_trasladado']),
                    'palabras_similares' => ($row['similares'] ?? '') !== '' ? $row['similares'] : null,
                    'vigencia_hasta' => ($row['vigencia_hasta'] ?? '') !== '' ? $row['vigencia_hasta'] : null,
                ];
            },
            fn (array $batch) => SatClaveProdServ::upsert(
                $batch,
                ['clave'],
                ['descripcion', 'incluye_iva', 'incluye_ieps', 'palabras_similares', 'vigencia_hasta']
            )
        );
    }

    /**
     * cfdi_40_claves_unidades -> sat_clave_unidad
     * Columns: id, texto, descripcion, notas, vigencia_desde, vigencia_hasta, simbolo
     */
    protected function syncClaveUnidad(\PDO $source): int
    {
        return $this->upsertInBatches(
            $source,
            'SELECT id, texto, descripcion, simbolo FROM cfdi_40_claves_unidades',
            function (array $row): array {
                return [
                    'clave' => $row['id'],
                    'nombre' => mb_substr($row['texto'], 0, 255),
                    'descripcion' => ($row['descripcion'] ?? '') !== '' ? $row['descripcion'] : null,
                    'simbolo' => ($row['simbolo'] ?? '') !== '' ? mb_substr($row['simbolo'], 0, 30) : null,
                ];
            },
            fn (array $batch) => SatClaveUnidad::upsert(
                $batch,
                ['clave'],
                ['nombre', 'descripcion', 'simbolo']
            )
        );
    }

    /**
     * cfdi_40_formas_pago -> sat_forma_pago
     * Columns: id, texto, es_bancarizado, ... (we only use id + texto)
     */
    protected function syncFormaPago(\PDO $source): int
    {
        return $this->upsertInBatches(
            $source,
            'SELECT id, texto FROM cfdi_40_formas_pago',
            fn (array $row): array => [
                'clave' => $row['id'],
                'descripcion' => mb_substr($row['texto'], 0, 100),
            ],
            fn (array $batch) => SatFormaPago::upsert($batch, ['clave'], ['descripcion'])
        );
    }

    /**
     * cfdi_40_reglas_tasa_cuota -> sat_tasa_o_cuota
     * Columns: tipo (Fijo|Rango), minimo, valor, impuesto, factor (Tasa|Cuota),
     *          traslado, retencion, vigencia_desde, vigencia_hasta
     *
     * Only "Fijo" rows are imported (concrete selectable rates). "Rango" rows
     * describe allowed ranges, not values a dropdown can offer. There is no
     * natural PK, so rows are matched with firstOrCreate (the table is tiny).
     */
    protected function syncTasaOCuota(\PDO $source): int
    {
        $statement = $source->query(
            "SELECT tipo, valor, impuesto, factor, traslado, retencion FROM cfdi_40_reglas_tasa_cuota WHERE tipo = 'Fijo'"
        );

        $count = 0;

        foreach ($statement as $row) {
            // Normalize e.g. "IVA Crédito aplicado del 50%" -> IVA
            $impuesto = collect(['IVA', 'ISR', 'IEPS'])
                ->first(fn ($name) => str_starts_with($row['impuesto'], $name)) ?? $row['impuesto'];

            SatTasaOCuota::firstOrCreate([
                'tipo' => $row['factor'], // Tasa | Cuota
                'impuesto' => mb_substr($impuesto, 0, 10),
                'valor' => (float) $row['valor'],
                'retencion' => (bool) $row['retencion'],
                'traslado' => (bool) $row['traslado'],
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Stream rows from the source SQLite and upsert them in batches.
     */
    protected function upsertInBatches(\PDO $source, string $query, callable $map, callable $flush): int
    {
        $statement = $source->query($query);

        $batch = [];
        $count = 0;

        foreach ($statement as $row) {
            $batch[] = $map($row);
            $count++;

            if (count($batch) >= $this->batchSize) {
                $flush($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            $flush($batch);
        }

        return $count;
    }

    /**
     * The source stores tri-state flags as '' (unknown), '0' and '1'.
     */
    protected function toNullableBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (bool) $value;
    }
}
