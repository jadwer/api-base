<?php

namespace Modules\AppConfig\Services;

use Modules\AppConfig\Models\AppSetting;

/**
 * AppSettingResolver
 *
 * Typed wrapper over AppSetting::get() so that Mailables, Notifications,
 * TemplateRenderService, and other runtime consumers can resolve
 * branding/company values from the database without hardcoded fallbacks
 * to a specific tenant.
 *
 * The underlying AppSetting::get() already handles caching (60s TTL) and
 * type casting (boolean/integer/json/string). This class adds:
 *   - Dependency-injectable shape (testable via mock binding).
 *   - Convenience type helpers (getString, getBool, getInt, getArray).
 *   - A single named API surface that can later evolve (e.g. to support
 *     per-tenant scopes) without touching every Mailable.
 *
 * Usage in a Mailable / Notification:
 *
 *     $companyName = app(AppSettingResolver::class)
 *         ->get('company.name', config('app.name', 'Demo Company'));
 *
 * Two-tier fallback intentional: (1) DB AppSetting, (2) Laravel config,
 * (3) generic placeholder. No tenant-specific string ever appears in code.
 */
class AppSettingResolver
{
    public function get(string $key, mixed $default = null): mixed
    {
        return AppSetting::get($key, $default);
    }

    public function getString(string $key, string $default = ''): string
    {
        $value = AppSetting::get($key);
        return $value === null ? $default : (string) $value;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        return AppSetting::getBoolean($key, $default);
    }

    public function getInt(string $key, int $default = 0): int
    {
        $value = AppSetting::get($key);
        return $value === null ? $default : (int) $value;
    }

    public function getArray(string $key, array $default = []): array
    {
        $value = AppSetting::get($key);
        return is_array($value) ? $value : $default;
    }
}
