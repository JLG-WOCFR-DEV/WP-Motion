<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class NamesHtmlTokensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        WpMotion_Names::reset();
    }

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

    public function test_claim_is_unique_per_request(): void
    {
        $this->assertTrue(WpMotion_Names::claim('wpmotion-post-1-image'));
        $this->assertFalse(WpMotion_Names::claim('wpmotion-post-1-image'));
        $this->assertTrue(WpMotion_Names::claim('wpmotion-post-2-image'));
        $this->assertFalse(WpMotion_Names::claim(''));
    }

    public function test_mark_skips_duplicate_names(): void
    {
        $first = WpMotion_Shared_Elements::mark('<img src="a.jpg">', 'wpmotion-post-1-image');
        $second = WpMotion_Shared_Elements::mark('<img src="b.jpg">', 'wpmotion-post-1-image');
        $this->assertStringContainsString('view-transition-name:wpmotion-post-1-image', $first);
        $this->assertStringNotContainsString('view-transition-name', $second);
        $this->assertStringContainsString('src="b.jpg"', $second);
    }

    public function test_image_name_is_applied_to_img_not_figure(): void
    {
        $html = '<figure class="wp-block-post-featured-image"><img src="hero.jpg" alt="Hero"></figure>';
        $out = WpMotion_Shared_Elements::mark($html, 'wpmotion-post-4-image');

        $this->assertMatchesRegularExpression('/<img[^>]*view-transition-name:wpmotion-post-4-image/', $out);
        $this->assertDoesNotMatchRegularExpression('/<figure[^>]*view-transition-name/', $out);
        $this->assertMatchesRegularExpression('/<img[^>]*data-wpmotion-shared="wpmotion-post-4-image"/', $out);
    }

    public function test_image_like_names_prefer_img_tag(): void
    {
        $this->assertTrue(WpMotion_Shared_Elements::prefer_img('wpmotion-post-3-image'));
        $this->assertTrue(WpMotion_Shared_Elements::prefer_img(WpMotion_Names::LOGO));
        $this->assertFalse(WpMotion_Shared_Elements::prefer_img('wpmotion-post-3-title'));
        $this->assertFalse(WpMotion_Shared_Elements::prefer_img(WpMotion_Names::HEADER));
    }
}
