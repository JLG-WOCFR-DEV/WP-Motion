<?php

declare(strict_types=1);

final class WpGsap_Import_Export
{
    public function boot(): void
    {
        add_action('admin_post_wp_gsap_export', [$this, 'export']);
        add_action('admin_post_wp_gsap_import', [$this, 'import']);
    }

    public function export(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Droits insuffisants.', 'wp-gsap'), '', ['response' => 403]);
        }

        check_admin_referer('wp_gsap_export');

        $payload = [
            'plugin' => 'wp-gsap',
            'version' => WPGSAP_VERSION,
            'exported_at' => gmdate('c'),
            'settings' => WpGsap_Settings::get(),
        ];

        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=wp-gsap-settings.json');
        echo wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function import(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Droits insuffisants.', 'wp-gsap'), '', ['response' => 403]);
        }

        check_admin_referer('wp_gsap_import');

        $redirect = admin_url('admin.php?page=wp-gsap&tab=tools');

        if (empty($_FILES['wp_gsap_import']['tmp_name']) || !is_uploaded_file((string) $_FILES['wp_gsap_import']['tmp_name'])) {
            wp_safe_redirect(add_query_arg('wpgsap', 'import-missing', $redirect));
            exit;
        }

        $raw = file_get_contents((string) $_FILES['wp_gsap_import']['tmp_name']);
        if (!is_string($raw) || $raw === '') {
            wp_safe_redirect(add_query_arg('wpgsap', 'import-invalid', $redirect));
            exit;
        }

        $decoded = json_decode($raw, true);
        $settings = self::extract_settings($decoded);
        if ($settings === null) {
            wp_safe_redirect(add_query_arg('wpgsap', 'import-invalid', $redirect));
            exit;
        }

        update_option(WpGsap_Settings::OPTION, WpGsap_Settings::sanitize($settings));
        wp_safe_redirect(add_query_arg('wpgsap', 'imported', $redirect));
        exit;
    }

    /**
     * @param mixed $decoded
     * @return array<string, mixed>|null
     */
    public static function extract_settings($decoded): ?array
    {
        if (!is_array($decoded)) {
            return null;
        }

        if (isset($decoded['settings']) && is_array($decoded['settings'])) {
            return $decoded['settings'];
        }

        if (isset($decoded['enabled']) || isset($decoded['preset']) || isset($decoded['routes'])) {
            return $decoded;
        }

        return null;
    }
}
