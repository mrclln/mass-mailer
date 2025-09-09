<?php

if (!function_exists('mass_mailer_config')) {
    /**
     * Get mass mailer configuration value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function mass_mailer_config(string $key, $default = null)
    {
        return config('mass-mailer.' . $key, $default);
    }
}

if (!function_exists('mass_mailer_enabled')) {
    /**
     * Check if mass mailer is enabled
     *
     * @return bool
     */
    function mass_mailer_enabled(): bool
    {
        return mass_mailer_config('enabled', true);
    }
}

if (!function_exists('mass_mailer_ui_framework')) {
    /**
     * Get the current UI framework
     *
     * @return string
     */
    function mass_mailer_ui_framework(): string
    {
        return mass_mailer_config('ui.framework', 'bootstrap');
    }
}
