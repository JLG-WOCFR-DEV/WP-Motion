<?php

declare(strict_types=1);

final class WpMotion_Names
{
    public const LOGO = 'wpmotion-site-logo';
    public const HEADER = 'wpmotion-site-header';

    public static function ident(string $kind, int $id): string
    {
        $kind = strtolower($kind);
        $kind = preg_replace('/[^a-z0-9-]/', '', $kind) ?? 'item';
        if ($kind === '') {
            $kind = 'item';
        }

        return 'wpmotion-' . $kind . '-' . max(0, $id);
    }

    public static function post_image(int $post_id): string
    {
        return self::ident('post', $post_id) . '-image';
    }

    public static function post_title(int $post_id): string
    {
        return self::ident('post', $post_id) . '-title';
    }

    public static function media(int $attachment_id): string
    {
        return self::ident('media', $attachment_id);
    }

    public static function product_image(int $product_id): string
    {
        return self::ident('product', $product_id) . '-image';
    }
}
