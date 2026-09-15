<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdminCompatTest extends TestCase
{
    public function test_readme_declares_wordpress_71(): void
    {
        $readme = (string) file_get_contents(WPMOTION_DIR . 'readme.txt');
        $this->assertMatchesRegularExpression('/^Tested up to:\s*7\.1\s*$/m', $readme);
        $this->assertMatchesRegularExpression('/^Stable tag:\s*1\.2\.4\s*$/m', $readme);
        $this->assertDoesNotMatchRegularExpression('/^Tested up to:\s*6\.8\s*$/m', $readme);
    }

    public function test_plugin_header_declares_wordpress_71(): void
    {
        $plugin = (string) file_get_contents(WPMOTION_DIR . 'wp-motion.php');
        $this->assertMatchesRegularExpression('/^\s*\*\s*Tested up to:\s*7\.1\s*$/m', $plugin);
        $this->assertStringContainsString("define('WPMOTION_VERSION', '1.2.4')", $plugin);
        $this->assertDoesNotMatchRegularExpression('/Tested up to:\s*6\.8/', $plugin);
    }

    public function test_menu_stays_under_settings(): void
    {
        $admin = (string) file_get_contents(WPMOTION_DIR . 'includes/class-admin.php');
        $this->assertStringContainsString('add_options_page(', $admin);
        $this->assertStringNotContainsString('add_menu_page(', $admin);
        $this->assertStringContainsString('settings_page_wp-motion', $admin);
        $this->assertStringNotContainsString('toplevel_page_wp-motion', $admin);
        $this->assertStringNotContainsString('admin.php?page=wp-motion', $admin);
        $this->assertStringNotContainsString('remove_submenu_page', $admin);
        $this->assertStringNotContainsString('remove_menu_page', $admin);
    }

    public function test_plugin_never_calls_remove_submenu_page(): void
    {
        $files = glob(WPMOTION_DIR . 'includes/*.php') ?: [];
        $files[] = WPMOTION_DIR . 'wp-motion.php';
        foreach ($files as $file) {
            $this->assertStringNotContainsString(
                'remove_submenu_page',
                (string) file_get_contents($file),
                $file . ' must not call remove_submenu_page'
            );
        }
    }

    public function test_admin_bar_toggle_stays_on_admin_post(): void
    {
        $admin = (string) file_get_contents(WPMOTION_DIR . 'includes/class-admin.php');
        $this->assertStringContainsString('admin-post.php?action=wpmotion_toggle', $admin);
        $this->assertStringContainsString('admin_post_wpmotion_toggle', $admin);
        $this->assertStringContainsString('redirect_legacy_toplevel', $admin);
        $this->assertStringContainsString("'target' => '_top'", $admin);
        $this->assertStringContainsString('admin_bar_link_meta', $admin);
    }

    public function test_admin_bar_link_meta_targets_top_window(): void
    {
        $meta = WpMotion_Admin::admin_bar_link_meta();
        $this->assertSame('_top', $meta['target']);
        $this->assertSame('wpmotion-admin-bar-link', $meta['class']);
    }

    public function test_toggle_redirect_avoids_admin_post_loop(): void
    {
        $settings = WpMotion_Admin::toggle_redirect_target('');
        $this->assertStringContainsString('options-general.php?page=wp-motion', $settings);

        $loop = WpMotion_Admin::toggle_redirect_target('https://example.test/wp-admin/admin-post.php?action=wpmotion_toggle');
        $this->assertStringContainsString('options-general.php?page=wp-motion', $loop);

        $editor = WpMotion_Admin::toggle_redirect_target('https://example.test/wp-admin/site-editor.php?canvas=edit');
        $this->assertSame('https://example.test/wp-admin/site-editor.php?canvas=edit', $editor);

        $post = WpMotion_Admin::toggle_redirect_target('https://example.test/wp-admin/post.php?post=12&action=edit');
        $this->assertSame('https://example.test/wp-admin/post.php?post=12&action=edit', $post);
    }

    public function test_import_export_uses_settings_page_url(): void
    {
        $import = (string) file_get_contents(WPMOTION_DIR . 'includes/class-import-export.php');
        $this->assertStringContainsString('WpMotion_Admin::page_url(\'tools\')', $import);
        $this->assertStringNotContainsString('admin.php?page=wp-motion', $import);
    }

    public function test_page_url_falls_back_to_options_general(): void
    {
        $url = WpMotion_Admin::page_url();
        $this->assertStringContainsString('options-general.php?page=wp-motion', $url);
        $this->assertStringNotContainsString('admin.php?page=', $url);
    }

    public function test_page_url_keeps_tab_and_notices_for_admin_bar_links(): void
    {
        $preview = WpMotion_Admin::page_url('preview');
        $this->assertStringContainsString('options-general.php?page=wp-motion', $preview);
        $this->assertStringContainsString('tab=preview', $preview);

        $reset = WpMotion_Admin::page_url('routes', ['wpmotion' => 'routes-reset']);
        $this->assertStringContainsString('tab=routes', $reset);
        $this->assertStringContainsString('wpmotion=routes-reset', $reset);
    }

    public function test_inspector_stays_on_parent_document(): void
    {
        $js = (string) file_get_contents(WPMOTION_DIR . 'assets/js/editor.js');
        $php = (string) file_get_contents(WPMOTION_DIR . 'includes/class-blocks.php');

        $this->assertStringContainsString('isIframedCanvasDocument', $js);
        $this->assertStringContainsString("block-editor-iframe__body", $js);
        $this->assertStringContainsString("block-editor-iframe__html", $js);
        $this->assertStringContainsString('editor-canvas', $js);
        $this->assertStringContainsString('wp.blockEditor && wp.blockEditor.InspectorControls', $js);
        $this->assertStringContainsString('__next40pxDefaultSize: true', $js);
        $this->assertStringContainsString('__nextHasNoMarginBottom: true', $js);
        $this->assertStringContainsString("getElementById('wp-admin-bar-wpmotion')", $js);
        $this->assertStringContainsString("setAttribute('target', '_top')", $js);
        $this->assertStringNotContainsString('wp.editor', $js);
        $this->assertStringNotContainsString('document.querySelector', $js);
        $this->assertStringNotContainsString('document.head', $js);
        $this->assertStringNotContainsString('contentDocument', $js);
        $this->assertStringContainsString('enqueue_block_editor_assets', $php);
        $this->assertStringContainsString('wp-block-editor', $php);
        $this->assertStringNotContainsString("'wp-editor'", $php);
        $this->assertStringNotContainsString("add_action('enqueue_block_assets'", $php);
    }

    public function test_front_js_skips_iframed_editor_canvas(): void
    {
        $js = (string) file_get_contents(WPMOTION_DIR . 'assets/js/front.js');
        $this->assertStringContainsString('function isEditorCanvas()', $js);
        $this->assertStringContainsString("block-editor-iframe__body", $js);
        $this->assertStringContainsString('window.parent.WPMOTION_EDITOR', $js);
        $this->assertStringContainsString('if (isEditorCanvas())', $js);
        $this->assertStringContainsString('WPMOTION_FRONT', $js);
    }

    public function test_front_assets_are_not_copied_into_editor_iframe(): void
    {
        $php = (string) file_get_contents(WPMOTION_DIR . 'includes/class-view-transitions.php');
        $plugin = (string) file_get_contents(WPMOTION_DIR . 'includes/class-plugin.php');
        $this->assertStringContainsString("add_action('wp_enqueue_scripts'", $php);
        $this->assertStringNotContainsString("add_action('enqueue_block_assets'", $php);
        $this->assertStringNotContainsString("add_action('enqueue_block_editor_assets'", $php);
        $this->assertStringContainsString('wp_is_block_editor', $plugin);
    }
}
