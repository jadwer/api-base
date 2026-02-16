<?php

namespace Modules\Auth\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Notifications\VerifyEmailNotification;
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
            'message' => 'Se ha enviado un enlace de verificacion a tu correo.',
        ]);
    }

    /**
     * Verify the user's email address.
     */
    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return response()->json([
                'message' => 'Enlace de verificacion invalido.',
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Tu correo ya ha sido verificado.',
            ]);
        }

        $user->markEmailAsVerified();

        return response()->json([
            'message' => 'Tu correo ha sido verificado exitosamente.',
        ]);
    }
}
