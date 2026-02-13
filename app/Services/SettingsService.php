<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    /**
     * Cache duration in seconds (1 hour)
     */
    protected const CACHE_DURATION = 3600;

    /**
     * Get a setting value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", self::CACHE_DURATION, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return $setting->value;
        });
    }

    /**
     * Set a setting value
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, $value): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        // Clear the cache for this setting
        Cache::forget("setting.{$key}");
    }

    /**
     * Clear all settings cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::flush();
    }

    /**
     * Get multiple settings by prefix
     *
     * @param string $prefix
     * @return array
     */
    public function getByPrefix(string $prefix): array
    {
        $settings = Setting::where('key', 'like', $prefix . '%')->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->value;
        }

        return $result;
    }

    /**
     * Set multiple settings at once
     *
     * @param array $settings
     * @return void
     */
    public function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value);
        }
    }
}
