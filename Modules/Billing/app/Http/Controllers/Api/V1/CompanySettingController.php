<?php

namespace Modules\Billing\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Services\PAC\SWPacService;

class CompanySettingController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;

    /**
     * Upload and validate certificate file (.cer)
     *
     * @param CompanySetting $companySetting
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadCertificate(CompanySetting $companySetting, Request $request): JsonResponse
    {
        // Check permission
        if (Gate::denies('billing.company-settings.upload-certificate')) {
            abort(403, 'No tiene permisos para subir certificados');
        }

        // Validate request
        $request->validate([
            'certificate' => ['required', 'file', 'max:50'], // Max 50KB for .cer files
        ]);

        try {
            $file = $request->file('certificate');
            $extension = strtolower($file->getClientOriginalExtension());

            // Validate file extension
            if ($extension !== 'cer') {
                return response()->json([
                    'message' => 'El archivo debe ser un certificado .cer',
                ], 422);
            }

            // Read file content
            $content = file_get_contents($file->getRealPath());

            // Validate it's a valid certificate
            $certInfo = $this->validateCertificate($content);

            if (!$certInfo['valid']) {
                return response()->json([
                    'message' => 'El certificado no es válido: ' . $certInfo['error'],
                ], 422);
            }

            // Store certificate securely
            $filename = 'certificates/' . $companySetting->id . '_' . time() . '.cer';
            Storage::disk('local')->put($filename, $content);

            // Update company setting
            $companySetting->update([
                'certificate_file' => $filename,
            ]);

            return response()->json([
                'message' => 'Certificado subido correctamente',
                'data' => [
                    'id' => $companySetting->id,
                    'certificate_serial' => $certInfo['serial'] ?? null,
                    'certificate_valid_from' => $certInfo['valid_from'] ?? null,
                    'certificate_valid_to' => $certInfo['valid_to'] ?? null,
                    'certificate_subject' => $certInfo['subject'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar el certificado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload and validate private key file (.key)
     *
     * @param CompanySetting $companySetting
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadKey(CompanySetting $companySetting, Request $request): JsonResponse
    {
        // Check permission
        if (Gate::denies('billing.company-settings.upload-key')) {
            abort(403, 'No tiene permisos para subir llaves privadas');
        }

        // Validate request
        $request->validate([
            'key' => ['required', 'file', 'max:50'], // Max 50KB for .key files
            'password' => ['required', 'string'],
        ]);

        try {
            $file = $request->file('key');
            $password = $request->input('password');
            $extension = strtolower($file->getClientOriginalExtension());

            // Validate file extension
            if ($extension !== 'key') {
                return response()->json([
                    'message' => 'El archivo debe ser una llave privada .key',
                ], 422);
            }

            // Read file content
            $content = file_get_contents($file->getRealPath());

            // Validate key with password
            $keyValid = $this->validatePrivateKey($content, $password);

            if (!$keyValid['valid']) {
                return response()->json([
                    'message' => 'La llave privada no es válida o la contraseña es incorrecta',
                    'error' => $keyValid['error'] ?? null,
                ], 422);
            }

            // Store key securely
            $filename = 'keys/' . $companySetting->id . '_' . time() . '.key';
            Storage::disk('local')->put($filename, $content);

            // Update company setting with encrypted password
            $companySetting->update([
                'key_file' => $filename,
                'key_password' => $password, // Model has encrypted cast
            ]);

            return response()->json([
                'message' => 'Llave privada subida correctamente',
                'data' => [
                    'id' => $companySetting->id,
                    'key_uploaded' => true,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar la llave privada',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test PAC connection
     *
     * @param CompanySetting $companySetting
     * @param SWPacService $pacService
     * @return JsonResponse
     */
    public function testPac(CompanySetting $companySetting, SWPacService $pacService): JsonResponse
    {
        // Check permission
        if (Gate::denies('billing.company-settings.test-pac')) {
            abort(403, 'No tiene permisos para probar conexión PAC');
        }

        try {
            // Check if PAC is configured
            if (!$companySetting->hasPACConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La configuración PAC no está completa. Verifique usuario, contraseña y proveedor.',
                ]);
            }

            // Check if PAC service is enabled
            if (!$pacService->isEnabled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El servicio PAC no está habilitado en la configuración del sistema.',
                ]);
            }

            // Try to get balance/status from PAC (this validates credentials)
            $balanceInfo = $pacService->getBalance();

            return response()->json([
                'success' => true,
                'message' => 'Conexión exitosa con el PAC',
                'data' => [
                    'provider' => $companySetting->pac_provider,
                    'mode' => $companySetting->pac_production_mode ? 'production' : 'sandbox',
                    'balance' => $balanceInfo['balance'] ?? null,
                    'stamps_used' => $balanceInfo['stamps_used'] ?? null,
                    'stamps_available' => $balanceInfo['stamps_available'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al conectar con el PAC: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate a certificate file
     *
     * @param string $content Certificate content
     * @return array Validation result
     */
    protected function validateCertificate(string $content): array
    {
        try {
            // Try to parse as DER format (SAT uses DER)
            $cert = @openssl_x509_read($content);

            if (!$cert) {
                // Try converting from DER to PEM
                $pem = "-----BEGIN CERTIFICATE-----\n" .
                    chunk_split(base64_encode($content), 64, "\n") .
                    "-----END CERTIFICATE-----";
                $cert = @openssl_x509_read($pem);
            }

            if (!$cert) {
                return [
                    'valid' => false,
                    'error' => 'No se pudo leer el certificado',
                ];
            }

            $certInfo = openssl_x509_parse($cert);

            if (!$certInfo) {
                return [
                    'valid' => false,
                    'error' => 'No se pudo parsear el certificado',
                ];
            }

            // Check if certificate is expired
            $validTo = $certInfo['validTo_time_t'] ?? 0;
            if ($validTo < time()) {
                return [
                    'valid' => false,
                    'error' => 'El certificado está expirado',
                ];
            }

            return [
                'valid' => true,
                'serial' => $certInfo['serialNumberHex'] ?? null,
                'valid_from' => isset($certInfo['validFrom_time_t'])
                    ? date('Y-m-d H:i:s', $certInfo['validFrom_time_t'])
                    : null,
                'valid_to' => isset($certInfo['validTo_time_t'])
                    ? date('Y-m-d H:i:s', $certInfo['validTo_time_t'])
                    : null,
                'subject' => $certInfo['subject']['CN'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate a private key file with password
     *
     * @param string $content Key content
     * @param string $password Key password
     * @return array Validation result
     */
    protected function validatePrivateKey(string $content, string $password): array
    {
        try {
            // SAT keys are in DER format encrypted with PKCS#8
            // Try to convert and load the key

            // First try as PEM
            $key = @openssl_pkey_get_private($content, $password);

            if (!$key) {
                // Try as DER - convert to PEM first
                $pem = "-----BEGIN ENCRYPTED PRIVATE KEY-----\n" .
                    chunk_split(base64_encode($content), 64, "\n") .
                    "-----END ENCRYPTED PRIVATE KEY-----";
                $key = @openssl_pkey_get_private($pem, $password);
            }

            if (!$key) {
                // Try RSA format
                $pem = "-----BEGIN RSA PRIVATE KEY-----\n" .
                    chunk_split(base64_encode($content), 64, "\n") .
                    "-----END RSA PRIVATE KEY-----";
                $key = @openssl_pkey_get_private($pem, $password);
            }

            if (!$key) {
                return [
                    'valid' => false,
                    'error' => 'No se pudo cargar la llave privada. Verifique que la contraseña sea correcta.',
                ];
            }

            // Get key details
            $details = openssl_pkey_get_details($key);

            return [
                'valid' => true,
                'bits' => $details['bits'] ?? null,
                'type' => $details['type'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
