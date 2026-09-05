<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class NamesHtmlTokensTest extends TestCase
{
    public function test_stable_names(): void
    {
        $this->assertSame('wpgsap-post-12-image', WpGsap_Names::post_image(12));
        $this->assertSame('wpgsap-post-12-title', WpGsap_Names::post_title(12));
        $this->assertSame('wpgsap-product-9-image', WpGsap_Names::product_image(9));
        $this->assertSame('wpgsap-media-4', WpGsap_Names::media(4));
        $this->assertSame('wpgsap-item-1', WpGsap_Names::ident('***', 1));
    }

    public function test_add_style_on_bare_tag(): void
    {
        $html = WpGsap_Html::add_style('<img src="x.jpg" alt="">', 'view-transition-name', 'wpgsap-post-1-image');
        $this->assertStringContainsString('view-transition-name:wpgsap-post-1-image', $html);
        $this->assertStringContainsString('src="x.jpg"', $html);
    }

    public function test_add_style_merges_existing(): void
    {
        $html = WpGsap_Html::add_style('<div style="color:red">', 'view-transition-name', 'wpgsap-site-header');
        $this->assertStringContainsString('color:red', $html);
        $this->assertStringContainsString('view-transition-name:wpgsap-site-header', $html);
    }

    public function test_tokens_css_and_clamp(): void
    {
        $tokens = WpGsap_Tokens::get([
            'duration_ms' => 4000,
            'easing' => 'snappy',
            'reduced_motion' => 'fade',
        ]);
        $this->assertSame(1200, $tokens['duration_ms']);
        $css = WpGsap_Tokens::to_css($tokens);
        $this->assertStringContainsString('--wpgsap-duration:1200ms', $css);
        $this->assertStringContainsString('cubic-bezier', $css);
    }
}
