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

    /**
     * Get the full absolute URL for the clinic logo with smart fallbacks.
     * Prevents 404s caused by ephemeral container restarts or missing disk files.
     *
     * @return string|null
     */
    public static function getLogoUrl(): ?string
    {
        $logo = static::get('clinic_logo') ?: static::get('logo');

        if (empty($logo)) {
            return null;
        }

        // 1. Data URL (base64)
        if (str_starts_with($logo, 'data:image/')) {
            return $logo;
        }

        // 2. Absolute HTTP/HTTPS URL
        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
            return static::normalizeProtocol($logo);
        }

        // 3. Static image path in public/ (e.g. images/clinic-logo.png)
        if (str_starts_with(ltrim($logo, '/'), 'images/')) {
            $clean = ltrim($logo, '/');
            return static::normalizeProtocol(asset($clean));
        }

        // 4. Stored file in storage/ (e.g. clinic/logo_....png)
        $clean = ltrim($logo, '/');
        // If the file exists on the public storage disk or public/storage
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($clean) || file_exists(public_path('storage/' . $clean))) {
            return static::normalizeProtocol(asset('storage/' . $clean));
        }

        // If ephemeral storage was wiped but we have base64 in clinic_logo_data, restore it
        $backupData = static::get('clinic_logo_data');
        if (!empty($backupData) && str_starts_with($backupData, 'data:image/')) {
            try {
                $base64 = substr($backupData, strpos($backupData, ',') + 1);
                $decoded = base64_decode($base64);
                if ($decoded !== false) {
                    \Illuminate\Support\Facades\Storage::disk('public')->put($clean, $decoded);
                    return static::normalizeProtocol(asset('storage/' . $clean));
                }
            } catch (\Throwable $e) {
                return $backupData;
            }
        }

        // 5. Ephemeral file missing: fallback to permanent static logo
        if (file_exists(public_path('images/clinic-logo.png'))) {
            return static::normalizeProtocol(asset('images/clinic-logo.png'));
        }

        return null;
    }

    /**
     * Ensure HTTPS in production / reverse-proxy environments.
     */
    public static function normalizeProtocol(string $url): string
    {
        if (request()?->header('x-forwarded-proto') === 'https' 
            || request()?->isSecure() 
            || app()->environment('production')
            || str_contains($url, 'onrender.com')
            || str_contains($url, 'ngrok')) {
            return preg_replace('/^http:\/\//i', 'https://', $url);
        }
        return $url;
    }
}

