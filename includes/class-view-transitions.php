<?php

declare(strict_types=1);

final class WpGsap_View_Transitions
{
    public function boot(): void
    {
        add_filter('language_attributes', [$this, 'html_attributes']);
        add_action('wp_head', [$this, 'print_opt_in'], 1);
        add_action('wp_enqueue_scripts', [$this, 'enqueue'], 20);
    }

    public function html_attributes(string $output): string
    {
        if (!WpGsap_Plugin::is_front_enabled()) {
            return $output;
        }

        $template = WpGsap_Template_Resolver::current();
        $id = (int) get_queried_object_id();

        $output .= ' data-wpgsap-template="' . esc_attr($template) . '"';
        if ($id > 0) {
            $output .= ' data-wpgsap-id="' . esc_attr((string) $id) . '"';
        }

        return $output;
    }

    public function print_opt_in(): void
    {
        if (!WpGsap_Plugin::is_front_enabled()) {
            return;
        }

        $settings = WpGsap_Settings::get();
        $tokens = WpGsap_Tokens::get($settings);
        $template = WpGsap_Template_Resolver::current();

        echo '<style id="wpgsap-tokens">' . WpGsap_Tokens::to_css($tokens) . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        if (($settings['preset'] ?? 'fade') === 'none' && $this->all_routes_none($settings)) {
            return;
        }

        if (in_array($template, ['checkout', 'cart', 'account'], true)) {
            return;
        }

        echo '<style id="wpgsap-vt-optin">@view-transition{navigation:auto;}@media (prefers-reduced-motion:reduce){:root{--wpgsap-duration:var(--wpgsap-reduced-duration);}}</style>' . "\n";
    }

    public function enqueue(): void
    {
        if (!WpGsap_Plugin::is_front_enabled()) {
            return;
        }

        $settings = WpGsap_Settings::get();
        $tokens = WpGsap_Tokens::get($settings);

        wp_enqueue_style(
            'wpgsap-front-transitions',
            WPGSAP_URL . 'assets/css/front-transitions.css',
            [],
            WPGSAP_VERSION
        );
        wp_enqueue_style(
            'wpgsap-front-scenes',
            WPGSAP_URL . 'assets/css/front-scenes.css',
            ['wpgsap-front-transitions'],
            WPGSAP_VERSION
        );

        wp_enqueue_script(
            'wpgsap-front',
            WPGSAP_URL . 'assets/js/front.js',
            [],
            WPGSAP_VERSION,
            true
        );

        wp_localize_script('wpgsap-front', 'WPGSAP', $this->js_config($settings, $tokens));
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
            'gsapSource' => $settings['gsap_source'],
            'gsap' => [
                'core' => 'https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js',
                'scrollTrigger' => 'https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/ScrollTrigger.min.js',
                'splitText' => 'https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/SplitText.min.js',
            ],
            'current' => [
                'template' => WpGsap_Template_Resolver::current(),
                'id' => (int) get_queried_object_id(),
            ],
            'known' => WpGsap_Template_Resolver::known_from_wordpress(),
            'i18n' => [
                'pageReady' => sprintf(
                    /* translators: %s: document title */
                    __('Page chargée : %s', 'wp-gsap'),
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
