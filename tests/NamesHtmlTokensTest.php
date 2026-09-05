<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class NamesHtmlTokensTest extends TestCase
{
    public function test_stable_names(): void
    {
        $this->assertSame('wpmotion-post-12-image', WpMotion_Names::post_image(12));
        $this->assertSame('wpmotion-post-12-title', WpMotion_Names::post_title(12));
        $this->assertSame('wpmotion-product-9-image', WpMotion_Names::product_image(9));
        $this->assertSame('wpmotion-media-4', WpMotion_Names::media(4));
        $this->assertSame('wpmotion-item-1', WpMotion_Names::ident('***', 1));
    }

    public function test_add_style_on_bare_tag(): void
    {
        $html = WpMotion_Html::add_style('<img src="x.jpg" alt="">', 'view-transition-name', 'wpmotion-post-1-image');
        $this->assertStringContainsString('view-transition-name:wpmotion-post-1-image', $html);
        $this->assertStringContainsString('src="x.jpg"', $html);
    }

    public function test_add_style_merges_existing(): void
    {
        $html = WpMotion_Html::add_style('<div style="color:red">', 'view-transition-name', 'wpmotion-site-header');
        $this->assertStringContainsString('color:red', $html);
        $this->assertStringContainsString('view-transition-name:wpmotion-site-header', $html);
    }

    public function test_tokens_css_and_clamp(): void
    {
        $tokens = WpMotion_Tokens::get([
            'duration_ms' => 4000,
            'easing' => 'snappy',
            'reduced_motion' => 'fade',
        ]);
        $this->assertSame(1200, $tokens['duration_ms']);
        $css = WpMotion_Tokens::to_css($tokens);
        $this->assertStringContainsString('--wpmotion-duration:1200ms', $css);
        $this->assertStringContainsString('cubic-bezier', $css);
    }
}
