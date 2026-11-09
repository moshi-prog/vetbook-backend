<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicSetting extends Model
{
    protected $table = 'clinic_settings';

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a setting value by key, with an optional fallback default.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();

        if ($setting !== null && $setting->value !== null && $setting->value !== '') {
            return $setting->value;
        }

        return $default;
    }

    /**
     * Set / persist a setting value by key.
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public static function set(string $key, $value)
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value !== null ? (string)$value : null]
        );
    }
}

