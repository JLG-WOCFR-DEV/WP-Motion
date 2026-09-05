<?php

declare(strict_types=1);

final class WpGsap_Shared_Elements
{
    public function boot(): void
    {
        add_filter('render_block', [$this, 'filter_block'], 12, 2);
        add_filter('post_thumbnail_html', [$this, 'filter_thumbnail'], 12, 2);
        add_filter('get_custom_logo', [$this, 'filter_logo'], 12);
        add_filter('post_class', [$this, 'filter_post_class'], 10, 3);
        add_action('wp_head', [$this, 'print_header_css'], 30);
    }

    /**
     * @param array<string, mixed> $block
     */
    public function filter_block(string $content, array $block): string
    {
        if ($content === '' || !WpGsap_Plugin::is_front_enabled()) {
            return $content;
        }

        $settings = WpGsap_Settings::get();
        $name = (string) ($block['blockName'] ?? '');
        $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
        $participate = array_key_exists('wpGsapParticipate', $attrs) ? $attrs['wpGsapParticipate'] : null;

        if ($name === 'core/template-part' && !empty($settings['header_persistent'])) {
            $slug = (string) ($attrs['slug'] ?? '');
            if ($slug === 'header') {
                $content = WpGsap_Html::add_style($content, 'view-transition-name', WpGsap_Names::HEADER);
                $content = WpGsap_Html::add_attribute($content, 'data-wpgsap-shared', WpGsap_Names::HEADER);
            }
        }

        if ($name === 'core/post-featured-image' && $this->should_auto($settings['shared_featured_image'], $participate)) {
            $post_id = $this->block_post_id($block);
            if ($post_id > 0) {
                $content = $this->mark($content, WpGsap_Names::post_image($post_id));
            }
        }

        if ($name === 'core/post-title' && $this->should_auto($settings['shared_title'], $participate)) {
            $post_id = $this->block_post_id($block);
            if ($post_id > 0) {
                $content = $this->mark($content, WpGsap_Names::post_title($post_id));
            }
        }

        if (in_array($name, ['core/image', 'core/cover'], true) && $participate === true) {
            $media_id = (int) ($attrs['id'] ?? 0);
            $vt = $media_id > 0 ? WpGsap_Names::media($media_id) : WpGsap_Names::post_image($this->block_post_id($block));
            $content = $this->mark($content, $vt);
        }

        if (in_array($name, ['core/heading', 'core/group'], true)) {
            $scene = is_string($attrs['wpGsapScene'] ?? null) ? $attrs['wpGsapScene'] : '';
            if ($scene !== '' && $scene !== 'none') {
                $content = WpGsap_Html::add_attribute($content, 'data-wpgsap-scene', sanitize_key($scene));
                $content = WpGsap_Html::add_class($content, 'wpgsap-scene');
                $content = WpGsap_Html::add_class($content, 'wpgsap-scene--' . sanitize_key($scene));
            }
        }

        if ($name === 'core/heading' && $participate === true) {
            $post_id = $this->block_post_id($block);
            if ($post_id > 0) {
                $content = $this->mark($content, WpGsap_Names::post_title($post_id));
            }
        }

        return $content;
    }

    public function filter_thumbnail(string $html, $post_id): string
    {
        if ($html === '' || !WpGsap_Plugin::is_front_enabled()) {
            return $html;
        }

        $settings = WpGsap_Settings::get();
        if (empty($settings['shared_featured_image'])) {
            return $html;
        }

        return $this->mark($html, WpGsap_Names::post_image((int) $post_id));
    }

    public function filter_logo(string $html): string
    {
        if ($html === '' || !WpGsap_Plugin::is_front_enabled()) {
            return $html;
        }

        return $this->mark($html, WpGsap_Names::LOGO);
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
            $classes[] = 'wpgsap-post-' . $post_id;
        }

        return $classes;
    }

    public function print_header_css(): void
    {
        if (!WpGsap_Plugin::is_front_enabled()) {
            return;
        }

        $settings = WpGsap_Settings::get();
        if (empty($settings['header_persistent'])) {
            return;
        }

        $selector = (string) $settings['header_selector'];
        echo '<style id="wpgsap-header">' . $selector . '{view-transition-name:' . WpGsap_Names::HEADER . ';}</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

    private function mark(string $html, string $name): string
    {
        $html = WpGsap_Html::add_style($html, 'view-transition-name', $name);
        return WpGsap_Html::add_attribute($html, 'data-wpgsap-shared', $name);
    }
}
