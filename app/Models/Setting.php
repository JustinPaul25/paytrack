<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Check if settings table exists
     */
    protected static function tableExists(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        if (!self::tableExists()) {
            return $default;
        }

        try {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        } catch (\Exception $e) {
            // Table doesn't exist or other error, return default
            return $default;
        }
    }

    /**
     * Set a setting value by key
     */
    public static function set(string $key, $value): void
    {
        if (!self::tableExists()) {
            \Log::warning('Settings table does not exist. Run migrations first.', [
                'key' => $key
            ]);
            return;
        }

        try {
            self::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        } catch (\Exception $e) {
            \Log::warning('Failed to set setting', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
        }
    }
}
