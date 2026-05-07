<?php

namespace Modules\AppConfig\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Modules\AppConfig\Models\AppSetting;

class AppSettingController extends Controller
{
    /**
     * List all settings (admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user || (!$user->can('app-settings.index') && !$user->hasRole(['god', 'admin', 'administrator']))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return response()->json([
            'data' => AppSetting::getAllGrouped(),
        ]);
    }

    /**
     * Get all settings for a group (admin only).
     */
    public function byGroup(Request $request, string $group): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user || (!$user->can('app-settings.index') && !$user->hasRole(['god', 'admin', 'administrator']))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return response()->json([
            'data' => AppSetting::getByGroup($group),
        ]);
    }

    /**
     * Update a setting value (admin only).
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user || (!$user->can('app-settings.update') && !$user->hasRole(['god', 'admin', 'administrator']))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $setting = AppSetting::where('key', $key)->first();

        if (!$setting) {
            return response()->json(['error' => 'Setting not found'], 404);
        }

        $request->validate([
            'value' => ['present'],
        ]);

        AppSetting::set($key, $request->input('value'));

        $setting->refresh();

        return response()->json([
            'data' => [
                'key' => $setting->key,
                'value' => AppSetting::get($key),
                'type' => $setting->type,
                'label' => $setting->label,
            ],
            'message' => 'Setting updated successfully',
        ]);
    }

    /**
     * Send a test email to verify mail configuration.
     * POST /api/v1/app-settings/test-email
     */
    public function testEmail(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user || (!$user->can('app-settings.update') && !$user->hasRole(['god', 'admin', 'administrator']))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $toEmail = $request->input('email');
            $fromEmail = config('mail.from.address');
            $fromName = config('mail.from.name');

            $companyName = app(\Modules\AppConfig\Services\AppSettingResolver::class)
                ->get('company.name', config('app.name', 'Demo Company'));

            Mail::raw(
                "Este es un correo de prueba del sistema {$companyName}.\n\n"
                . "Si recibiste este correo, la configuracion SMTP es correcta.\n\n"
                . "Remitente configurado: {$fromEmail}\n"
                . "Fecha: " . now()->format('d/m/Y H:i:s'),
                function ($message) use ($toEmail, $fromName) {
                    $message->to($toEmail)
                        ->subject('Correo de prueba - ' . $fromName);
                }
            );

            return response()->json([
                'message' => 'Correo de prueba enviado exitosamente a ' . $toEmail,
                'from' => $fromEmail,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al enviar correo de prueba',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Public settings (company + branding, no auth required).
     */
    public function publicSettings(): JsonResponse
    {
        $company = AppSetting::getByGroup('company');
        $branding = AppSetting::getByGroup('branding');
        $social = AppSetting::getByGroup('social');

        return response()->json([
            'data' => [
                'company' => $company,
                'branding' => $branding,
                'social' => $social,
            ],
        ]);
    }
}
