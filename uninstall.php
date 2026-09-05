<?php

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('wp_motion_settings');
delete_option('wp_gsap_settings');

if (is_multisite()) {
    $site_ids = get_sites(['fields' => 'ids', 'number' => 0]);
    foreach ($site_ids as $site_id) {
        delete_blog_option((int) $site_id, 'wp_motion_settings');
    }
}
