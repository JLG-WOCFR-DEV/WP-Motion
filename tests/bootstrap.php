<?php

declare(strict_types=1);

define('WPGSAP_DIR', dirname(__DIR__) . '/');

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once WPGSAP_DIR . 'includes/autoload.php';
