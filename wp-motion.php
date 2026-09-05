<?php
/**
 * Plugin Name: WP-Motion
 * Description: Transitions de pages (View Transitions) chorégraphiées avec Motion (MIT). Open source, sans GSAP.
 * Version: 1.1.1
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: JLG
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-motion
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WPMOTION_VERSION', '1.1.1');
define('WPMOTION_FILE', __FILE__);
define('WPMOTION_DIR', plugin_dir_path(__FILE__));
define('WPMOTION_URL', plugin_dir_url(__FILE__));

require_once WPMOTION_DIR . 'includes/autoload.php';

add_action('plugins_loaded', static function (): void {
    load_plugin_textdomain('wp-motion', false, dirname(plugin_basename(WPMOTION_FILE)) . '/languages');
    WpMotion_Plugin::instance()->boot();
});
