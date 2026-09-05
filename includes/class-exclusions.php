<?php

declare(strict_types=1);

final class WpGsap_Exclusions
{
    /**
     * Built-in paths that must never animate.
     *
     * @return list<string>
     */
    public static function hardcoded(): array
    {
        return [
            '/wp-admin',
            '/wp-login.php',
            '/wp-cron.php',
            '/wp-json/',
            '/xmlrpc.php',
            '/feed/',
            '/comments/feed/',
        ];
    }

    /**
     * @param list<string> $user_paths
     */
    public static function match(string $path, array $user_paths = []): bool
    {
        $path = self::normalize($path);

        if (self::is_hardcoded($path)) {
            return true;
        }

        foreach ($user_paths as $pattern) {
            if (!is_string($pattern) || $pattern === '') {
                continue;
            }
            if (self::path_matches($path, self::normalize($pattern))) {
                return true;
            }
        }

        return false;
    }

    public static function is_hardcoded(string $path): bool
    {
        $path = self::normalize($path);

        foreach (self::hardcoded() as $pattern) {
            if (self::path_matches($path, self::normalize($pattern))) {
                return true;
            }
        }

        return false;
    }

    public static function normalize(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '/';
        }

        $parsed = parse_url($path);
        if (is_array($parsed) && isset($parsed['path']) && is_string($parsed['path'])) {
            $path = $parsed['path'];
        }

        $path = '/' . ltrim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return strtolower($path);
    }

    public static function path_matches(string $path, string $pattern): bool
    {
        if ($pattern === '/' && $path === '/') {
            return true;
        }

        if ($path === $pattern) {
            return true;
        }

        $prefix = rtrim($pattern, '/');
        if ($prefix !== '' && ($path === $prefix || str_starts_with($path, $prefix . '/'))) {
            return true;
        }

        return false;
    }
}
