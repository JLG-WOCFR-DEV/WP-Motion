<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SettingsImportTest extends TestCase
{
    public function test_sanitize_clamps_and_filters_paths(): void
    {
        $clean = WpMotion_Settings::sanitize([
            'enabled' => 'on',
            'preset' => 'nope',
            'duration_ms' => 12,
            'easing' => 'snappy',
            'reduced_motion' => 'fade',
            'exclude_paths' => "# comment\n/cart/\n/cart/\n",
            'header_persistent' => 0,
            'header_selector' => '#masthead, header.site-header',
            'shared_featured_image' => '1',
            'shared_title' => false,
            'routes' => [],
        ]);

        $this->assertTrue($clean['enabled']);
        $this->assertSame('fade', $clean['preset']);
        $this->assertSame(80, $clean['duration_ms']);
        $this->assertSame(['/cart/'], $clean['exclude_paths']);
        $this->assertFalse($clean['header_persistent']);
        $this->assertFalse($clean['shared_title']);
        $this->assertSame([], $clean['routes']);
    }

    public function test_import_extracts_nested_or_flat_json(): void
    {
        $nested = WpMotion_Import_Export::extract_settings([
            'plugin' => 'wp-motion',
            'settings' => ['enabled' => true, 'preset' => 'slide'],
        ]);
        $this->assertSame('slide', $nested['preset']);

        $flat = WpMotion_Import_Export::extract_settings(['enabled' => false, 'routes' => []]);
        $this->assertIsArray($flat);

        $this->assertNull(WpMotion_Import_Export::extract_settings('nope'));
        $this->assertNull(WpMotion_Import_Export::extract_settings(['foo' => 1]));
    }
}
