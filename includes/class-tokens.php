<?php

declare(strict_types=1);

final class WpGsap_Tokens
{
    /**
     * @param array<string, mixed>|null $settings
     * @return array{duration_ms: int, easing: string, distance: string, reduced_motion: string}
     */
    public static function get(?array $settings = null): array
    {
        $settings ??= WpGsap_Settings::get();
        $easing_key = is_string($settings['easing'] ?? null) ? $settings['easing'] : 'snappy';

        $tokens = [
            'duration_ms' => (int) ($settings['duration_ms'] ?? 400),
            'easing' => WpGsap_Settings::easing_css($easing_key),
            'distance' => '2rem',
            'reduced_motion' => is_string($settings['reduced_motion'] ?? null) ? $settings['reduced_motion'] : 'fade',
        ];

        $filtered = function_exists('apply_filters') ? apply_filters('wpgsap_motion_tokens', $tokens) : $tokens;
        if (!is_array($filtered)) {
            return $tokens;
        }

        return [
            'duration_ms' => max(80, min(1200, (int) ($filtered['duration_ms'] ?? $tokens['duration_ms']))),
            'easing' => is_string($filtered['easing'] ?? null) && $filtered['easing'] !== '' ? $filtered['easing'] : $tokens['easing'],
            'distance' => is_string($filtered['distance'] ?? null) && $filtered['distance'] !== '' ? $filtered['distance'] : $tokens['distance'],
            'reduced_motion' => in_array($filtered['reduced_motion'] ?? '', WpGsap_Settings::REDUCED_MOTION, true)
                ? $filtered['reduced_motion']
                : $tokens['reduced_motion'],
        ];
    }

    /**
     * @param array{duration_ms: int, easing: string, distance: string, reduced_motion: string} $tokens
     */
    public static function to_css(array $tokens): string
    {
        $duration = (int) $tokens['duration_ms'];
        $easing = $tokens['easing'];
        $distance = $tokens['distance'];

        return sprintf(
            ':root{--wpgsap-duration:%dms;--wpgsap-easing:%s;--wpgsap-distance:%s;--wpgsap-reduced-duration:%dms;}',
            $duration,
            $easing,
            $distance,
            min(80, $duration)
        );
    }
}
