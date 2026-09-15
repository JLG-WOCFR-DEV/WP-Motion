<?php

declare(strict_types=1);

define('WPMOTION_DIR', dirname(__DIR__) . '/');

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!function_exists('admin_url')) {
    function admin_url(string $path = '', $scheme = 'admin'): string
    {
        unset($scheme);

        return 'https://example.test/wp-admin/' . ltrim($path, '/');
    }
}

if (!function_exists('add_query_arg')) {
    /**
     * @param array<string, scalar>|string $args
     * @param string $url
     */
    function add_query_arg($args, $url = ''): string
    {
        if (!is_array($args)) {
            return (string) $url;
        }

        $separator = str_contains((string) $url, '?') ? '&' : '?';

        return (string) $url . $separator . http_build_query($args);
    }
}

if (!function_exists('menu_page_url')) {
    function menu_page_url(string $menu_slug, bool $display = true): string
    {
        unset($menu_slug, $display);

        return '';
    }
}

require_once WPMOTION_DIR . 'includes/autoload.php';
