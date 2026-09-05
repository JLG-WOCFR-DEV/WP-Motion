<?php

declare(strict_types=1);

if (!defined('ABSPATH') && !defined('WPGSAP_DIR')) {
    exit;
}

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'WpGsap_')) {
        return;
    }

    $relative = strtolower(str_replace('_', '-', substr($class, 7)));
    $path = WPGSAP_DIR . 'includes/class-' . $relative . '.php';

    if (is_file($path)) {
        require_once $path;
    }
});
