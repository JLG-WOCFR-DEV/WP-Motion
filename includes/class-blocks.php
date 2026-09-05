<?php

declare(strict_types=1);

final class WpGsap_Blocks
{
    public function boot(): void
    {
        add_action('enqueue_block_editor_assets', [$this, 'editor_assets']);
    }

    public function editor_assets(): void
    {
        wp_enqueue_script(
            'wpgsap-editor',
            WPGSAP_URL . 'assets/js/editor.js',
            ['wp-blocks', 'wp-hooks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor', 'wp-compose'],
            WPGSAP_VERSION,
            true
        );

        wp_set_script_translations('wpgsap-editor', 'wp-gsap', WPGSAP_DIR . 'languages');
    }
}
