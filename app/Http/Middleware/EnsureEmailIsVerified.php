<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\AppConfig\Models\AppSetting;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Conditionally require email verification based on app settings.
     * If auth.require_email_verification is false (default), this is a no-op.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! AppSetting::getBoolean('auth.require_email_verification', false)) {
            return $next($request);
        }

        $user = $request->user('sanctum');

        if ($user && ! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Debes verificar tu correo electronico antes de continuar.',
                'error' => 'email_not_verified',
            ], 403);
        }

        return $next($request);
    }
}
