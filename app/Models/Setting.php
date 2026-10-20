<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public const CACHE_KEY = 'wa.settings';

    /**
     * Return all settings as an associative array (cached).
     *
     * @return array<string, mixed>
     */
    public static function allCached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return static::query()
                ->pluck('value', 'key')
                ->map(fn ($value) => self::decode($value))
                ->all();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::allCached();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => self::encode($value)]);
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Keep the numeric/integer settings usable even when stored as strings.
     */
    public static function int(string $key, int $default = 0): int
    {
        $value = static::get($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @return array<int, string>
     */
    public static function phones(string $key): array
    {
        $value = static::get($key, []);

        if (is_string($value)) {
            $value = preg_split('/[\s,;]+/', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($phone) => preg_replace('/\D+/', '', (string) $phone))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function encode(mixed $value): string
    {
        return is_string($value) ? $value : json_encode($value);
    }

    private static function decode(?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
