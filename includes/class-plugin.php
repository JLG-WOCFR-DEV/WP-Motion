<?php

declare(strict_types=1);

final class WpMotion_Plugin
{
    private static ?self $instance = null;

    private static ?bool $frontEnabled = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function boot(): void
    {
        if (is_admin()) {
            (new WpMotion_Admin())->boot();
            (new WpMotion_Import_Export())->boot();
        }

        (new WpMotion_View_Transitions())->boot();
        (new WpMotion_Shared_Elements())->boot();
        (new WpMotion_Assets())->boot();
        (new WpMotion_Blocks())->boot();
        (new WpMotion_Woocommerce())->boot();
    }

    /**
     * Front is off until the user opts in, and never on excluded templates.
     *
     * @param array<string, mixed>|null $settings
     */
    public static function is_front_enabled(?array $settings = null): bool
    {
        if ($settings === null && self::$frontEnabled !== null) {
            return self::$frontEnabled;
        }

        $enabled = self::compute_front_enabled($settings);
        if ($settings === null) {
            self::$frontEnabled = $enabled;
        }

        return $enabled;
    }

    /**
     * @param array<string, mixed>|null $settings
     */
    private static function compute_front_enabled(?array $settings): bool
    {
        $settings ??= WpMotion_Settings::get();
        if (empty($settings['enabled'])) {
            return false;
        }

        if (is_admin() || wp_doing_ajax() || (function_exists('wp_is_json_request') && wp_is_json_request())) {
            return false;
        }

        if (function_exists('wp_is_serving_rest_request') && wp_is_serving_rest_request()) {
            return false;
        }

        $template = WpMotion_Template_Resolver::current();
        if ($template === 'excluded') {
            return false;
        }

        if (is_preview() || is_customize_preview()) {
            return false;
        }

        $path = WpMotion_Template_Resolver::path_from_url(home_url(add_query_arg([])));
        if (isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])) {
            $path = WpMotion_Exclusions::normalize($_SERVER['REQUEST_URI']);
        }

        $user_paths = is_array($settings['exclude_paths'] ?? null) ? $settings['exclude_paths'] : [];

        return !WpMotion_Exclusions::match($path, $user_paths);
    }
}
