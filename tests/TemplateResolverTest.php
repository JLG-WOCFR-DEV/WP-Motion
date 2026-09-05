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

        $this->assertSame('checkout', WpGsap_Template_Resolver::from_path('/checkout/pay', $known));
        $this->assertSame('shop', WpGsap_Template_Resolver::from_path('/shop/page/2', $known));
        $this->assertSame('home', WpGsap_Template_Resolver::from_path('/', $known));
        $this->assertSame('unknown', WpGsap_Template_Resolver::from_path('/hello-world', $known));
        $this->assertSame('excluded', WpGsap_Template_Resolver::from_path('/wp-admin/', $known));
    }
}
