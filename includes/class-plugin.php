<?php

declare(strict_types=1);

final class WpGsap_Plugin
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function boot(): void
    {
        if (is_admin()) {
            (new WpGsap_Admin())->boot();
            (new WpGsap_Import_Export())->boot();
        }

        (new WpGsap_View_Transitions())->boot();
        (new WpGsap_Shared_Elements())->boot();
        (new WpGsap_Assets())->boot();
        (new WpGsap_Blocks())->boot();
        (new WpGsap_Woocommerce())->boot();
    }

    /**
     * Front is off until the user opts in, and never on excluded templates.
     *
     * @param array<string, mixed>|null $settings
     */
    public static function is_front_enabled(?array $settings = null): bool
    {
        $settings ??= WpGsap_Settings::get();
        if (empty($settings['enabled'])) {
            return false;
        }

        if (is_admin() || wp_doing_ajax() || (function_exists('wp_is_json_request') && wp_is_json_request())) {
            return false;
        }

        if (function_exists('wp_is_serving_rest_request') && wp_is_serving_rest_request()) {
            return false;
        }

        $template = WpGsap_Template_Resolver::current();
        if ($template === 'excluded') {
            return false;
        }

        if (is_preview() || is_customize_preview()) {
            return false;
        }

        $path = WpGsap_Template_Resolver::path_from_url(home_url(add_query_arg([])));
        if (isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])) {
            $path = WpGsap_Exclusions::normalize($_SERVER['REQUEST_URI']);
        }

        $user_paths = is_array($settings['exclude_paths'] ?? null) ? $settings['exclude_paths'] : [];

        return !WpGsap_Exclusions::match($path, $user_paths);
    }
}
