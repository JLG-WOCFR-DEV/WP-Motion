<?php

declare(strict_types=1);

final class WpMotion_Assets
{
    public const MOTION_VERSION = '13.2.0';

    public function boot(): void
    {
        add_action('admin_notices', [$this, 'license_notice']);
    }

    public function license_notice(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
        $tab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'general';
        if ($page !== 'wp-motion' || $tab !== 'tools') {
            return;
        }

        echo '<div class="notice notice-info"><p>';
        echo esc_html__(
            'Motion 13.2.0 est bundlé (MIT) dans ce plugin. Le code WP Motion est GPL-2.0-or-later. Aucun code GSAP n’est chargé.',
            'wp-motion'
        );
        echo '</p></div>';
    }
}
