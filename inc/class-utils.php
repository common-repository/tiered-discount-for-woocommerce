<?php

namespace Tiered_Discount_For_WooCommerce;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Utilities class 
 */
class Utils {

	/**
	 * Check if enabled woocommerce coupon
	 * 
	 * @since 1.0.1
	 * @return boolean
	 */
	public static function enabled_woocommerce_coupon() {
		return get_option('woocommerce_enable_coupons') === 'yes';
	}

	/**
	 * Check if pro version installed
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function has_pro_installed() {
		return file_exists(WP_PLUGIN_DIR . '/tiered-discount-for-woocommerce-pro/tiered-discount-for-woocommerce-pro.php');
	}

	/**
	 * Get condition operators
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_operators($operators = array()) {
		$supported_operators = array(
			'equal_to' => __('Equal To ( = )', 'tiered-discount-for-woocommerce'),
			'less_than' => __('Less than ( < )', 'tiered-discount-for-woocommerce'),
			'less_than_or_equal' => __('Less than or equal ( <= )', 'tiered-discount-for-woocommerce'),
			'greater_than_or_equal' => __('Greater than or equal ( >= )', 'tiered-discount-for-woocommerce'),
			'greater_than' => __('Greater than ( > )', 'tiered-discount-for-woocommerce'),
			'in_list' => __('In list', 'tiered-discount-for-woocommerce'),
			'not_in_list' => __('Not in list', 'tiered-discount-for-woocommerce'),
		);

		$return_operators = [];
		while ($key = current($operators)) {
			if (isset($supported_operators[$key])) {
				$return_operators[$key] = $supported_operators[$key];
			}

			next($operators);
		}

		return $return_operators;
	}

	/**
	 * Get condition operators dropdown
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_operators_options($args = array()) {
		$operators = self::get_operators($args);

		$options = array_map(function ($label, $key) {
			return sprintf('<option value="%s">%s</option>', $key, $label);
		}, $operators, array_keys($operators));

		echo wp_kses(implode('', $options), array(
			'option' => array(
				'value' => true
			)
		));
	}

	/**
	 * Get coupon data by a coupon id
	 * 
	 * @since 1.0.0
	 * @param int coupon_id
	 */
	public static function get_coupon_data($coupon_id) {
		$coupon_data = wp_parse_args(json_decode(get_post_meta($coupon_id, 'tiered_discount_for_woocommerce_settings', true), true), array(
			'rules' => array(),
			'disabled' => false,
			'start_tiered_discount' => 'immediately',
			'start_after_date' => '',
		));

		return $coupon_data;
	}

	/**
	 * Group of condition of tier discount
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_condition_groups() {
		return apply_filters('tiered_discount_for_woocommerce/condition_groups', array(
			'cart' => __('Cart', 'tiered-discount-for-woocommerce'),
			'date' => __('Date', 'tiered-discount-for-woocommerce'),
			'billing' => __('Billing', 'tiered-discount-for-woocommerce'),
			'shipping' => __('Shipping', 'tiered-discount-for-woocommerce'),
			'customer' => __('Customer', 'tiered-discount-for-woocommerce')
		));
	}

	/**
	 * Get condition item of groups
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_all_conditions() {
		return apply_filters('tiered_discount_for_woocommerce/condition_groups', array(
			'cart:subtotal' => array(
				'group' => 'cart',
				'priority' => 10,
				'label' => __('Subtotal', 'tiered-discount-for-woocommerce'),
			),
			'cart:total_quantity' => array(
				'group' => 'cart',
				'priority' => 15,
				'label' => __('Total quantity', 'tiered-discount-for-woocommerce'),
			),
			'cart:total_weight' => array(
				'group' => 'cart',
				'priority' => 20,
				'label' => __('Total weight', 'tiered-discount-for-woocommerce'),
			),
			'date:weekly_days' => array(
				'group' => 'date',
				'priority' => 10,
				'label' => __('Weekly Days', 'tiered-discount-for-woocommerce'),
			),
			'date:between_dates' => array(
				'group' => 'date',
				'priority' => 15,
				'is_pro' => true,
				'label' => __('Between Dates', 'tiered-discount-for-woocommerce'),
			),
			'date:between_times' => array(
				'group' => 'date',
				'priority' => 20,
				'label' => __('Between Times', 'tiered-discount-for-woocommerce'),
			),

			'billing:city' => array(
				'group' => 'billing',
				'priority' => 10,
				'label' => __('City', 'tiered-discount-for-woocommerce'),
			),
			'billing:zipcode' => array(
				'group' => 'billing',
				'priority' => 20,
				'label' => __('Zip code', 'tiered-discount-for-woocommerce'),
			),
			'billing:state' => array(
				'group' => 'billing',
				'priority' => 25,
				'label' => __('State', 'tiered-discount-for-woocommerce'),
			),
			'billing:country' => array(
				'group' => 'billing',
				'priority' => 30,
				'label' => __('Country', 'tiered-discount-for-woocommerce'),
			),

			'shipping:city' => array(
				'group' => 'shipping',
				'priority' => 10,
				'label' => __('City', 'tiered-discount-for-woocommerce'),
			),
			'shipping:zipcode' => array(
				'group' => 'shipping',
				'priority' => 15,
				'label' => __('Zip code', 'tiered-discount-for-woocommerce'),
			),
			'shipping:state' => array(
				'group' => 'shipping',
				'priority' => 20,
				'label' => __('State', 'tiered-discount-for-woocommerce'),
			),
			'shipping:country' => array(
				'group' => 'shipping',
				'priority' => 25,
				'label' => __('Country', 'tiered-discount-for-woocommerce'),
			),

			'customer:users' => array(
				'group' => 'customer',
				'priority' => 10,
				'label' => __('Users', 'tiered-discount-for-woocommerce'),
			),
			'customer:roles' => array(
				'group' => 'customer',
				'priority' => 15,
				'label' => __('Roles', 'tiered-discount-for-woocommerce'),
			),
			'customer:logged_in' => array(
				'group' => 'customer',
				'priority' => 20,
				'label' => __('Logged In', 'tiered-discount-for-woocommerce'),
			),
		));
	}

	/**
	 * Get conditions of group
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_conditions_by_group($group) {
		$all_conditions = self::get_all_conditions();

		$group_conditions = [];

		foreach ($all_conditions as $key => $condition) {
			if ($group !== $condition['group']) {
				continue;
			}

			$group_conditions[$key] = $condition;
		}

		uasort($group_conditions, function ($a, $b) {
			return $a['priority'] > $b['priority'] ? 1 : -1;
		});

		return $group_conditions;
	}

	/**
	 * Free lock message
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public static function field_lock_message() {
		if (self::has_pro_installed()) {
			if (class_exists('\Tiered_Discount_For_WooCommerce_Pro\Upgrade')) {
				if (!\Tiered_Discount_For_WooCommerce_Pro\Upgrade::license_activated()) {
					echo '<div class="locked-message locked-message-activate-license">';
					$message = sprintf(
						/* translators: %1$s: Link open, %2$s: Link close */
						esc_html__('Please activate your license on the %1$ssettings page%2$s for unlock this feature.', 'tiered-discount-for-woocommerce'),
						'<a href="' . esc_url(menu_page_url('tiered-discount-for-woocommerce-settings', false)) . '">',
						'</a>'
					);
					echo wp_kses($message, array('a' => array('href' => true,  'target' => true)));
					echo '</div>';
				}
			} else {
				echo '<div class="locked-message">';
				esc_html_e('Please activate the Tiered Discount for WooCommerce Pro plugin.', 'tiered-discount-for-woocommerce');
				echo '</div>';
			}
		} else {
			echo '<div class="locked-message">Get the <a target="_blank" href="https://codiepress.com/plugins/tiered-discount-for-woocommerce-pro/">pro version</a> for unlock this feature.</div>';
		}
	}
}
