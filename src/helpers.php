<?php

if (!function_exists('mass_mailer_get_file_icon')) {
    /**
     * Get the appropriate icon class for a file type
     *
     * @param string $mimeType
     * @param string $framework
     * @return string
     */
    function mass_mailer_get_file_icon($mimeType, $framework = 'bootstrap')
    {
        $config = config('mass-mailer-ui', []);

        if (str_contains($mimeType, 'image/')) {
            return $config['icons']['file_types']['image'][$framework] ?? 'fas fa-image';
        } elseif ($mimeType === 'application/pdf') {
            return $config['icons']['file_types']['pdf'][$framework] ?? 'fas fa-file-pdf';
        } elseif (str_contains($mimeType, 'word')) {
            return $config['icons']['file_types']['word'][$framework] ?? 'fas fa-file-word';
        } else {
            return $config['icons']['file_types']['default'][$framework] ?? 'fas fa-file';
        }
    }
}

if (!function_exists('mass_mailer_format_file_size')) {
    /**
     * Format file size in human readable format
     *
     * @param int $bytes
     * @return string
     */
    function mass_mailer_format_file_size($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 1) . ' ' . $units[$pow];
    }
}

if (!function_exists('mass_mailer_get_button_classes')) {
    /**
     * Get button classes based on framework and type
     *
     * @param string $type
     * @param string $framework
     * @return string
     */
    function mass_mailer_get_button_classes($type, $framework = 'bootstrap')
    {
        $config = config('mass-mailer-ui', []);

        return $config['buttons'][$type][$framework] ?? '';
    }
}

if (!function_exists('mass_mailer_get_alert_classes')) {
    /**
     * Get alert classes based on framework and type
     *
     * @param string $type
     * @param string $framework
     * @return string
     */
    function mass_mailer_get_alert_classes($type, $framework = 'bootstrap')
    {
        $config = config('mass-mailer-ui', []);

        return $config['alerts'][$type][$framework] ?? '';
    }
}

if (!function_exists('mass_mailer_get_sweetalert_config')) {
    /**
     * Get SweetAlert configuration for a specific type
     *
     * @param string $type
     * @return array
     */
    function mass_mailer_get_sweetalert_config($type)
    {
        $config = config('mass-mailer-ui.libraries.sweetalert.config', []);

        return $config[$type] ?? [];
    }
}

if (!function_exists('mass_mailer_get_quill_config')) {
    /**
     * Get Quill editor configuration
     *
     * @return array
     */
    function mass_mailer_get_quill_config()
    {
        return config('mass-mailer-ui.libraries.quill.config', []);
    }
}

if (!function_exists('mass_mailer_get_color_classes')) {
    /**
     * Get color classes based on framework and type
     *
     * @param string $type
     * @param string $framework
     * @return string
     */
    function mass_mailer_get_color_classes($type, $framework = 'bootstrap')
    {
        $config = config('mass-mailer-ui', []);

        return $config['colors'][$type][$framework] ?? '';
    }
}

if (!function_exists('mass_mailer_get_form_classes')) {
    /**
     * Get form element classes based on framework and type
     *
     * @param string $type
     * @param string $framework
     * @return string
     */
    function mass_mailer_get_form_classes($type, $framework = 'bootstrap')
    {
        $config = config('mass-mailer-ui', []);

        return $config['forms'][$type][$framework] ?? '';
    }
}

if (!function_exists('mass_mailer_get_table_classes')) {
    /**
     * Get table classes based on framework and type
     *
     * @param string $type
     * @param string $framework
     * @return string
     */
    function mass_mailer_get_table_classes($type, $framework = 'bootstrap')
    {
        $config = config('mass-mailer-ui', []);

        return $config['tables'][$type][$framework] ?? '';
    }
}

if (!function_exists('mass_mailer_get_modal_classes')) {
    /**
     * Get modal classes based on framework and type
     *
     * @param string $type
     * @param string $framework
     * @return string
     */
    function mass_mailer_get_modal_classes($type, $framework = 'bootstrap')
    {
        $config = config('mass-mailer-ui', []);

        return $config['modals'][$type][$framework] ?? '';
    }
}

if (!function_exists('mass_mailer_get_button_size_classes')) {
    /**
     * Get button size classes based on framework and size
     *
     * @param string $size
     * @param string $framework
     * @return string
     */
    function mass_mailer_get_button_size_classes($size, $framework = 'bootstrap')
    {
        $config = config('mass-mailer-ui', []);

        return $config['buttons']['sizes'][$size][$framework] ?? '';
    }
}

if (!function_exists('mass_mailer_get_button_loading_classes')) {
    /**
     * Get button loading classes based on framework
     *
     * @param string $framework
     * @return string
     */
    function mass_mailer_get_button_loading_classes($framework = 'bootstrap')
    {
        $config = config('mass-mailer-ui', []);

        return $config['buttons']['loading'][$framework] ?? '';
    }
}
