<?php

declare(strict_types=1);

final class WpGsap_Settings
{
    public const OPTION = 'wp_gsap_settings';

    public const PRESETS = ['fade', 'slide', 'wipe', 'none'];

    public const EASINGS = [
        'ease' => 'ease',
        'ease-out' => 'ease-out',
        'ease-in-out' => 'ease-in-out',
        'linear' => 'linear',
        'snappy' => 'cubic-bezier(0.22, 1, 0.36, 1)',
        'cinematic' => 'cubic-bezier(0.77, 0, 0.175, 1)',
    ];

    public const REDUCED_MOTION = ['none', 'fade'];

    public const GSAP_SOURCES = ['cdn', 'none'];

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'enabled' => false,
            'preset' => 'fade',
            'duration_ms' => 400,
            'easing' => 'snappy',
            'reduced_motion' => 'fade',
            'exclude_paths' => [
                '/cart/',
                '/checkout/',
                '/my-account/',
                '/panier/',
                '/commande/',
                '/mon-compte/',
            ],
            'header_persistent' => true,
            'header_selector' => '#masthead, header.site-header, header.wp-block-template-part',
            'shared_featured_image' => true,
            'shared_title' => true,
            'gsap_source' => 'cdn',
            'routes' => self::default_routes(),
        ];
    }

    /**
     * @return list<array{from: string, to: string, preset: string, shared: bool}>
     */
    public static function default_routes(): array
    {
        return [
            ['from' => 'archive', 'to' => 'single', 'preset' => 'fade', 'shared' => true],
            ['from' => 'home', 'to' => 'single', 'preset' => 'fade', 'shared' => true],
            ['from' => 'single', 'to' => 'single', 'preset' => 'slide', 'shared' => false],
            ['from' => 'page', 'to' => 'page', 'preset' => 'fade', 'shared' => false],
            ['from' => 'shop', 'to' => 'product', 'preset' => 'fade', 'shared' => true],
            ['from' => '*', 'to' => 'checkout', 'preset' => 'none', 'shared' => false],
            ['from' => '*', 'to' => 'cart', 'preset' => 'none', 'shared' => false],
            ['from' => '*', 'to' => 'account', 'preset' => 'none', 'shared' => false],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        $stored = get_option(self::OPTION, []);
        if (!is_array($stored)) {
            $stored = [];
        }

        return self::sanitize(array_merge(self::defaults(), $stored));
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function sanitize(array $input): array
    {
        $defaults = self::defaults();
        $existing = [];
        if (function_exists('get_option')) {
            $stored = get_option(self::OPTION, []);
            if (is_array($stored)) {
                $existing = $stored;
            }
        }
        foreach (['routes', 'exclude_paths', 'header_selector', 'preset', 'duration_ms', 'easing', 'reduced_motion', 'gsap_source'] as $key) {
            if (!array_key_exists($key, $input) && array_key_exists($key, $existing)) {
                $input[$key] = $existing[$key];
            }
        }
        foreach (['enabled', 'header_persistent', 'shared_featured_image', 'shared_title'] as $key) {
            if (!array_key_exists($key, $input) && array_key_exists($key, $existing)) {
                $input[$key] = $existing[$key];
            }
        }


        $easing = is_string($input['easing'] ?? null) ? $input['easing'] : $defaults['easing'];
        if (!isset(self::EASINGS[$easing])) {
            $easing = $defaults['easing'];
        }

        $preset = is_string($input['preset'] ?? null) ? $input['preset'] : $defaults['preset'];
        if (!in_array($preset, self::PRESETS, true)) {
            $preset = $defaults['preset'];
        }

        $reduced = is_string($input['reduced_motion'] ?? null) ? $input['reduced_motion'] : $defaults['reduced_motion'];
        if (!in_array($reduced, self::REDUCED_MOTION, true)) {
            $reduced = $defaults['reduced_motion'];
        }

        $gsap = is_string($input['gsap_source'] ?? null) ? $input['gsap_source'] : $defaults['gsap_source'];
        if (!in_array($gsap, self::GSAP_SOURCES, true)) {
            $gsap = $defaults['gsap_source'];
        }

        $duration = isset($input['duration_ms']) ? (int) $input['duration_ms'] : $defaults['duration_ms'];
        $duration = max(80, min(1200, $duration));

        $header_selector = is_string($input['header_selector'] ?? null)
            ? $input['header_selector']
            : $defaults['header_selector'];
        $header_selector = trim(preg_replace('/[^\w\s.#,\-\[\]="\':]/u', '', $header_selector) ?? $header_selector);

        return [
            'enabled' => self::bool($input['enabled'] ?? false),
            'preset' => $preset,
            'duration_ms' => $duration,
            'easing' => $easing,
            'reduced_motion' => $reduced,
            'exclude_paths' => self::sanitize_paths($input['exclude_paths'] ?? $defaults['exclude_paths']),
            'header_persistent' => self::bool($input['header_persistent'] ?? true),
            'header_selector' => $header_selector !== '' ? $header_selector : $defaults['header_selector'],
            'shared_featured_image' => self::bool($input['shared_featured_image'] ?? true),
            'shared_title' => self::bool($input['shared_title'] ?? true),
            'gsap_source' => $gsap,
            'routes' => WpGsap_Routes::sanitize($input['routes'] ?? $defaults['routes']),
        ];
    }

    public static function easing_css(string $key): string
    {
        return self::EASINGS[$key] ?? self::EASINGS['snappy'];
    }

    /**
     * @param mixed $paths
     * @return list<string>
     */
    public static function sanitize_paths($paths): array
    {
        if (is_string($paths)) {
            $paths = preg_split('/\r\n|\r|\n/', $paths) ?: [];
        }

        if (!is_array($paths)) {
            return [];
        }

        $clean = [];
        foreach ($paths as $path) {
            if (!is_string($path)) {
                continue;
            }
            $path = trim($path);
            if ($path === '' || str_starts_with($path, '#')) {
                continue;
            }
            $path = '/' . ltrim($path, '/');
            $clean[] = $path;
        }

        return array_values(array_unique($clean));
    }

    /**
     * @param mixed $value
     */
    public static function bool($value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'on' || $value === 'true';
    }
}
