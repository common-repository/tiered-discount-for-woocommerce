<?php

namespace Tiered_Discount_For_WooCommerce;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Main class plugin
 */
final class Main {

	/**
	 * Hold the current instance of plugin
	 * 
	 * @since 1.0.0
	 * @var Main
	 */
	private static $instance = null;

	/**
	 * Get instance of current class
	 * 
	 * @since 1.0.0
	 * @return Main
	 */
	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hold admin class
	 * 
	 * @since 1.0.0
	 * @var Admin
	 */
	public $admin = null;

	/**
	 * Conditions template class
	 * 
	 * @since 1.0.0
	 * @var Condition_Template
	 */
	public $condition_templates = null;

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since 1.0
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong(__FUNCTION__, esc_html__('Cheating huh?', 'tiered-discount-for-woocommerce'), '1.0.0');
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @since 1.0
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong(__FUNCTION__, esc_html__('Cheating huh?', 'tiered-discount-for-woocommerce'), '1.0.0');
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->include_files();
		$this->init();
		$this->hooks();
	}

	/**
	 * Load plugin files
	 * 
	 * @version 1.0.0
	 * @return void
	 */
	public function include_files() {
		require_once TIERED_DISCOUNT_FOR_WOOCOMMERCE_PATH . 'inc/class-utils.php';
		require_once TIERED_DISCOUNT_FOR_WOOCOMMERCE_PATH . 'inc/class-admin.php';
		require_once TIERED_DISCOUNT_FOR_WOOCOMMERCE_PATH . 'inc/class-condition-templates.php';
	}

	/**
	 * Initialize classes
	 * 
	 * @since 1.0.0
	 */
	public function init() {
		$this->admin = new Admin();
		$this->condition_templates = new Condition_Templates();
	}

	/**
	 * Add hooks of plugin
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function hooks() {
		add_action('admin_notices', array($this, 'disabled_coupon_feature'));
		add_filter('plugin_action_links', array($this, 'add_plugin_links'), 10, 2);
		add_filter('woocommerce_coupon_get_amount', array($this, 'woocommerce_get_shop_coupon_data'), 200, 2);
		add_filter('tiered_discount_for_woocommerce/condition_matched', array($this, 'cart_condition_type'), 10, 2);
		add_filter('tiered_discount_for_woocommerce/condition_matched', array($this, 'date_condition_type'), 10, 2);
		add_filter('tiered_discount_for_woocommerce/condition_matched', array($this, 'customer_condition_type'), 10, 2);
		add_filter('tiered_discount_for_woocommerce/condition_matched', array($this, 'billing_shipping_condition_match'), 10, 2);
	}

	/**
	 * Show notice for not enabled coupon feature
	 * 
	 * @since 1.0.1
	 */
	public function disabled_coupon_feature() {
		if (Utils::enabled_woocommerce_coupon()) {
			return;
		}

		$notice = sprintf(
			/* translators: 1 for plugin name, 2 for link */
			esc_html__('%1$s need to enable WooCommerce coupon. Please %2$s and enable coupons.', 'tiered-discount-for-woocommerce'),
			'<strong>Tiered Discount for WooCommerce</strong>',
			'<a href="' . esc_url(admin_url('admin.php?page=wc-settings')) . '">' . __('go here') . '</a>'
		);

		printf('<div class="notice notice-warning"><p>%1$s</p></div>', wp_kses_post($notice));
	}

	/**
	 * Add add coupon link in plugin links
	 * 
	 * @since 1.0.1
	 * @return array
	 */
	public function add_plugin_links($actions, $plugin_file) {
		if (TIERED_DISCOUNT_FOR_WOOCOMMERCE_BASENAME == $plugin_file) {
			$new_links = array();
			if (Utils::enabled_woocommerce_coupon()) {
				$new_links[] = sprintf('<a href="%s">%s</a>', admin_url('edit.php?post_type=shop_coupon'), __('Add Coupon', 'tiered-discount-for-woocommerce'));
			}

			$actions = array_merge($new_links, $actions);
		}

		return $actions;
	}

	/**
	 * Update coupon amount
	 * 
	 * @since 1.0.0
	 * @return float
	 */
	public function woocommerce_get_shop_coupon_data($amount, $coupon) {
		if (is_admin()) {
			return $amount;
		}

		$tiered_data = Utils::get_coupon_data($coupon->get_id());
		if (true === $tiered_data['disabled']) {
			return $amount;
		}

		if (isset($tiered_data['start_tiered_discount']) && 'start_after_date' === $tiered_data['start_tiered_discount']) {
			$start_time = strtotime($tiered_data['start_after_date']);
			if (false === $start_time || $start_time > current_time('timestamp')) {
				return $amount;
			}
		}

		if (!isset($tiered_data['discount_tiers']) || !is_array($tiered_data['discount_tiers']) || count($tiered_data['discount_tiers']) == 0) {
			return $amount;
		}

		$matched_tiers = array_filter($tiered_data['discount_tiers'], function ($current_rule) {
			if (!isset($current_rule['conditions']) || !is_array($current_rule['conditions']) || count($current_rule['conditions']) == 0 || $current_rule['disabled'] || strlen($current_rule['discount']) == 0) {
				return false;
			}

			$match_conditions = array_filter($current_rule['conditions'], function ($condition) {
				return apply_filters('tiered_discount_for_woocommerce/condition_matched', false, $condition);
			});

			if ('match_any' == $current_rule['condition_relationship'] && count($match_conditions) > 0) {
				return true;
			}

			if ('match_all' == $current_rule['condition_relationship'] && count($match_conditions) === count($current_rule['conditions'])) {
				return true;
			}

			return false;
		});

		if (count($matched_tiers) === 0) {
			return $amount;
		}

		$current_tier = current($matched_tiers);
		if (count($matched_tiers) > 1) {
			$tier_priority = !empty($tiered_data['match_discount_tier_priority']) ? $tiered_data['match_discount_tier_priority'] : 'highest_discount';

			usort($matched_tiers, function ($a, $b) {
				return $a['discount'] > $b['discount'] ? 1 : -1;
			});

			if ('lowest_discount' === $tier_priority) {
				$current_tier = reset($matched_tiers);
			} else {
				$current_tier = end($matched_tiers);
			}
		}

		$discount = floatval($current_tier['discount']);
		if ('fixed_discount' === $current_tier['discount_type']) {
			return $discount;
		}

		if ('percentage_discount' === $current_tier['discount_type']) {
			if ($discount <= 0) {
				return 0;
			}

			$subtotal = (float) WC()->cart->get_subtotal();
			return floatval($subtotal * $discount / 100);
		}

		return $amount;
	}

	/**
	 * Cart related condition filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function cart_condition_type($matched, $condition) {
		if (!in_array($condition['type'], array('cart:subtotal', 'cart:total_quantity', 'cart:total_weight'))) {
			return $matched;
		}

		$operator = $condition['operator'];
		$target_amount = floatval($condition['value']);

		if ('cart:subtotal' === $condition['type']) {
			$subtotal = (float) WC()->cart->get_subtotal();

			if ('equal_to' === $operator && $subtotal == $target_amount) {
				return true;
			}

			if ('less_than' === $operator && $subtotal < $target_amount) {
				return true;
			}

			if ('less_than_or_equal' === $operator && $subtotal <= $target_amount) {
				return true;
			}

			if ('greater_than_or_equal' === $operator && $subtotal >= $target_amount) {
				return true;
			}

			if ('greater_than' === $operator && $subtotal > $target_amount) {
				return true;
			}
		}

		if ('cart:total_quantity' === $condition['type']) {
			$quantity = WC()->cart->get_cart_contents_count();

			if ('equal_to' === $operator && $quantity == $target_amount) {
				return true;
			}

			if ('less_than' === $operator && $quantity < $target_amount) {
				return true;
			}

			if ('less_than_or_equal' === $operator && $quantity <= $target_amount) {
				return true;
			}

			if ('greater_than_or_equal' === $operator && $quantity >= $target_amount) {
				return true;
			}

			if ('greater_than' === $operator && $quantity > $target_amount) {
				return true;
			}
		}

		if ('cart:total_weight' === $condition['type']) {
			$weight = WC()->cart->cart_contents_weight;

			if ('equal_to' === $operator && $weight == $target_amount) {
				return true;
			}

			if ('less_than' === $operator && $weight < $target_amount) {
				return true;
			}

			if ('less_than_or_equal' === $operator && $weight <= $target_amount) {
				return true;
			}

			if ('greater_than_or_equal' === $operator && $weight >= $target_amount) {
				return true;
			}

			if ('greater_than' === $operator && $weight > $target_amount) {
				return true;
			}
		}

		return $matched;
	}

	/**
	 * Date related condition filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function date_condition_type($matched, $condition) {
		$operator = $condition['operator'];

		if ('date:weekly_days' === $condition['type']) {
			$weekly_days = isset($condition['weekly_days']) && is_array($condition['weekly_days']) ? $condition['weekly_days'] : array();
			$current_day = strtolower(current_time('l'));

			if ('in_list' == $operator && in_array($current_day, $weekly_days)) {
				return true;
			}

			if ('not_in_list' == $operator && !in_array($current_day, $weekly_days)) {
				return true;
			}
		}

		return $matched;
	}

	/**
	 * Customer related condition filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function customer_condition_type($matched, $condition) {
		$operator = $condition['operator'];

		if ('customer:users' === $condition['type']) {
			$customers = isset($condition['customer_users']) && is_array($condition['customer_users']) ? $condition['customer_users'] : array();
			if ('in_list' === $operator && in_array(get_current_user_id(), $customers)) {
				return true;
			}

			if ('not_in_list' === $operator && !in_array(get_current_user_id(), $customers)) {
				return true;
			}
		}

		if ('customer:logged_in' === $condition['type'] && 'yes' == $condition['logged_in']) {
			return is_user_logged_in();
		}

		if ('customer:logged_in' === $condition['type'] && 'no' == $condition['logged_in']) {
			return !is_user_logged_in();
		}

		return $matched;
	}

	/**
	 * Billing & Shipping condition filter
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function billing_shipping_condition_match($matched, $condition) {
		$operator = $condition['operator'];

		if ('billing:city' === $condition['type']) {
			$cities = $condition['billing_cities'] ?? '';
			$cities = array_filter(array_map('trim', explode(',', strtolower($cities))));

			$customer_city = strtolower(WC()->customer->get_billing_city());
			if ('in_list' === $operator && in_array($customer_city, $cities)) {
				return true;
			}

			if ('not_in_list' === $operator && !in_array($customer_city, $cities)) {
				return true;
			}
		}

		if ('shipping:city' === $condition['type']) {
			$cities = $condition['shipping_cities'] ?? '';
			$cities = array_filter(array_map('trim', explode(',', strtolower($cities))));

			$customer_city = strtolower(WC()->customer->get_shipping_city());

			if ('in_list' === $operator && in_array($customer_city, $cities)) {
				return true;
			}

			if ('not_in_list' === $operator && !in_array($customer_city, $cities)) {
				return true;
			}
		}

		if ('billing:country' === $condition['type'] || 'shipping:country' === $condition['type']) {
			$countries = isset($condition['countries']) && is_array($condition['countries']) ? $condition['countries'] : array();

			$customer_country = WC()->customer->get_shipping_country();
			if ('billing:country' === $condition['type']) {
				$customer_country = WC()->customer->get_billing_country();
			}

			if ('in_list' === $operator && in_array($customer_country, $countries)) {
				return true;
			}

			if ('not_in_list' === $operator && !in_array($customer_country, $countries)) {
				return true;
			}
		}

		return $matched;
	}
}

Main::get_instance();
