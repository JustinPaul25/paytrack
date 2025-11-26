<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        try {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        } catch (\Exception $e) {
            // Table doesn't exist yet, return default
            return $default;
        }
    }

    /**
     * Set a setting value by key
     */
    public static function set(string $key, $value): void
    {
        try {
            self::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        } catch (\Exception $e) {
            // Table doesn't exist yet, ignore
            \Log::warning('Settings table does not exist. Run migrations first.', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
        }
    }
}
