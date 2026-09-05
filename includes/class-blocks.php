<?php

declare(strict_types=1);

final class WpMotion_Blocks
{
    public function boot(): void
    {
        add_action('enqueue_block_editor_assets', [$this, 'editor_assets']);
    }

    public function editor_assets(): void
    {
        wp_enqueue_script(
            'wpmotion-editor',
            WPMOTION_URL . 'assets/js/editor.js',
            ['wp-blocks', 'wp-hooks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor', 'wp-compose'],
            WPMOTION_VERSION,
            true
        );

        wp_set_script_translations('wpmotion-editor', 'wp-motion', WPMOTION_DIR . 'languages');

        $settings = WpMotion_Settings::get();
        wp_localize_script('wpmotion-editor', 'WPMOTION_EDITOR', [
            'autoFeatured' => !empty($settings['shared_featured_image']),
            'autoTitle' => !empty($settings['shared_title']),
        ]);
    }
}
