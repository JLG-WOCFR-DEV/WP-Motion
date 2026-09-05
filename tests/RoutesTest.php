<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RoutesTest extends TestCase
{
    public function test_exact_beats_wildcard(): void
    {
        $routes = [
            ['from' => '*', 'to' => 'checkout', 'preset' => 'none', 'shared' => false],
            ['from' => 'archive', 'to' => 'single', 'preset' => 'fade', 'shared' => true],
            ['from' => '*', 'to' => '*', 'preset' => 'wipe', 'shared' => false],
        ];

        $checkout = WpGsap_Routes::match('single', 'checkout', $routes);
        $this->assertSame('none', $checkout['preset']);

        $article = WpGsap_Routes::match('archive', 'single', $routes);
        $this->assertSame('fade', $article['preset']);
        $this->assertTrue($article['shared']);
    }

    public function test_fallback_when_no_rule(): void
    {
        $matched = WpGsap_Routes::match('page', 'page', [], 'slide');
        $this->assertSame('slide', $matched['preset']);
    }

    public function test_singular_matches_single(): void
    {
        $routes = [
            ['from' => '*', 'to' => 'singular', 'preset' => 'fade', 'shared' => false],
        ];
        $matched = WpGsap_Routes::match('home', 'single', $routes);
        $this->assertSame('fade', $matched['preset']);
    }

    public function test_sanitize_drops_unknown_preset(): void
    {
        $clean = WpGsap_Routes::sanitize([
            ['from' => 'nope', 'to' => 'single', 'preset' => 'explode', 'shared' => '1'],
        ]);
        $this->assertSame('*', $clean[0]['from']);
        $this->assertSame('fade', $clean[0]['preset']);
        $this->assertTrue($clean[0]['shared']);
    }
}
