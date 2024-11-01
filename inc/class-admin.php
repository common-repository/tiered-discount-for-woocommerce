<?php

namespace Tiered_Discount_For_WooCommerce;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Admin class of the plugin
 */
final class Admin {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_scripts();
		add_action('save_post', array($this, 'save_coupon'));
		add_action('add_meta_boxes', array($this, 'add_meta_box'));
		add_action('admin_footer', array($this, 'add_component'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

		add_action('wp_ajax_tiered_discount_for_woocommerce/import_coupon_data', array($this, 'import_coupon_data'));
		add_action('wp_ajax_tiered_discount_for_woocommerce/get_dropdown_data', array($this, 'get_dropdown_data'));
	}

	/**
	 * Register styles and scripts
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function register_scripts() {
		if (defined('TIERED_DISCOUNT_FOR_WOOCOMMERCE_DEV')) {
			wp_register_script('tiered-discount-for-woocommerce-vue', TIERED_DISCOUNT_FOR_WOOCOMMERCE_URI . 'assets/vue.js', [], '3.4.21', true);
		} else {
			wp_register_script('tiered-discount-for-woocommerce-vue', TIERED_DISCOUNT_FOR_WOOCOMMERCE_URI . 'assets/vue.min.js', [], '3.4.21', true);
		}
	}

	/**
	 * Enqueue script on backend
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		if (get_current_screen()->id !== 'shop_coupon') {
			return;
		}

		$wc_countries = new \WC_Countries();

		wp_register_style('select2', TIERED_DISCOUNT_FOR_WOOCOMMERCE_URI . 'assets/select2.min.css');
		wp_enqueue_style('tiered-discount-for-woocommerce', TIERED_DISCOUNT_FOR_WOOCOMMERCE_URI . 'assets/admin.min.css', ['select2'], TIERED_DISCOUNT_FOR_WOOCOMMERCE_VERSION);

		do_action('tiered_discount_for_woocommerce/admin_enqueue_scripts');
		wp_enqueue_script('tiered-discount-for-woocommerce', TIERED_DISCOUNT_FOR_WOOCOMMERCE_URI . 'assets/admin.min.js', ['jquery', 'tiered-discount-for-woocommerce-vue', 'select2'], TIERED_DISCOUNT_FOR_WOOCOMMERCE_VERSION, true);
		wp_localize_script('tiered-discount-for-woocommerce', 'tiered_discount_for_woocommerce_admin', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'countries' => $wc_countries->get_countries(),
			'nonce' => wp_create_nonce('_nonce_tiered_discount_for_woocommerce/get_dropdown_data'),
			'i10n' => array(
				'copy_text' => __('Copy', 'tiered-discount-for-woocommerce'),
				'discount_tier' => __('Discount Tier', 'tiered-discount-for-woocommerce'),
				'delete_discount_tier_warning' => __('Do you want to delete this rule?', 'tiered-discount-for-woocommerce'),
				'delete_condition_warning' => __('Do you want to delete this condition?', 'tiered-discount-for-woocommerce')
			)
		));
	}

	/**
	 * Add meta boxes for coupon post type
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_meta_box($post_type) {
		if ('shop_coupon' !== $post_type) {
			return;
		}

		add_meta_box('tiered_discount_for_woocommerce_metabox', __('Tiered Discount Settings', 'tiered-discount-for-woocommerce'), array($this, 'render_meta_box'), 'shop_coupon', 'advanced', 'high');
	}

	/**
	 * Render meta box
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function render_meta_box($post) {
		$coupon_data = Utils::get_coupon_data($post->ID); ?>
		<div id="tiered-discount-for-woocommerce" data-settings="<?php echo esc_attr(wp_json_encode($coupon_data)); ?>">
			<?php wp_nonce_field('_nonce_tiered_discount_for_woocommerce_meta_box', '_nonce_tiered_discount_for_woocommerce'); ?>
			<input name="tiered_discount_for_woocommerce_settings" type="hidden" v-model="get_json_data">

			<div class="rule-empty" v-if="discount_tiers.length === 0">
				<div class="tiered-discount-import-rule">
					<select class="select2-import-tiered-discount" ref="import_tiered_discount_rule" data-placeholder="<?php echo esc_attr_e('Select a coupon', 'tiered-discount-for-woocommerce'); ?>"></select>
					<button @click.prevent="import_coupon_data()" class="button" :disabled="!has_import_rule_id"><?php esc_html_e('Import', 'tiered-discount-for-woocommerce'); ?></button>
					<input ref="import_nonce" type="hidden" value="<?php echo esc_attr(wp_create_nonce('_nonce_tiered_discount_for_woocommerce/import_coupon_data')); ?>">
				</div>
				<div class="tiered-discount-or-hr"><?php esc_html_e('or', 'tiered-discount-for-woocommerce'); ?></div>
				<a href="#" @click.prevent="add_new_discount_tier()" class="button btn-large-border"><?php esc_html_e('Add a discount rule', 'tiered-discount-for-woocommerce'); ?></a>
			</div>
			<template v-else>
				<table class="table-discount-rule-of-coupon table-discount-rule-of-coupon-settings">
					<tr>
						<th><?php esc_html_e('Discount tier', 'tiered-discount-for-woocommerce'); ?></th>
						<td>
							<label>
								<input class="switch-checkbox" type="checkbox" v-model="disabled">
								<?php esc_html_e('Disable', 'tiered-discount-for-woocommerce'); ?>
							</label>
						</td>
					</tr>

					<tr>
						<th class="vcenter"><?php esc_html_e('Start discount', 'tiered-discount-for-woocommerce'); ?></th>
						<td>
							<select v-model="start_tiered_discount">
								<option value="immediately"><?php esc_html_e('Immediately', 'tiered-discount-for-woocommerce'); ?></option>
								<option value="start_after_date"><?php esc_html_e('After', 'tiered-discount-for-woocommerce'); ?></option>
							</select>

							<input type="datetime-local" v-if="start_tiered_discount == 'start_after_date'" v-model="start_after_date">
						</td>
					</tr>

					<tr>
						<th class="vcenter">
							<?php esc_html_e('Discount rule priority', 'tiered-discount-for-woocommerce'); ?>

							<div class="field-note">
								<?php esc_html_e('The selected priority will be applied, If match more than one tier.', 'tiered-discount-for-woocommerce'); ?>
							</div>

						</th>
						<td>
							<select v-model="match_discount_tier_priority">
								<option value="highest_discount"><?php esc_html_e('Highest discount', 'tiered-discount-for-woocommerce'); ?></option>
								<option value="lowest_discount"><?php esc_html_e('Lowest discount', 'tiered-discount-for-woocommerce'); ?></option>
							</select>
						</td>
					</tr>
				</table>

				<div :class="['tiered-discount-rule-wrapper', {'tiered-discount-disabled': (disabled || free_shipping)}]">
					<discount-tier-item v-for="(rule, index) in discount_tiers" :key="rule.id" :rule="rule" :rule-no="index"></discount-tier-item>

					<div class="tiered-discount-footer">
						<button href="#" @click.prevent="add_new_discount_tier()" class="button btn-add-new-rule">
							<?php esc_html_e('Add new rule', 'tiered-discount-for-woocommerce'); ?>
							<span class="dashicons dashicons-lock" v-if="discount_tiers.length >= 3 && !has_pro()"></span>
						</button>
						<button class="button button-primary button-save-tier"><?php esc_html_e('Save Changes', 'tiered-discount-for-woocommerce'); ?></button>
					</div>
				</div>
			</template>

			<div id="tiered-discount-locked-modal" v-if="show_locked_modal">
				<div class="modal-body">
					<a @click.prevent="show_locked_modal = false" href="#" class="btn-modal-close dashicons dashicons-no-alt"></a>

					<span class="modal-icon dashicons dashicons-lock"></span>

					<div>
						<?php
						$text = sprintf(
							/* translators: %s for link */
							esc_html__('For adding more discount tire, please get a pro version from %s.', 'tiered-discount-for-woocommerce'),
							'<a target="_blank" href="https://codiepress.com/plugins/tiered-discount-for-woocommerce-pro/">' . esc_html__('here', 'tiered-discount-for-woocommerce') . '</a>'
						);

						echo wp_kses($text, array('a' => array('href' => true, 'target' => true)));
						?>
					</div>


					<div class="modal-footer">
						<a @click.prevent="show_locked_modal = false" class="button" href="#"><?php esc_html_e('Back', 'tiered-discount-for-woocommerce'); ?></a>
						<a @click="show_locked_modal = false" class="button button-get-pro" href="https://codiepress.com/plugins/tiered-discount-for-woocommerce-pro/" target="_blank"><?php esc_html_e('Get Pro', 'tiered-discount-for-woocommerce'); ?></a>
					</div>
				</div>
			</div>
		</div>
<?php
	}

	/**
	 * Save meta box content.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID
	 */
	public function save_coupon($post_id) {
		if (!isset($_POST['_nonce_tiered_discount_for_woocommerce'])) {
			return;
		}

		$nonce = sanitize_text_field($_POST['_nonce_tiered_discount_for_woocommerce']);
		if (!wp_verify_nonce($nonce, '_nonce_tiered_discount_for_woocommerce_meta_box')) {
			return;
		}

		if (!isset($_POST['tiered_discount_for_woocommerce_settings'])) {
			return;
		}

		$tiered_discount = stripslashes(sanitize_text_field($_POST['tiered_discount_for_woocommerce_settings']));
		update_post_meta($post_id, 'tiered_discount_for_woocommerce_settings', $tiered_discount);
	}

	/**
	 * Add vuejs component
	 */

	public function add_component() {
		if (get_current_screen()->id !== 'shop_coupon') {
			return;
		}

		echo '<template id="component-tiered-discount">';
		include_once TIERED_DISCOUNT_FOR_WOOCOMMERCE_PATH . '/templates/tiered-discount-item.php';
		echo '</template>';

		echo '<template id="component-tiered-discount-condition">';
		include_once TIERED_DISCOUNT_FOR_WOOCOMMERCE_PATH . '/templates/tiered-discount-condition.php';
		echo '</template>';
	}

	/**
	 * Import data content by ID
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function import_coupon_data() {
		if (!isset($_POST['security'])) {
			wp_send_json_error(array(
				'error' => __('Security Missing.', 'tiered-discount-for-woocommerce')
			));
		}

		check_ajax_referer('_nonce_tiered_discount_for_woocommerce/import_coupon_data', 'security');

		if (empty($_POST['coupon_id'])) {
			wp_send_json_error(array(
				'error' => __('No coupon ID found.', 'tiered-discount-for-woocommerce')
			));
		}

		$coupon_data = Utils::get_coupon_data(absint($_POST['coupon_id']));
		wp_send_json_success($coupon_data);
	}

	/**
	 * Get users by search
	 * 
	 * @since 1.0.0
	 * @return void
	 */

	public function get_dropdown_data() {
		if (!isset($_POST['security'])) {
			wp_send_json_error(array(
				'error' => __('Security Missing.', 'tiered-discount-for-woocommerce')
			));
		}

		check_ajax_referer('_nonce_tiered_discount_for_woocommerce/get_dropdown_data', 'security');

		$results = array();
		$search_args = array();

		$query_type = !empty($_POST['type']) ? sanitize_text_field($_POST['type']) : false;
		$search_term = !empty($_POST['term']) ? sanitize_text_field($_POST['term'])  : '';

		if ('users' == $query_type) {
			if (!empty($search_term)) {
				$search_args['search'] = $search_term;
			}

			if (isset($_POST['user_ids']) && is_array($_POST['user_ids'])) {
				$user_ids = array_map('absint', $_POST['user_ids']);
				$search_args['include'] = $user_ids;
			}

			$get_users = get_users($search_args);
			$results = array_map(function ($user) {
				return array('id' => $user->id, 'name' => $user->display_name);
			}, $get_users);
		}

		if ('coupon_rules' == $query_type) {
			$coupons = get_posts(array(
				's' => $search_term,
				'post_type' => 'shop_coupon',
				'posts_per_page' => -1,
				'meta_query' => array(
					array(
						'key' => 'tiered_discount_for_woocommerce_settings',
						'compare' => 'EXISTS',
					)
				)
			));

			$results = array_map(function ($coupon) {
				$title[] = esc_html__('ID', 'tiered-discount-for-woocommerce') . ': ' . $coupon->ID;
				return array('id' => $coupon->ID, 'name' => sprintf('%s (%d)', $coupon->post_title, $coupon->ID));
			}, $coupons);
		}

		if ('states' == $query_type) {
			if ( empty($_POST['country'])) {
				wp_send_json_error( array(
					'error' => esc_html__('Country Missing', 'tiered-discount-for-woocommerce')
				));
			}

			$wc_countries = new \WC_Countries();
			$states = $wc_countries->get_states(sanitize_text_field( $_POST['country'] ));

			if (!empty($search_term)) {
				$states = array_filter($states, function ($state) use ($search_term) {
					return stripos($state, $search_term) !== false;
				});
			}

			if ( !is_array($states)) {
				$states = [];
			}

			$results = array_map(function ($state, $code) {
				return array('id' => $code, 'name' => html_entity_decode($state));
			}, $states, array_keys($states));
		}

		//error_log(print_r($results, true));
		wp_send_json_success($results);
	}
}
