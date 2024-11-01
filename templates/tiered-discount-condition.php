<?php

if (!defined('ABSPATH')) {
	exit;
}

use Tiered_Discount_For_WooCommerce\Utils;

$condition_groups = Utils::get_condition_groups();
?>

<table class="tiered-discount-for-woocommerce-condition-item">
	<tr>
		<td class="condition-type-field-column" style="vertical-align: top;">
			<select v-model="type">
				<?php
				foreach ($condition_groups as $group_key => $group_label) {
					$conditions = Utils::get_conditions_by_group($group_key);
					if (count($conditions) == 0) {
						continue;
					}

					echo '<optgroup label="' . esc_attr($group_label) . '">';
					foreach ($conditions as $key => $condition) {
						echo '<option value="' . esc_attr($key) . '">' . esc_html($condition['label']) . ' </option>';
					}
					echo '</optgroup>';
				}
				?>
			</select>
		</td>

		<td class="condition-type-fields-column">
			<div class="condition-type-fields-wrapper">
				<template v-if="['cart:subtotal', 'cart:total_quantity', 'cart:total_weight'].includes(type)">
					<select v-model="operator">
						<?php Utils::get_operators_options(array('equal_to', 'less_than', 'less_than_or_equal', 'greater_than_or_equal', 'greater_than')); ?>
					</select>

					<input type="number" v-model="value" placeholder="<?php echo '0.00'; ?>">
				</template>

				<template v-if="type == 'customer:users'">
					<select v-model="operator">
						<?php Utils::get_operators_options(array('in_list', 'not_in_list')); ?>
					</select>

					<div class="input-field-loading" v-if="loading_customers"></div>
					<select ref="select2_ajax" multiple v-else data-placeholder="<?php esc_html_e('Select users', 'tiered-discount-for-woocommerce'); ?>" data-model="customer_users" data-type="users">
						<option v-for="user in get_ui_data_items('hold_customers')" :value="user.id" :selected="customer_users.includes(user.id.toString())">{{user.name}}</option>
					</select>
				</template>

				<template v-if="type == 'customer:logged_in'">
					<select v-model="logged_in">
						<option value="yes"><?php esc_html_e('Yes', 'tiered-discount-for-woocommerce'); ?></option>
						<option value="no"><?php esc_html_e('No', 'tiered-discount-for-woocommerce'); ?></option>
					</select>
				</template>

				<?php do_action('tiered_discount_for_woocommerce/condition_templates'); ?>

				<a href="#" class="btn-condition-delete dashicons dashicons-no-alt" @click.prevent="delete_item()"></a>
			</div>
		</td>
	</tr>
</table>