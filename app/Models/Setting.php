<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @property string $key
 * @property string|null $value
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Read a setting value (cached), falling back to $default when unset.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = Cache::rememberForever(
            self::cacheKey($key),
            fn (): ?string => self::query()->where('key', $key)->value('value'),
        );

        return $value ?? $default;
    }

    /**
     * Write a setting value and refresh the cache.
     */
    public static function set(string $key, ?string $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget(self::cacheKey($key));
    }

    private static function cacheKey(string $key): string
    {
        return 'setting:'.$key;
    }
}
