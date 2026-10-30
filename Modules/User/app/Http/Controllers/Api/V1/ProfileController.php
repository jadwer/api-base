<?php

namespace Modules\User\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\User\Models\User;

/**
 * Perfil propio (2026-09-30).
 *
 * - update: antes era un closure que hacia update(name, email, status) sin
 *   validar: cualquier usuario podia cambiarse su estado o poner cualquier
 *   correo (bug H3). Ahora solo el nombre, validado. El correo y el estado
 *   se administran desde Usuarios.
 * - avatar: "preset:N" (avatares ilustrados de diseno), receta del
 *   generador "avatar:1:<piel>.<peinado>.<pelo>.<fondo>.<ropa>.<color>.<accesorio>"
 *   (mismo contrato que D.17 de AtomoPlatform; el SVG lo dibuja el frontend)
 *   o foto subida.
 */
class ProfileController extends Controller
{
    public const PRESET_COUNT = 8;

    public const RECIPE_PATTERN = '/^avatar:1:\d{1,2}(?:\.\d{1,2}){6}$/';

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->forceFill(['name' => trim($data['name'])])->save();

        return response()->json(['data' => $this->payload($user)]);
    }

    /** POST profile/avatar (multipart "avatar"), JSON {"preset": 1..8} o {"recipe": "avatar:1:..."}. */
    public function updateAvatar(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($request->hasFile('avatar')) {
            $request->validate([
                'avatar' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
            ], [
                'avatar.image' => 'El archivo debe ser una imagen.',
                'avatar.mimes' => 'La foto debe ser JPG, PNG o WEBP.',
                'avatar.max' => 'La foto no puede pesar más de 2 MB.',
                'avatar.dimensions' => 'La foto no puede medir más de 4000 px por lado.',
            ]);
            $path = $request->file('avatar')->storeAs(
                'avatars',
                $user->id . '-' . Str::random(8) . '.' . $request->file('avatar')->extension(),
                'public'
            );
            $this->deleteUploaded($user);
            $user->forceFill(['avatar' => $path])->save();
        } elseif ($request->has('recipe')) {
            $data = $request->validate([
                'recipe' => ['required', 'string', 'regex:' . self::RECIPE_PATTERN],
            ], [
                'recipe.required' => 'Arma tu avatar o sube una foto.',
                'recipe.string' => 'El avatar armado no es valido.',
                'recipe.regex' => 'El avatar armado no es valido.',
            ]);
            $this->deleteUploaded($user);
            $user->forceFill(['avatar' => $data['recipe']])->save();
        } else {
            $data = $request->validate([
                'preset' => ['required', 'integer', 'min:1', 'max:' . self::PRESET_COUNT],
            ], [
                'preset.required' => 'Elige un avatar o sube una foto.',
                'preset.min' => 'El avatar elegido no existe.',
                'preset.max' => 'El avatar elegido no existe.',
            ]);
            $this->deleteUploaded($user);
            $user->forceFill(['avatar' => 'preset:' . $data['preset']])->save();
        }

        return response()->json(['data' => $this->payload($user)]);
    }

    public function deleteAvatar(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->deleteUploaded($user);
        $user->forceFill(['avatar' => null])->save();

        return response()->json(['data' => $this->payload($user)]);
    }

    /** Valor publico del avatar: "preset:N", receta, URL absoluta de la foto o null. */
    public static function avatarValue(?string $avatar): ?string
    {
        if (! $avatar) {
            return null;
        }
        if (! self::isUploaded($avatar)) {
            return $avatar;
        }

        return Storage::disk('public')->url($avatar);
    }

    private function deleteUploaded(User $user): void
    {
        if ($user->avatar && self::isUploaded($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }
    }

    /** Los presets y las recetas no son archivos: no se borran ni se vuelven URL. */
    private static function isUploaded(string $avatar): bool
    {
        return ! str_starts_with($avatar, 'preset:') && ! str_starts_with($avatar, 'avatar:');
    }

    private function payload(User $user): array
    {
        return [
            'type' => 'users',
            'id' => (string) $user->id,
            'attributes' => [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => self::avatarValue($user->avatar),
            ],
        ];
    }
}
