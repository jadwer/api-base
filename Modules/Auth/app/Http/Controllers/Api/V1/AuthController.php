<?php

namespace Modules\Auth\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Iniciar sesión y devolver token de acceso
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Lista de campos válidos esperados
        $validFields = ['email', 'password'];

        // Obtener los campos que llegaron en la solicitud
        $inputFields = array_keys($request->all());

        // Verificar si hay campos adicionales no esperados
        $unexpectedFields = array_diff($inputFields, $validFields);

        // Si hay campos no esperados, devolver un error
        if (!empty($unexpectedFields)) {
            return response()->json([
                'message' => 'Campos no permitidos: ' . implode(', ', $unexpectedFields),
            ], 422);  // Si prefieres un error de bad request, puedes cambiar a 400
        }

        // Continuamos con el flujo normal si no hay campos inesperados
        $credentials = $request->validated();
        $user = User::where('email', $credentials['email'])->first();

        // Verificar si el usuario existe y si la contraseña es correcta
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

        // Verificar si el usuario está activo y no está soft-deleted
        if ($user->deleted_at !== null || $user->status !== 'active') {
            return response()->json([
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

        // Generar un token de acceso
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * Cerrar sesión del usuario autenticado
     */
    public function logout(): JsonResponse
    {
        $user = Auth::guard('sanctum')->user();

        if ($user) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    public function register(Request $request): JsonResponse
{
    $data = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'string', 'min:8'],
    ]);

    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => $data['password'],
        'status' => 'active', // o 'pending' si prefieres
    ]);

    $user->assignRole('customer');

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'access_token' => $token,
        'token_type' => 'Bearer',
        'user' => $user,
    ]);
}

}
