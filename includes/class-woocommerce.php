<?php

declare(strict_types=1);

final class WpMotion_Woocommerce
{
    public function boot(): void
    {
        add_filter('woocommerce_product_get_image', [$this, 'product_image'], 20, 2);
        add_filter('woocommerce_single_product_image_thumbnail_html', [$this, 'single_image'], 20, 2);
    }

    /**
     * @param mixed $product
     */
    public function product_image(string $html, $product): string
    {
        if ($html === '' || !WpMotion_Plugin::is_front_enabled()) {
            return $html;
        }

        $settings = WpMotion_Settings::get();
        if (empty($settings['shared_featured_image'])) {
            return $html;
        }

        $id = $this->product_id($product);
        if ($id <= 0) {
            return $html;
        }

        $html = WpMotion_Html::add_style($html, 'view-transition-name', WpMotion_Names::product_image($id));
        return WpMotion_Html::add_attribute($html, 'data-wpmotion-shared', WpMotion_Names::product_image($id));
    }

    public function single_image(string $html, $attachment_id): string
    {
        unset($attachment_id);

        if ($html === '' || !WpMotion_Plugin::is_front_enabled()) {
            return $html;
        }

        $settings = WpMotion_Settings::get();
        if (empty($settings['shared_featured_image'])) {
            return $html;
        }

        $id = (int) get_queried_object_id();
        if ($id <= 0) {
            return $html;
        }

        $html = WpMotion_Html::add_style($html, 'view-transition-name', WpMotion_Names::product_image($id));
        return WpMotion_Html::add_attribute($html, 'data-wpmotion-shared', WpMotion_Names::product_image($id));
    }

    /**
     * @param mixed $product
     */
    private function product_id($product): int
    {
        if (is_object($product) && method_exists($product, 'get_id')) {
            return (int) $product->get_id();
        }
        if (is_numeric($product)) {
            return (int) $product;
        }

        return 0;
    }
}
