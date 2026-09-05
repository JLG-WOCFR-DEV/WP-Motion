<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ExclusionsTest extends TestCase
{
    public function test_hardcoded_admin_and_rest(): void
    {
        $this->assertTrue(WpMotion_Exclusions::match('/wp-admin/plugins.php'));
        $this->assertTrue(WpMotion_Exclusions::match('/wp-login.php'));
        $this->assertTrue(WpMotion_Exclusions::match('/wp-json/wp/v2/posts'));
        $this->assertTrue(WpMotion_Exclusions::match('/feed/'));
        $this->assertFalse(WpMotion_Exclusions::match('/blog/hello-world'));
    }

    public function test_user_paths(): void
    {
        $paths = ['/checkout/', '/panier', '/my-account/orders'];
        $this->assertTrue(WpMotion_Exclusions::match('/checkout', $paths));
        $this->assertTrue(WpMotion_Exclusions::match('/checkout/pay', $paths));
        $this->assertTrue(WpMotion_Exclusions::match('/panier/', $paths));
        $this->assertFalse(WpMotion_Exclusions::match('/boutique', $paths));
    }

    public function test_normalize_strips_query(): void
    {
        $this->assertSame('/checkout', WpMotion_Exclusions::normalize('https://example.com/checkout/?foo=1'));
        $this->assertSame('/', WpMotion_Exclusions::normalize(''));
    }
}
