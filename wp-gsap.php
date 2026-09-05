<?php
/**
 * Plugin Name: WP-GSAP
 * Description: Transitions de pages modernes (View Transitions) et scènes de mouvement optionnelles pour WordPress. GSAP n’est chargé que si une scène l’exige.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: JLG
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-gsap
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WPGSAP_VERSION', '1.0.0');
define('WPGSAP_FILE', __FILE__);
define('WPGSAP_DIR', plugin_dir_path(__FILE__));
define('WPGSAP_URL', plugin_dir_url(__FILE__));

require_once WPGSAP_DIR . 'includes/autoload.php';

add_action('plugins_loaded', static function (): void {
    load_plugin_textdomain('wp-gsap', false, dirname(plugin_basename(WPGSAP_FILE)) . '/languages');
    WpGsap_Plugin::instance()->boot();
});
