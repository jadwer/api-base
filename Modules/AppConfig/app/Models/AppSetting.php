<?php

namespace Modules\AppConfig\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
    ];

    /**
     * Get a setting value by key, with type casting and caching.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("app_setting:{$key}", 60, function () use ($key) {
            return static::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value by key, invalidating cache.
     */
    public static function set(string $key, mixed $value): void
    {
        $setting = static::where('key', $key)->first();

        if ($setting) {
            $setting->update(['value' => static::serializeValue($value, $setting->type)]);
        }

        Cache::forget("app_setting:{$key}");
        Cache::forget('app_settings:grouped');
    }

    /**
     * Shortcut for getting boolean values.
     */
    public static function getBoolean(string $key, bool $default = false): bool
    {
        $value = static::get($key);

        if ($value === null) {
            return $default;
        }

        return (bool) $value;
    }

    /**
     * Get all settings for a group.
     */
    public static function getByGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->map(fn ($s) => [
                'key' => $s->key,
                'value' => static::castValue($s->value, $s->type),
                'type' => $s->type,
                'label' => $s->label,
                'description' => $s->description,
            ])
            ->keyBy('key')
            ->toArray();
    }

    /**
     * Get all settings grouped by group.
     */
    public static function getAllGrouped(): array
    {
        return Cache::remember('app_settings:grouped', 60, function () {
            return static::all()
                ->groupBy('group')
                ->map(fn ($items) => $items->map(fn ($s) => [
                    'key' => $s->key,
                    'value' => static::castValue($s->value, $s->type),
                    'type' => $s->type,
                    'label' => $s->label,
                    'description' => $s->description,
                ])->keyBy('key'))
                ->toArray();
        });
    }

    /**
     * Cast the stored string value to the appropriate type.
     */
    protected static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Serialize a value for storage.
     */
    protected static function serializeValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => $value ? 'true' : 'false',
            'json' => is_string($value) ? $value : json_encode($value),
            default => (string) $value,
        };
    }
}
