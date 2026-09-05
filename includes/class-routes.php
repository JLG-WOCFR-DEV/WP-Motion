<?php

declare(strict_types=1);

final class WpMotion_Routes
{
    public const TEMPLATES = [
        'home',
        'archive',
        'single',
        'page',
        'singular',
        'search',
        '404',
        'shop',
        'product',
        'cart',
        'checkout',
        'account',
        'unknown',
        '*',
    ];

    /**
     * @param mixed $routes
     * @return list<array{from: string, to: string, preset: string, shared: bool}>
     */
    public static function sanitize($routes): array
    {
        if (!is_array($routes)) {
            return WpMotion_Settings::default_routes();
        }

        $clean = [];
        foreach ($routes as $route) {
            if (!is_array($route)) {
                continue;
            }
            $from = self::template($route['from'] ?? '*');
            $to = self::template($route['to'] ?? '*');
            $preset = is_string($route['preset'] ?? null) ? $route['preset'] : 'fade';
            if (!in_array($preset, WpMotion_Settings::PRESETS, true)) {
                $preset = 'fade';
            }
            $clean[] = [
                'from' => $from,
                'to' => $to,
                'preset' => $preset,
                'shared' => WpMotion_Settings::bool($route['shared'] ?? false),
            ];
        }

        return $clean;
    }

    public static function template(string $value): string
    {
        $value = strtolower(trim($value));
        return in_array($value, self::TEMPLATES, true) ? $value : '*';
    }

    /**
     * First most-specific matching rule. Exact beats wildcard.
     *
     * @param list<array{from: string, to: string, preset: string, shared: bool}> $routes
     * @return array{from: string, to: string, preset: string, shared: bool}|null
     */
    public static function match(string $from, string $to, array $routes, string $fallback_preset = 'fade'): ?array
    {
        $from = self::template($from);
        $to = self::template($to);

        $best = null;
        $best_score = -1;

        foreach ($routes as $index => $route) {
            if (!is_array($route)) {
                continue;
            }
            $route_from = $route['from'] ?? '*';
            $route_to = $route['to'] ?? '*';
            if (!self::side_matches($from, (string) $route_from) || !self::side_matches($to, (string) $route_to)) {
                continue;
            }

            $score = 0;
            if ($route_from === $from) {
                $score += 2;
            } elseif ($route_from === '*') {
                $score += 0;
            } else {
                continue;
            }
            if ($route_to === $to) {
                $score += 2;
            } elseif ($route_to === '*') {
                $score += 0;
            } else {
                continue;
            }

            // Prefer earlier rules on a tie so admin order matters.
            $score = ($score * 1000) - $index;
            if ($score > $best_score) {
                $best_score = $score;
                $best = $route;
            }
        }

        if ($best !== null) {
            return $best;
        }

        return [
            'from' => '*',
            'to' => '*',
            'preset' => in_array($fallback_preset, WpMotion_Settings::PRESETS, true) ? $fallback_preset : 'fade',
            'shared' => false,
        ];
    }

    public static function side_matches(string $actual, string $rule): bool
    {
        if ($rule === '*' || $rule === $actual) {
            return true;
        }

        if ($rule === 'singular' && in_array($actual, ['single', 'page', 'product', 'singular'], true)) {
            return true;
        }

        if ($rule === 'archive' && in_array($actual, ['archive', 'home', 'shop', 'search'], true)) {
            return false;
        }

        return false;
    }
}
