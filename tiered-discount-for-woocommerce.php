<?php

/**
 * Plugin Name: Tiered Discount for WooCommerce, Personalized Discounts, Advanced Coupon
 * Description: Elevate your WooCommerce store with dynamic tiered discounts based on cart conditions and user roles. Effortlessly managed from the WooCommerce coupon page.
 * Version: 1.0.1
 * Author: Repon Hossain
 * Author URI: https://workwithrepon.com
 * Text Domain: tiered-discount-for-woocommerce
 * 
 * Requires Plugins: woocommerce
 * Requires at least: 4.3
 * Requires PHP: 7.4.3
 * Tested up to: 6.5.3
 * 
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH')) {
	exit;
}

define('TIERED_DISCOUNT_FOR_WOOCOMMERCE_FILE', __FILE__);
define('TIERED_DISCOUNT_FOR_WOOCOMMERCE_VERSION', '1.0.1');
define('TIERED_DISCOUNT_FOR_WOOCOMMERCE_BASENAME', plugin_basename(__FILE__));
define('TIERED_DISCOUNT_FOR_WOOCOMMERCE_URI', trailingslashit(plugins_url('/', __FILE__)));
define('TIERED_DISCOUNT_FOR_WOOCOMMERCE_PATH', trailingslashit(plugin_dir_path(__FILE__)));
define('TIERED_DISCOUNT_FOR_WOOCOMMERCE_PHP_MIN', '7.4.3');

define('TIERED_DISCOUNT_FOR_WOOCOMMERCE_API_URI', 'https://codiepress.com');
define('TIERED_DISCOUNT_FOR_WOOCOMMERCE_PRODUCT_ID', 286);


/**
 * Check PHP version. Show notice if version of PHP less than our 7.4.3 
 * 
 * @since 1.0.0
 * @return void
 */
function tiered_discount_for_woocommerce_php_missing_notice() {
	$notice = sprintf(
		/* translators: 1 for plugin name, 2 for PHP, 3 for PHP version */
		esc_html__('%1$s need %2$s version %3$s or greater.', 'tiered-discount-for-woocommerce'),
		'<strong>Tiered Discount for WooCommerce</strong>',
		'<strong>PHP</strong>',
		TIERED_DISCOUNT_FOR_WOOCOMMERCE_PHP_MIN
	);

	printf('<div class="notice notice-warning"><p>%1$s</p></div>', wp_kses_post($notice));
}

/**
 * Admin notice for missing woocommerce
 * 
 * @since 1.0.0
 * @return void
 */
function tiered_discount_for_woocommerce_woocommerce_missing() {
	if (file_exists(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php')) {
		$notice_title = __('Activate WooCommerce', 'tiered-discount-for-woocommerce');
		$notice_url = wp_nonce_url('plugins.php?action=activate&plugin=woocommerce/woocommerce.php&plugin_status=all&paged=1', 'activate-plugin_woocommerce/woocommerce.php');
	} else {
		$notice_title = __('Install WooCommerce', 'tiered-discount-for-woocommerce');
		$notice_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=woocommerce'), 'install-plugin_woocommerce');
	}

	$notice = sprintf(
		/* translators: 1 for plugin name, 2 for WooCommerce, 3 WooCommerce link */
		esc_html__('%1$s need %2$s to be installed and activated to function properly. %3$s', 'tiered-discount-for-woocommerce'),
		'<strong>Tiered Discount for WooCommerce</strong>',
		'<strong>WooCommerce</strong>',
		'<a href="' . esc_url($notice_url) . '">' . $notice_title . '</a>'
	);

	printf('<div class="notice notice-warning"><p>%1$s</p></div>', wp_kses_post($notice));
}

/**
 * Load our plugin main file of pass our plugin requirement
 * 
 * @since 1.0.0
 * @return void
 */
function tiered_discount_for_woocommerce_load_plugin() {
	if (version_compare(PHP_VERSION, TIERED_DISCOUNT_FOR_WOOCOMMERCE_PHP_MIN, '<')) {
		return add_action('admin_notices', 'tiered_discount_for_woocommerce_php_missing_notice');
	}

	//Check WooCommerce activate
	if (!class_exists('WooCommerce', false)) {
		return add_action('admin_notices', 'tiered_discount_for_woocommerce_woocommerce_missing');
	}

	require_once TIERED_DISCOUNT_FOR_WOOCOMMERCE_PATH . 'inc/class-main.php';
}
add_action('plugins_loaded', 'tiered_discount_for_woocommerce_load_plugin');


require __DIR__ . '/vendor/autoload.php';


/**
 * Initialize the plugin tracker
 *
 * @return void
 */
function appsero_init_tracker_tiered_discount_for_woocommerce() {

    if ( ! class_exists( 'Appsero\Client' ) ) {
      require_once __DIR__ . '/appsero/src/Client.php';
    }

    $client = new Appsero\Client( 'dd28ba41-8837-40eb-ba96-fb45b82a2bd5', 'Tiered Discount for WooCommerce', __FILE__ );

    // Active insights
    $client->insights()->init();

}

appsero_init_tracker_tiered_discount_for_woocommerce();
