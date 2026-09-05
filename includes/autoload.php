<?php

declare(strict_types=1);

if (!defined('ABSPATH') && !defined('WPMOTION_DIR')) {
    exit;
}

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'WpMotion_')) {
        return;
    }

    $relative = strtolower(str_replace('_', '-', substr($class, 9)));
    $path = WPMOTION_DIR . 'includes/class-' . $relative . '.php';

    if (is_file($path)) {
        require_once $path;
    }
});
