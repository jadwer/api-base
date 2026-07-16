<?php

namespace Modules\Billing\Services\PAC;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Billing\Exceptions\PacException;

/**
 * SW Sapien PAC Integration Service
 *
 * Handles communication with SW Sapien PAC for CFDI stamping and cancellation
 * API Documentation: https://developers.sw.com.mx/
 */
class SWPacService
{
    protected string $baseUrl;
    protected ?string $token;
    protected ?string $user;
    protected ?string $password;
    protected int $timeout;
    protected int $retryAttempts;
    protected int $retryDelay;
    protected bool $enabled;

    public function __construct()
    {
        $config = config('billing.sw_pac');

        $this->baseUrl = $config['url'];
        $this->token = $config['token'];
        $this->user = $config['user'];
        $this->password = $config['password'];
        $this->timeout = $config['timeout'];
        $this->retryAttempts = $config['retry_attempts'];
        $this->retryDelay = $config['retry_delay'];
        $this->enabled = $config['enabled'];
    }

    /**
     * Check if PAC integration is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Stamp CFDI XML via SW's Emision (Issue) service.
     *
     * Emision recibe el XML SIN sellar, SW lo sella con el CSD previamente
     * cargado (uploadCertificate) eligiendolo por el RFC del Emisor, y luego
     * lo timbra. El endpoint /b64 es multipart/form-data con el campo `xml`
     * (el XML en base64), no un JSON. Este metodo mantiene el nombre stamp()
     * para no romper a CFDIStampingService.
     *
     * @param string $xmlContent The unsealed XML content to issue+stamp
     * @return array Response from PAC with UUID and stamped XML
     * @throws PacException
     */
    public function stamp(string $xmlContent): array
    {
        if (!$this->enabled) {
            throw new PacException('SW PAC integration is not enabled');
        }

        try {
            // SW Sapien Emision /b64: multipart con el campo `xml` = XML sin sellar
            // en base64. NO es JSON (eso da "Xml vacio" o error de estructura).
            $xmlBase64 = base64_encode($xmlContent);

            $response = Http::withToken($this->getAuthToken())
                ->timeout($this->timeout)
                ->retry($this->retryAttempts, $this->retryDelay)
                ->attach('xml', $xmlBase64, 'cfdi.txt')
                ->post("{$this->baseUrl}/cfdi33/issue/v4/b64");

            if (!$response->successful()) {
                Log::error('SW PAC issue error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new PacException(
                    'Error al timbrar CFDI: ' . ($response->json()['message'] ?? 'Error desconocido'),
                    $response->status()
                );
            }

            $data = $response->json('data');

            if (!$data || !isset($data['uuid'])) {
                throw new PacException('Respuesta inválida del PAC: falta UUID');
            }

            return [
                'uuid' => $data['uuid'],
                'fecha_timbrado' => $data['fechaTimbrado'] ?? now(),
                'xml_timbrado' => base64_decode($data['cfdi']),
                'cadena_original' => $data['cadenaOriginalSAT'] ?? null,
                'sello_sat' => $data['selloSAT'] ?? null,
                'sello_cfdi' => $data['selloCFDI'] ?? null,
                'certificado_sat' => $data['noCertificadoSAT'] ?? null,
                'qr_code' => $data['qrCode'] ?? null,
                'pac_response' => $response->json(),
            ];
        } catch (PacException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('SW PAC issue exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new PacException('Error al conectar con el PAC: ' . $e->getMessage());
        }
    }

    /**
     * Upload a CSD (certificate + key) to SW's certificate store (ADT).
     *
     * Required once per emisor before using the Emision service. SW stores the
     * CSD keyed by RFC and picks it automatically by matching the Emisor RFC in
     * the XML. Idempotent on SW's side: re-uploading updates the stored CSD.
     *
     * @param string $cerContent Raw contents of the .cer file
     * @param string $keyContent Raw contents of the .key file
     * @param string $keyPassword Password of the .key
     * @return array SW response
     * @throws PacException
     */
    public function uploadCertificate(string $cerContent, string $keyContent, string $keyPassword): array
    {
        if (!$this->enabled) {
            throw new PacException('SW PAC integration is not enabled');
        }

        try {
            $response = Http::withToken($this->getAuthToken())
                ->timeout($this->timeout)
                ->retry($this->retryAttempts, $this->retryDelay)
                ->post("{$this->baseUrl}/certificates/save", [
                    'type' => 'stamp',
                    'b64Cer' => base64_encode($cerContent),
                    'b64Key' => base64_encode($keyContent),
                    'password' => $keyPassword,
                ]);

            if (!$response->successful()) {
                Log::error('SW PAC certificate upload error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new PacException(
                    'Error al cargar el CSD en el PAC: ' . ($response->json()['message'] ?? 'Error desconocido'),
                    $response->status()
                );
            }

            // SW puede devolver `data` como objeto, string o null; normalizamos
            // a array para el contrato del metodo.
            $body = $response->json();
            return is_array($body) ? $body : ['response' => $body];
        } catch (PacException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('SW PAC certificate upload exception', [
                'message' => $e->getMessage(),
            ]);

            throw new PacException('Error al cargar el CSD en el PAC: ' . $e->getMessage());
        }
    }

    /**
     * Cancel CFDI
     *
     * @param string $uuid The UUID of the CFDI to cancel
     * @param string $rfcEmisor RFC of the issuer
     * @param string $rfcReceptor RFC of the receiver
     * @param float $total Total amount of the invoice
     * @param string $motivoCancelacion Cancellation reason code (01-04)
     * @param string|null $uuidSustitucion UUID of replacement CFDI (required for motive 01)
     * @return array Response from PAC
     * @throws PacException
     */
    public function cancel(
        string $uuid,
        string $rfcEmisor,
        string $rfcReceptor,
        float $total,
        string $motivoCancelacion = '02',
        ?string $uuidSustitucion = null
    ): array {
        if (!$this->enabled) {
            throw new PacException('SW PAC integration is not enabled');
        }

        // Validate cancellation motive
        $validMotives = ['01', '02', '03', '04'];
        if (!in_array($motivoCancelacion, $validMotives)) {
            throw new PacException('Motivo de cancelación inválido');
        }

        // Motive 01 (Comprobante emitido con errores con relación) requires UUID de sustitución
        if ($motivoCancelacion === '01' && empty($uuidSustitucion)) {
            throw new PacException('El motivo 01 requiere UUID de sustitución');
        }

        try {
            // Load certificate data from company settings
            $settings = \Modules\Billing\Models\CompanySetting::where('is_active', true)->first();

            if (!$settings || empty($settings->certificate_file) || empty($settings->key_file)) {
                throw new PacException('No se encontraron certificados configurados para la cancelacion CFDI');
            }

            $cerPath = storage_path('app/' . $settings->certificate_file);
            $keyPath = storage_path('app/' . $settings->key_file);

            if (!file_exists($cerPath) || !file_exists($keyPath)) {
                throw new PacException('Los archivos de certificado no existen en el servidor');
            }

            $payload = [
                'rfc' => $rfcEmisor,
                'b64Cer' => base64_encode(file_get_contents($cerPath)),
                'b64Key' => base64_encode(file_get_contents($keyPath)),
                'password' => $settings->key_password ?? '',
                'uuids' => [
                    [
                        'uuid' => $uuid,
                        'motivo' => $motivoCancelacion,
                        'folioSustitucion' => $uuidSustitucion,
                    ]
                ],
            ];

            $response = Http::withToken($this->getAuthToken())
                ->timeout($this->timeout)
                ->retry($this->retryAttempts, $this->retryDelay)
                ->post("{$this->baseUrl}/cfdi33/cancel/csd", $payload);

            if (!$response->successful()) {
                Log::error('SW PAC cancel error', [
                    'uuid' => $uuid,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new PacException(
                    'Error al cancelar CFDI: ' . ($response->json()['message'] ?? 'Error desconocido'),
                    $response->status()
                );
            }

            $data = $response->json('data');

            return [
                'status' => $data['status'] ?? 'cancelled',
                'fecha_cancelacion' => $data['fechaCancelacion'] ?? now(),
                'acuse' => $data['acuse'] ?? null,
                'pac_response' => $response->json(),
            ];
        } catch (PacException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('SW PAC cancel exception', [
                'uuid' => $uuid,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new PacException('Error al conectar con el PAC: ' . $e->getMessage());
        }
    }

    /**
     * Get cancellation status
     *
     * @param string $uuid
     * @param string $rfcEmisor
     * @return array
     * @throws PacException
     */
    public function getCancellationStatus(string $uuid, string $rfcEmisor): array
    {
        if (!$this->enabled) {
            throw new PacException('SW PAC integration is not enabled');
        }

        try {
            $response = Http::withToken($this->getAuthToken())
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}/cfdi33/cancel/status/{$rfcEmisor}/{$uuid}");

            if (!$response->successful()) {
                throw new PacException(
                    'Error al consultar estatus de cancelación',
                    $response->status()
                );
            }

            return $response->json('data');
        } catch (PacException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PacException('Error al consultar estatus: ' . $e->getMessage());
        }
    }

    /**
     * Validate CFDI with SAT
     *
     * @param string $uuid
     * @param string $rfcEmisor
     * @param string $rfcReceptor
     * @param float $total
     * @return array
     * @throws PacException
     */
    public function validateWithSAT(
        string $uuid,
        string $rfcEmisor,
        string $rfcReceptor,
        float $total
    ): array {
        if (!$this->enabled) {
            throw new PacException('SW PAC integration is not enabled');
        }

        try {
            // Format total to 6 decimal places as required by SAT
            $totalFormatted = number_format($total, 6, '.', '');

            $url = sprintf(
                '%s/validate/cfdi/%s/%s/%s/%s',
                $this->baseUrl,
                $uuid,
                $rfcEmisor,
                $rfcReceptor,
                $totalFormatted
            );

            $response = Http::withToken($this->getAuthToken())
                ->timeout($this->timeout)
                ->get($url);

            if (!$response->successful()) {
                throw new PacException(
                    'Error al validar CFDI con SAT',
                    $response->status()
                );
            }

            $data = $response->json('data');

            return [
                'status' => $data['CodigoEstatus'] ?? null,
                'es_cancelable' => $data['EsCancelable'] ?? null,
                'estado' => $data['Estado'] ?? null,
                'validacion_efos' => $data['ValidacionEFOS'] ?? null,
            ];
        } catch (PacException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PacException('Error al validar con SAT: ' . $e->getMessage());
        }
    }

    /**
     * Get authentication token
     * Uses either pre-configured token or generates one using user/password
     *
     * @return string
     * @throws PacException
     */
    protected function getAuthToken(): string
    {
        // If token is configured, use it
        if (!empty($this->token)) {
            return $this->token;
        }

        // Otherwise, authenticate with user/password
        if (empty($this->user) || empty($this->password)) {
            throw new PacException('SW PAC credentials not configured');
        }

        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/security/authenticate", [
                    'user' => $this->user,
                    'password' => $this->password,
                ]);

            if (!$response->successful()) {
                throw new PacException('Error al autenticar con SW PAC');
            }

            $token = $response->json('data.token');

            if (empty($token)) {
                throw new PacException('No se recibió token de autenticación');
            }

            // Cache token for subsequent requests in this instance
            $this->token = $token;

            return $token;
        } catch (PacException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PacException('Error al obtener token: ' . $e->getMessage());
        }
    }

    /**
     * Test connection to PAC
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            $token = $this->getAuthToken();
            return !empty($token);
        } catch (\Exception $e) {
            Log::error('SW PAC connection test failed', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get account balance and stamps information
     *
     * @return array
     * @throws PacException
     */
    public function getBalance(): array
    {
        if (!$this->enabled) {
            throw new PacException('SW PAC integration is not enabled');
        }

        try {
            $response = Http::withToken($this->getAuthToken())
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}/account/balance");

            if (!$response->successful()) {
                Log::error('SW PAC balance error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new PacException(
                    'Error al consultar saldo: ' . ($response->json()['message'] ?? 'Error desconocido'),
                    $response->status()
                );
            }

            $data = $response->json('data');

            return [
                'balance' => $data['saldoTimbres'] ?? $data['balance'] ?? null,
                'stamps_used' => $data['timbresUtilizados'] ?? $data['stampsUsed'] ?? null,
                'stamps_available' => $data['timbresDisponibles'] ?? $data['stampsAvailable'] ?? null,
                'unlimited' => $data['unlimited'] ?? false,
                'expiration_date' => $data['fechaExpiracion'] ?? $data['expirationDate'] ?? null,
            ];
        } catch (PacException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('SW PAC balance exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new PacException('Error al consultar saldo PAC: ' . $e->getMessage());
        }
    }
}
