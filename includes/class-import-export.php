<?php

declare(strict_types=1);

final class WpMotion_Import_Export
{
    public function boot(): void
    {
        add_action('admin_post_wp_motion_export', [$this, 'export']);
        add_action('admin_post_wp_motion_import', [$this, 'import']);
    }

    public function export(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Droits insuffisants.', 'wp-motion'), '', ['response' => 403]);
        }

        check_admin_referer('wp_motion_export');

        $payload = [
            'plugin' => 'wp-motion',
            'version' => WPMOTION_VERSION,
            'exported_at' => gmdate('c'),
            'settings' => WpMotion_Settings::get(),
        ];

        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=wp-motion-settings.json');
        echo wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function import(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Droits insuffisants.', 'wp-motion'), '', ['response' => 403]);
        }

        check_admin_referer('wp_motion_import');

        $redirect = admin_url('admin.php?page=wp-motion&tab=tools');

        if (empty($_FILES['wp_motion_import']['tmp_name']) || !is_uploaded_file((string) $_FILES['wp_motion_import']['tmp_name'])) {
            wp_safe_redirect(add_query_arg('wpmotion', 'import-missing', $redirect));
            exit;
        }

        $raw = file_get_contents((string) $_FILES['wp_motion_import']['tmp_name']);
        if (!is_string($raw) || $raw === '') {
            wp_safe_redirect(add_query_arg('wpmotion', 'import-invalid', $redirect));
            exit;
        }

        $decoded = json_decode($raw, true);
        $settings = self::extract_settings($decoded);
        if ($settings === null) {
            wp_safe_redirect(add_query_arg('wpmotion', 'import-invalid', $redirect));
            exit;
        }

        update_option(WpMotion_Settings::OPTION, WpMotion_Settings::sanitize($settings));
        wp_safe_redirect(add_query_arg('wpmotion', 'imported', $redirect));
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
