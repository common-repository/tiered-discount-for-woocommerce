<?php

namespace Tiered_Discount_For_WooCommerce;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Condition class for template of options
 */
final class Condition_Templates {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action('tiered_discount_for_woocommerce/condition_templates', array($this, 'weekly_days'));
		add_action('tiered_discount_for_woocommerce/condition_templates', array($this, 'between_dates'));
		add_action('tiered_discount_for_woocommerce/condition_templates', array($this, 'between_times'));

		add_action('tiered_discount_for_woocommerce/condition_templates', array($this, 'add_billing_city_template'));
		add_action('tiered_discount_for_woocommerce/condition_templates', array($this, 'add_billing_zipcode_template'));
		add_action('tiered_discount_for_woocommerce/condition_templates', array($this, 'add_billing_state_template'));

		add_action('tiered_discount_for_woocommerce/condition_templates', array($this, 'add_shipping_city_template'));
		add_action('tiered_discount_for_woocommerce/condition_templates', array($this, 'add_shipping_zipcode_template'));
		add_action('tiered_discount_for_woocommerce/condition_templates', array($this, 'add_shipping_state_template'));

		add_action('tiered_discount_for_woocommerce/condition_templates', array($this, 'add_billing_country_template'));
		add_action('tiered_discount_for_woocommerce/condition_templates', array($this, 'add_shipping_country_options'));

		add_action('tiered_discount_for_woocommerce/condition_templates', array($this, 'customer_roles'));
	}

	/**
	 * Add weekly days template of condition
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function weekly_days() { ?>
		<template v-if="type == 'date:weekly_days'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
			</select>

			<select v-model="weekly_days" data-model="weekly_days" ref="select2_dropdown" data-placeholder="<?php esc_attr_e('Select days', 'tiered-discount-for-woocommerce'); ?>" multiple>
				<option value="sunday"><?php esc_html_e('Sunday', 'tiered-discount-for-woocommerce'); ?></option>
				<option value="monday"><?php esc_html_e('Monday', 'tiered-discount-for-woocommerce'); ?></option>
				<option value="tuesday"><?php esc_html_e('Tuesday', 'tiered-discount-for-woocommerce'); ?></option>
				<option value="wednesday"><?php esc_html_e('Wednesday', 'tiered-discount-for-woocommerce'); ?></option>
				<option value="thursday"><?php esc_html_e('Thursday', 'tiered-discount-for-woocommerce'); ?></option>
				<option value="friday"><?php esc_html_e('Friday', 'tiered-discount-for-woocommerce'); ?></option>
				<option value="saturday"><?php esc_html_e('Saturday', 'tiered-discount-for-woocommerce'); ?></option>
			</select>
		</template>
	<?php
	}

	/**
	 * Add between dates template of condition
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function between_dates() { ?>
		<div class="tiered-discount-for-woocommerce-locked-fields" v-if="type == 'date:between_dates'">
			<input type="datetime-local">
			<input type="datetime-local">

			<?php Utils::field_lock_message(); ?>
		</div>
	<?php
	}

	/**
	 * Add between times template of condition template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function between_times() { ?>
		<div class="tiered-discount-for-woocommerce-locked-fields" v-if="type == 'date:between_times'">
			<input type="time">
			<input type="time">

			<?php Utils::field_lock_message(); ?>
		</div>
	<?php
	}

	/**
	 * Add city template of condition
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_billing_city_template() { ?>
		<template v-if="type == 'billing:city'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
			</select>

			<?php $placeholder = __('Example: Chicago, New York', 'tiered-discount-for-woocommerce'); ?>
			<input style="width: 400px;" type="text" v-model="billing_cities" placeholder="<?php echo esc_attr($placeholder); ?>" title="<?php echo esc_attr($placeholder); ?>">
		</template>
	<?php
	}

	/**
	 * Add zipcode template of billing conditin
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_billing_zipcode_template() { ?>
		<div class="tiered-discount-for-woocommerce-locked-fields" v-if="type == 'billing:zipcode'">
			<select>
				<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
			</select>

			<input style="width: 400px;" type="text" placeholder="<?php esc_html_e('Example: 38632, 21710, 38686', 'tiered-discount-for-woocommerce'); ?>">

			<?php Utils::field_lock_message(); ?>
		</div>
	<?php
	}

	/**
	 * Add state of billing template of condition
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_billing_state_template() { ?>
		<div class="tiered-discount-for-woocommerce-locked-fields" v-if="type == 'billing:state'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
			</select>

			<select ref="select2_dropdown">
				<option value=""><?php esc_html_e('Select a country', 'tiered-discount-for-woocommerce'); ?></option>
				<option v-for="(country, country_code) in get_countries()" :value="country_code">{{country}}</option>
			</select>

			<select ref="select2_ajax" multiple data-placeholder="<?php esc_html_e('Select states', 'tiered-discount-for-woocommerce'); ?>">
				<option value="state1">State one</option>
				<option value="state1">State two</option>
			</select>
			<?php Utils::field_lock_message(); ?>
		</div>
	<?php
	}

	/**
	 * Add city template of condition
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_shipping_city_template() { ?>
		<template v-if="type == 'shipping:city'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
			</select>

			<?php $placeholder = __('Example: Chicago, New York', 'tiered-discount-for-woocommerce'); ?>
			<input style="width: 400px;" type="text" v-model="shipping_cities" placeholder="<?php echo esc_attr($placeholder); ?>" title="<?php echo esc_attr($placeholder); ?>">
		</template>
	<?php
	}

	/**
	 * Add state template of condition
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_shipping_zipcode_template() { ?>
		<div class="tiered-discount-for-woocommerce-locked-fields" v-if="type == 'shipping:zipcode'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
			</select>

			<input style="width: 400px;" type="text" v-model="zipcodes" placeholder="<?php esc_html_e('Example: 38632, 21710, 38686', 'tiered-discount-for-woocommerce'); ?>">

			<?php Utils::field_lock_message(); ?>
		</div>
	<?php
	}

	/**
	 * Add state of shipping template of condition
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_shipping_state_template() { ?>
		<div class="tiered-discount-for-woocommerce-locked-fields" v-if="type == 'shipping:state'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
			</select>

			<select ref="select2_dropdown">
				<option value=""><?php esc_html_e('Select a country', 'tiered-discount-for-woocommerce'); ?></option>
				<option v-for="(country, country_code) in get_countries()" :value="country_code">{{country}}</option>
			</select>

			<select ref="select2_ajax" multiple data-placeholder="<?php esc_html_e('Select states', 'tiered-discount-for-woocommerce'); ?>">
				<option value="state1">State one</option>
				<option value="state1">State two</option>
			</select>
			<?php Utils::field_lock_message(); ?>
		</div>
	<?php
	}

	/**
	 * Add country template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_billing_country_template() { ?>
		<template v-if="type == 'billing:country'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
			</select>

			<select v-model="billing_countries" ref="select2_dropdown" multiple data-model="billing_countries" data-placeholder="<?php esc_attr_e('Select country', 'tiered-discount-for-woocommerce'); ?>">
				<option v-for="(country, country_code) in get_countries()" :value="country_code">{{country}}</option>
			</select>
		</template>
	<?php
	}

	/**
	 * Add country template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_shipping_country_options() { ?>
		<template v-if="type == 'shipping:country'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
			</select>

			<select v-model="shipping_countries" ref="select2_dropdown" multiple data-model="shipping_countries" data-placeholder="<?php esc_attr_e('Select country', 'tiered-discount-for-woocommerce'); ?>">
				<option v-for="(country, country_code) in get_countries()" :value="country_code">{{country}}</option>
			</select>
		</template>
	<?php
	}

	/**
	 * Add customer roles template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function customer_roles() { ?>
		<div class="tiered-discount-for-woocommerce-locked-fields" v-if="type == 'customer:roles'">
			<select>
				<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
			</select>

			<select>
				<option value="test">Administrator</option>
			</select>

			<?php Utils::field_lock_message(); ?>
		</div>
<?php
	}
}
