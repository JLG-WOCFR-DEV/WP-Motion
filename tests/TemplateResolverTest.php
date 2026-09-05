<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class TemplateResolverTest extends TestCase
{
    public function test_longest_prefix_wins(): void
    {
        $known = [
            'shop' => '/shop',
            'cart' => '/cart',
            'checkout' => '/checkout',
            'account' => '/my-account',
            'home' => '/',
        ];

        $this->assertSame('checkout', WpMotion_Template_Resolver::from_path('/checkout/pay', $known));
        $this->assertSame('shop', WpMotion_Template_Resolver::from_path('/shop/page/2', $known));
        $this->assertSame('home', WpMotion_Template_Resolver::from_path('/', $known));
        $this->assertSame('unknown', WpMotion_Template_Resolver::from_path('/hello-world', $known));
        $this->assertSame('excluded', WpMotion_Template_Resolver::from_path('/wp-admin/', $known));
    }
}
