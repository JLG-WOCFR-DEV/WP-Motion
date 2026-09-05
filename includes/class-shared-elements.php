<?php

declare(strict_types=1);

final class WpMotion_Shared_Elements
{
    public function boot(): void
    {
        add_filter('render_block', [$this, 'filter_block'], 12, 2);
        add_filter('post_thumbnail_html', [$this, 'filter_thumbnail'], 12, 2);
        add_filter('get_custom_logo', [$this, 'filter_logo'], 12);
        add_filter('post_class', [$this, 'filter_post_class'], 10, 3);
    }

    /**
     * @param array<string, mixed> $block
     */
    public function filter_block(string $content, array $block): string
    {
        if ($content === '' || !WpMotion_Plugin::is_front_enabled()) {
            return $content;
        }

        $settings = WpMotion_Settings::get();
        $name = (string) ($block['blockName'] ?? '');
        $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
        $participate = null;
        if (array_key_exists('wpMotionParticipate', $attrs)) {
            $participate = $attrs['wpMotionParticipate'];
        } elseif (array_key_exists('wpGsapParticipate', $attrs)) {
            $participate = $attrs['wpGsapParticipate'];
        }

        if ($name === 'core/template-part' && !empty($settings['header_persistent'])) {
            $slug = (string) ($attrs['slug'] ?? '');
            if ($slug === 'header') {
                $content = self::mark($content, WpMotion_Names::HEADER);
            }
        }

        if ($name === 'core/post-featured-image' && $this->should_auto($settings['shared_featured_image'], $participate)) {
            $post_id = $this->block_post_id($block);
            if ($post_id > 0) {
                $content = self::mark($content, WpMotion_Names::post_image($post_id));
            }
        }

        if ($name === 'core/post-title' && $this->should_auto($settings['shared_title'], $participate)) {
            $post_id = $this->block_post_id($block);
            if ($post_id > 0) {
                $content = self::mark($content, WpMotion_Names::post_title($post_id));
            }
        }

        if (in_array($name, ['core/image', 'core/cover'], true) && $participate === true) {
            $media_id = (int) ($attrs['id'] ?? 0);
            $vt = $media_id > 0 ? WpMotion_Names::media($media_id) : WpMotion_Names::post_image($this->block_post_id($block));
            $content = self::mark($content, $vt);
        }

        if (in_array($name, ['core/heading', 'core/group'], true)) {
            $scene = is_string($attrs['wpMotionScene'] ?? null) ? $attrs['wpMotionScene'] : '';
            if ($scene === '' && is_string($attrs['wpGsapScene'] ?? null)) {
                $scene = $attrs['wpGsapScene'];
            }
            if ($scene !== '' && $scene !== 'none') {
                $content = WpMotion_Html::add_attribute($content, 'data-wpmotion-scene', sanitize_key($scene));
                $content = WpMotion_Html::add_class($content, 'wpmotion-scene');
                $content = WpMotion_Html::add_class($content, 'wpmotion-scene--' . sanitize_key($scene));
            }
        }

        if ($name === 'core/heading' && $participate === true) {
            $post_id = $this->block_post_id($block);
            if ($post_id > 0) {
                $content = self::mark($content, WpMotion_Names::post_title($post_id));
            }
        }

        return $content;
    }

    public function filter_thumbnail(string $html, $post_id): string
    {
        if ($html === '' || !WpMotion_Plugin::is_front_enabled()) {
            return $html;
        }

        $settings = WpMotion_Settings::get();
        if (empty($settings['shared_featured_image'])) {
            return $html;
        }

        return self::mark($html, WpMotion_Names::post_image((int) $post_id));
    }

    public function filter_logo(string $html): string
    {
        if ($html === '' || !WpMotion_Plugin::is_front_enabled()) {
            return $html;
        }

        return self::mark($html, WpMotion_Names::LOGO);
    }

    /**
     * @param list<string> $classes
     * @param list<string> $css
     * @return list<string>
     */
    public function filter_post_class(array $classes, $css, $post_id): array
    {
        unset($css);
        $post_id = (int) $post_id;
        if ($post_id > 0) {
            $classes[] = 'wpmotion-post-' . $post_id;
        }

        return $classes;
    }

    /**
     * Apply a unique view-transition-name. First claimant in the request wins.
     */
    public static function mark(string $html, string $name): string
    {
        if ($html === '' || $name === '' || !WpMotion_Names::claim($name)) {
            return $html;
        }

        $html = WpMotion_Html::add_style($html, 'view-transition-name', $name);
        return WpMotion_Html::add_attribute($html, 'data-wpmotion-shared', $name);
    }

    /**
     * @param mixed $participate
     */
    private function should_auto(bool $setting, $participate): bool
    {
        if ($participate === false || $participate === 0 || $participate === '0') {
            return false;
        }
        if ($participate === true) {
            return true;
        }

        return $setting;
    }

    /**
     * @param array<string, mixed> $block
     */
    private function block_post_id(array $block): int
    {
        $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
        if (!empty($attrs['id']) && get_post_type((int) $attrs['id']) && get_post_type((int) $attrs['id']) !== 'attachment') {
            return (int) $attrs['id'];
        }

        $post_id = get_the_ID();
        return $post_id ? (int) $post_id : (int) get_queried_object_id();
    }
}
