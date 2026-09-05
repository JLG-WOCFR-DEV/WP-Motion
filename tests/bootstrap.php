<?php

declare(strict_types=1);

define('WPMOTION_DIR', dirname(__DIR__) . '/');

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once WPMOTION_DIR . 'includes/autoload.php';
