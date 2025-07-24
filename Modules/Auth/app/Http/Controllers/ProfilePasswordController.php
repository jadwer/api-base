<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfilePasswordController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed'],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '422',
                        'title' => 'Invalid current password',
                        'detail' => 'La contraseña actual no es correcta.',
                        'source' => ['pointer' => '/data/attributes/current_password']
                    ]
                ]
            ], 422);
        }

        $user->update([
            'password' => $validated['password'],
        ]);

        return response()->noContent(); // 204
    }
}
