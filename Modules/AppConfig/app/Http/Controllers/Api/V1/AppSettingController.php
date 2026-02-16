<?php

namespace Modules\AppConfig\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
