<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdminCompatTest extends TestCase
{
    public function test_readme_declares_wordpress_71(): void
    {
        $readme = (string) file_get_contents(WPMOTION_DIR . 'readme.txt');
        $this->assertMatchesRegularExpression('/^Tested up to:\s*7\.1\s*$/m', $readme);
        $this->assertMatchesRegularExpression('/^Stable tag:\s*1\.2\.3\s*$/m', $readme);
    }

    public function test_plugin_header_declares_wordpress_71(): void
    {
        $plugin = (string) file_get_contents(WPMOTION_DIR . 'wp-motion.php');
        $this->assertMatchesRegularExpression('/^\s*\*\s*Tested up to:\s*7\.1\s*$/m', $plugin);
        $this->assertStringContainsString("define('WPMOTION_VERSION', '1.2.3')", $plugin);
    }

    public function test_menu_is_registered_under_settings(): void
    {
        $admin = (string) file_get_contents(WPMOTION_DIR . 'includes/class-admin.php');
        $this->assertStringContainsString('add_options_page(', $admin);
        $this->assertStringNotContainsString('add_menu_page(', $admin);
        $this->assertStringContainsString('settings_page_wp-motion', $admin);
        $this->assertStringNotContainsString('toplevel_page_wp-motion', $admin);
        $this->assertStringNotContainsString('admin.php?page=wp-motion', $admin);
    }

    public function test_admin_bar_toggle_stays_on_admin_post(): void
    {
        $admin = (string) file_get_contents(WPMOTION_DIR . 'includes/class-admin.php');
        $this->assertStringContainsString('admin-post.php?action=wpmotion_toggle', $admin);
        $this->assertStringContainsString('admin_post_wpmotion_toggle', $admin);
        $this->assertStringContainsString('redirect_legacy_toplevel', $admin);
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

        $this->assertStringContainsString('wp.blockEditor && wp.blockEditor.InspectorControls', $js);
        $this->assertStringContainsString('__next40pxDefaultSize: true', $js);
        $this->assertStringNotContainsString('wp.editor', $js);
        $this->assertStringNotContainsString('document.querySelector', $js);
        $this->assertStringNotContainsString('document.head', $js);
        $this->assertStringContainsString('enqueue_block_editor_assets', $php);
        $this->assertStringContainsString('wp-block-editor', $php);
        $this->assertStringNotContainsString("'wp-editor'", $php);
    }
}
