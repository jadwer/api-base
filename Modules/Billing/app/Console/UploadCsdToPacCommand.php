<?php

namespace Modules\Billing\Console;

use Illuminate\Console\Command;
use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Services\PAC\SWPacService;

/**
 * Upload the active company's CSD (certificate + key) to the PAC store.
 *
 * Required once per emisor before using SW's Emision (Issue) service, which
 * seals the XML server-side with the CSD matched by the Emisor RFC. Re-running
 * updates the stored CSD on the PAC (idempotent on their side).
 */
class UploadCsdToPacCommand extends Command
{
    protected $signature = 'billing:upload-csd {--rfc= : RFC del emisor (por defecto la empresa activa)}';

    protected $description = 'Sube el CSD (cer/key) de la empresa al PAC para el servicio de Emision';

    public function handle(SWPacService $pacService): int
    {
        if (!$pacService->isEnabled()) {
            $this->error('El servicio PAC no esta habilitado (SW_PAC_ENABLED).');
            return self::FAILURE;
        }

        $rfc = $this->option('rfc');
        $settings = $rfc
            ? CompanySetting::byRFC($rfc)->first()
            : CompanySetting::where('is_active', true)->first();

        if (!$settings) {
            $this->error($rfc ? "No hay empresa con RFC {$rfc}." : 'No hay empresa activa configurada.');
            return self::FAILURE;
        }

        if (empty($settings->certificate_file) || empty($settings->key_file) || empty($settings->key_password)) {
            $this->error('La empresa no tiene CSD completo (certificate_file, key_file, key_password).');
            return self::FAILURE;
        }

        $cerPath = storage_path('app/' . $settings->certificate_file);
        $keyPath = storage_path('app/' . $settings->key_file);

        if (!file_exists($cerPath) || !file_exists($keyPath)) {
            $this->error('Los archivos del CSD no existen en el servidor.');
            return self::FAILURE;
        }

        $this->info("Subiendo CSD de {$settings->rfc} al PAC...");

        try {
            $pacService->uploadCertificate(
                file_get_contents($cerPath),
                file_get_contents($keyPath),
                $settings->key_password
            );
        } catch (\Throwable $e) {
            $this->error('Fallo la carga del CSD: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("CSD de {$settings->rfc} cargado en el PAC. Ya puede timbrar por Emision.");
        return self::SUCCESS;
    }
}
