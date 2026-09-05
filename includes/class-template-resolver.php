<?php

declare(strict_types=1);

final class WpGsap_Template_Resolver
{
    /**
     * Resolve a request path against known WordPress/WooCommerce URLs.
     *
     * @param array<string, string> $known Map of template => path (e.g. shop => /shop)
     */
    public static function from_path(string $path, array $known): string
    {
        $path = WpGsap_Exclusions::normalize($path);

        if (WpGsap_Exclusions::is_hardcoded($path)) {
            return 'excluded';
        }

        if (isset($known['query']) && $known['query'] !== '') {
            unset($known['query']);
        }

        $matches = [];
        foreach ($known as $template => $known_path) {
            if (!is_string($template) || !is_string($known_path) || $known_path === '') {
                continue;
            }
            $known_path = WpGsap_Exclusions::normalize($known_path);
            if ($known_path === '/') {
                if ($path === '/') {
                    $matches[$template] = 1;
                }
                continue;
            }
            if ($path === $known_path || str_starts_with($path, $known_path . '/')) {
                $matches[$template] = strlen($known_path);
            }
        }

        if ($matches !== []) {
            arsort($matches);
            return (string) array_key_first($matches);
        }

        if ($path === '/') {
            return 'home';
        }

        return 'unknown';
    }

    /**
     * @return array<string, string>
     */
    public static function known_from_wordpress(): array
    {
        $known = [
            'home' => self::path_from_url(home_url('/')),
        ];

        $blog_id = (int) get_option('page_for_posts');
        if ($blog_id > 0) {
            $known['archive'] = self::path_from_url((string) get_permalink($blog_id));
        }

        if (function_exists('wc_get_page_permalink')) {
            foreach (['shop' => 'shop', 'cart' => 'cart', 'checkout' => 'checkout', 'account' => 'myaccount'] as $template => $wc_page) {
                $url = wc_get_page_permalink($wc_page);
                if (is_string($url) && $url !== '') {
                    $known[$template] = self::path_from_url($url);
                }
            }
        }

        return $known;
    }

    public static function current(): string
    {
        if (is_admin() || (function_exists('is_login') && is_login())) {
            return 'excluded';
        }
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return 'excluded';
        }
        if (function_exists('wp_doing_cron') && wp_doing_cron()) {
            return 'excluded';
        }
        if (is_feed() || is_trackback() || is_robots() || is_favicon()) {
            return 'excluded';
        }
        if (function_exists('is_checkout') && is_checkout()) {
            return 'checkout';
        }
        if (function_exists('is_cart') && is_cart()) {
            return 'cart';
        }
        if (function_exists('is_account_page') && is_account_page()) {
            return 'account';
        }
        if (function_exists('is_product') && is_product()) {
            return 'product';
        }
        if (function_exists('is_shop') && is_shop()) {
            return 'shop';
        }
        if (is_front_page()) {
            return 'home';
        }
        if (is_search()) {
            return 'search';
        }
        if (is_404()) {
            return '404';
        }
        if (is_page()) {
            return 'page';
        }
        if (is_single()) {
            return 'single';
        }
        if (is_singular()) {
            return 'singular';
        }
        if (is_home() || is_archive() || is_post_type_archive()) {
            return 'archive';
        }

        return 'unknown';
    }

    public static function path_from_url(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        return WpGsap_Exclusions::normalize(is_string($path) ? $path : '/');
    }
}
