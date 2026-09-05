<?php

declare(strict_types=1);

final class WpMotion_Html
{
    public static function add_style(string $html, string $property, string $value, ?string $tag = null): string
    {
        if ($html === '' || $property === '') {
            return $html;
        }

        if (class_exists('WP_HTML_Tag_Processor')) {
            $processor = new WP_HTML_Tag_Processor($html);
            $needle = $tag !== null ? strtoupper($tag) : null;
            while ($processor->next_tag()) {
                if ($needle !== null && $processor->get_tag() !== $needle) {
                    continue;
                }
                $style = (string) $processor->get_attribute('style');
                $processor->set_attribute('style', self::merge_style($style, $property, $value));
                return $processor->get_updated_html();
            }

            return $html;
        }

        return self::patch_tag($html, $tag, static function (string $open) use ($property, $value): string {
            $decl = $property . ':' . $value;
            if (preg_match('/\sstyle=([\'"])(.*?)\1/i', $open, $sm)) {
                $merged = self::merge_style($sm[2], $property, $value);
                return preg_replace(
                    '/\sstyle=([\'"])(.*?)\1/i',
                    ' style="' . htmlspecialchars($merged, ENT_QUOTES, 'UTF-8') . '"',
                    $open,
                    1
                ) ?? $open;
            }

            return preg_replace(
                '/(\/?>)$/',
                ' style="' . htmlspecialchars($decl . ';', ENT_QUOTES, 'UTF-8') . '"$1',
                $open,
                1
            ) ?? $open;
        });
    }

    public static function add_attribute(string $html, string $name, string $value, ?string $tag = null): string
    {
        if ($html === '' || $name === '') {
            return $html;
        }

        if (class_exists('WP_HTML_Tag_Processor')) {
            $processor = new WP_HTML_Tag_Processor($html);
            $needle = $tag !== null ? strtoupper($tag) : null;
            while ($processor->next_tag()) {
                if ($needle !== null && $processor->get_tag() !== $needle) {
                    continue;
                }
                $processor->set_attribute($name, $value);
                return $processor->get_updated_html();
            }

            return $html;
        }

        $safe_name = preg_replace('/[^a-zA-Z0-9:-]/', '', $name) ?? $name;
        $safe_value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        return self::patch_tag($html, $tag, static function (string $open) use ($safe_name, $safe_value): string {
            if (preg_match('/\s' . preg_quote($safe_name, '/') . '=/i', $open)) {
                return preg_replace(
                    '/\s' . preg_quote($safe_name, '/') . '=([\'"])(.*?)\1/i',
                    ' ' . $safe_name . '="' . $safe_value . '"',
                    $open,
                    1
                ) ?? $open;
            }

            return preg_replace('/(\/?>)$/', ' ' . $safe_name . '="' . $safe_value . '"$1', $open, 1) ?? $open;
        });
    }

    public static function add_class(string $html, string $class): string
    {
        if ($html === '' || $class === '') {
            return $html;
        }

        if (class_exists('WP_HTML_Tag_Processor')) {
            $processor = new WP_HTML_Tag_Processor($html);
            if ($processor->next_tag()) {
                $processor->add_class($class);
                return $processor->get_updated_html();
            }

            return $html;
        }

        if (preg_match('/^(\s*<[a-zA-Z][^>]*\sclass=([\'"]))([^\'"]*)(\2)/i', $html, $m, PREG_OFFSET_CAPTURE)) {
            $classes = $m[3][0];
            if (!preg_match('/(?:^|\s)' . preg_quote($class, '/') . '(?:\s|$)/', $classes)) {
                $classes = trim($classes . ' ' . $class);
            }
            return substr_replace($html, $m[1][0] . $classes . $m[4][0], $m[1][1], strlen($m[1][0] . $m[3][0] . $m[4][0]));
        }

        return self::add_attribute($html, 'class', $class);
    }

    public static function merge_style(string $style, string $property, string $value): string
    {
        $decl = $property . ':' . $value;
        $style = trim($style);
        if ($style === '') {
            return $decl . ';';
        }

        $quoted = preg_quote($property, '/');
        $style = preg_replace('/(?:^|;)\s*' . $quoted . '\s*:[^;]*/i', '', $style) ?? $style;
        $style = trim($style, "; \t\n\r");

        return ($style === '' ? '' : $style . '; ') . $decl . ';';
    }

    /**
     * @param callable(string): string $patcher
     */
    private static function patch_tag(string $html, ?string $tag, callable $patcher): string
    {
        $tag_re = $tag !== null && $tag !== ''
            ? preg_quote($tag, '/')
            : '[a-zA-Z][a-zA-Z0-9:-]*';

        if (!preg_match('/<' . $tag_re . '\b[^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE)) {
            return $html;
        }

        $updated = $patcher($m[0][0]);

        return substr_replace($html, $updated, (int) $m[0][1], strlen($m[0][0]));
    }
}
