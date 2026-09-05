<?php

declare(strict_types=1);

final class WpGsap_Assets
{
    public function boot(): void
    {
        add_action('admin_notices', [$this, 'gsap_license_notice']);
    }

    public function gsap_license_notice(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
        if ($page !== 'wp-gsap') {
            return;
        }

        $settings = WpGsap_Settings::get();
        if (($settings['gsap_source'] ?? 'cdn') !== 'cdn') {
            return;
        }

        echo '<div class="notice notice-info"><p>';
        echo esc_html__(
            'GSAP n’est pas GPL. S’il est chargé, ce sera depuis jsDelivr (CDN) uniquement sur les pages qui utilisent une scène SplitText, pin ou parallax. Licence GSAP Standard (Webflow), distincte de la GPL du plugin.',
            'wp-gsap'
        );
        echo '</p></div>';
    }
}
