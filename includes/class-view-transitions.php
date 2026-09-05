<?php

declare(strict_types=1);

final class WpMotion_View_Transitions
{
    public function boot(): void
    {
        add_filter('language_attributes', [$this, 'html_attributes']);
        add_action('wp_head', [$this, 'print_opt_in'], 1);
        add_action('wp_enqueue_scripts', [$this, 'enqueue'], 20);
    }

    public function html_attributes(string $output): string
    {
        if (!WpMotion_Plugin::is_front_enabled()) {
            return $output;
        }

        $template = WpMotion_Template_Resolver::current();
        $id = (int) get_queried_object_id();

        $output .= ' data-wpmotion-template="' . esc_attr($template) . '"';
        if ($id > 0) {
            $output .= ' data-wpmotion-id="' . esc_attr((string) $id) . '"';
        }
        if (WpMotion_Plugin::is_debug()) {
            $output .= ' data-wpmotion-debug="1"';
        }

        return $output;
    }

    public function print_opt_in(): void
    {
        if (!WpMotion_Plugin::is_front_enabled()) {
            return;
        }

        $settings = WpMotion_Settings::get();
        $tokens = WpMotion_Tokens::get($settings);
        $template = WpMotion_Template_Resolver::current();

        echo '<style id="wpmotion-tokens">' . WpMotion_Tokens::to_css($tokens) . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        if (($settings['preset'] ?? 'fade') === 'none' && $this->all_routes_none($settings)) {
            return;
        }

        if (in_array($template, ['checkout', 'cart', 'account'], true)) {
            return;
        }

        echo '<style id="wpmotion-vt-optin">@view-transition{navigation:auto;}@media (prefers-reduced-motion:reduce){:root{--wpmotion-duration:var(--wpmotion-reduced-duration);}}</style>' . "\n";
    }

    public function enqueue(): void
    {
        if (!WpMotion_Plugin::is_front_enabled()) {
            return;
        }

        $settings = WpMotion_Settings::get();
        $tokens = WpMotion_Tokens::get($settings);

        wp_enqueue_style(
            'wpmotion-front-transitions',
            WPMOTION_URL . 'assets/css/front-transitions.css',
            [],
            WPMOTION_VERSION
        );
        wp_enqueue_style(
            'wpmotion-front-scenes',
            WPMOTION_URL . 'assets/css/front-scenes.css',
            ['wpmotion-front-transitions'],
            WPMOTION_VERSION
        );

        wp_enqueue_script(
            'wpmotion-front',
            WPMOTION_URL . 'assets/js/front.js',
            [],
            WPMOTION_VERSION,
            true
        );

        wp_localize_script('wpmotion-front', 'WPMOTION', $this->js_config($settings, $tokens));
    }

    /**
     * @param array<string, mixed> $settings
     * @param array{duration_ms: int, easing: string, distance: string, reduced_motion: string} $tokens
     * @return array<string, mixed>
     */
    private function js_config(array $settings, array $tokens): array
    {
        return [
            'enabled' => true,
            'preset' => $settings['preset'],
            'durationMs' => $tokens['duration_ms'],
            'easing' => $tokens['easing'],
            'reducedMotion' => $tokens['reduced_motion'],
            'excludePaths' => $settings['exclude_paths'],
            'routes' => $settings['routes'],
            'current' => [
                'template' => WpMotion_Template_Resolver::current(),
                'id' => (int) get_queried_object_id(),
            ],
            'known' => WpMotion_Template_Resolver::known_from_wordpress(),
            'headerPersistent' => !empty($settings['header_persistent']),
            'headerSelector' => (string) ($settings['header_selector'] ?? ''),
            'motionSrc' => WPMOTION_URL . 'assets/vendor/motion.min.js?ver=' . rawurlencode(WpMotion_Assets::MOTION_VERSION),
            'i18n' => [
                'pageReady' => sprintf(
                    /* translators: %s: document title */
                    __('Page chargée : %s', 'wp-motion'),
                    wp_get_document_title()
                ),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function all_routes_none(array $settings): bool
    {
        if (($settings['preset'] ?? '') !== 'none') {
            return false;
        }
        foreach ($settings['routes'] ?? [] as $route) {
            if (($route['preset'] ?? 'none') !== 'none') {
                return false;
            }
        }

        return true;
    }
}
