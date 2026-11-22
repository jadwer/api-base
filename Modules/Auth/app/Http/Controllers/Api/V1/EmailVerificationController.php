<?php

namespace Modules\Auth\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Notifications\VerifyEmailNotification;
use Modules\Auth\Support\EmailVerificationToken;
use Modules\User\Models\User;

class EmailVerificationController extends Controller
{
    /**
     * Send a verification email to the authenticated user.
     */
    public function sendVerificationEmail(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Tu correo ya ha sido verificado.',
            ]);
        }

        $user->notify(new VerifyEmailNotification());

        return response()->json([
            'message' => 'Se ha enviado un enlace de verificación a tu correo.',
        ]);
    }

    /**
     * Verify the user's email address.
     */
    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        $result = EmailVerificationToken::check($user, $hash);

        if ($result === EmailVerificationToken::INVALID) {
            return response()->json([
                'message' => 'Enlace de verificación inválido.',
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Tu correo ya ha sido verificado.',
            ]);
        }

        // Enlace vencido o con el formato anterior: no verifica; se manda uno
        // nuevo al correo registrado (la ruta va con throttle).
        if ($result !== EmailVerificationToken::VALID) {
            $user->notify(new VerifyEmailNotification());

            return response()->json([
                'message' => 'El enlace de verificación venció. Te enviamos uno nuevo a tu correo.',
            ], 403);
        }

        $user->markEmailAsVerified();

        // El registro publico nace inactive (hardening 2026-09):
        // verificar el correo es lo que habilita la cuenta. Solo
        // inactive -> active; un usuario banned NO se reactiva aqui.
        if ($user->status === 'inactive') {
            $user->forceFill(['status' => 'active'])->save();
        }

        return response()->json([
            'message' => 'Tu correo ha sido verificado exitosamente. Ya puedes iniciar sesión.',
        ]);
    }
}
