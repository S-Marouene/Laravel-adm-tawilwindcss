<?php

use App\Models\Setting;

if (!function_exists('setting')) {
    /**
     * Get a setting value from the database.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::getValue($key, $default);
    }
}

if (!function_exists('settings_grouped')) {
    /**
     * Get all settings grouped by their group.
     *
     * @return \Illuminate\Support\Collection
     */
    function settings_grouped(): \Illuminate\Support\Collection
    {
        return Setting::getGrouped();
    }
}
