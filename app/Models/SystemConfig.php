<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * SystemConfig Model
 * 
 * Stores global system configuration values.
 * Uses caching for optimized reads.
 */
class SystemConfig extends Model
{
    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'key';

    /**
     * The type of the primary key.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    /**
     * Cache key prefix for system configs.
     */
    private const CACHE_PREFIX = 'system_config:';

    /**
     * Cache TTL in seconds (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Get a configuration value by key.
     *
     * @param string $key The configuration key
     * @param mixed $default Default value if key not found
     * @return mixed The configuration value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = self::CACHE_PREFIX . $key;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            $config = static::find($key);
            
            if ($config === null) {
                return $default;
            }

            return $config->value;
        });
    }

    /**
     * Set a configuration value.
     *
     * @param string $key The configuration key
     * @param mixed $value The value to set
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        // Clear the cache for this key
        Cache::forget(self::CACHE_PREFIX . $key);
    }
}
