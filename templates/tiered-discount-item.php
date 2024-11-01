<?php

if (!defined('ABSPATH')) {
	exit;
}

?>

<div :class="['tiered-discount-for-woocommerce-discount-rule-item', {collapsed: collapsed}]" :data-id="rule.id">
	<header class="tier-heading" @click="collapsed = !collapsed">
		<h4>{{title}}</h4>

		<div class="actions" @click.stop="">
			<label>
				<input class="switch-checkbox" type="checkbox" v-model="disabled">
				<?php esc_html_e('Disable this rule', 'tiered-discount-for-woocommerce'); ?>
			</label>

			<a @click.prevent="duplicate_rule(ruleNo)" href="#" class="btn-duplicate-rule dashicons dashicons-admin-page" title="<?php esc_html_e('Duplicate rule', 'tiered-discount-for-woocommerce'); ?>"></a>

			<div class="move-rule-buttons">
				<a href="#" @click.prevent.stop="move_up()" class="dashicons dashicons-arrow-up-alt2"></a>
				<a href="#" @click.prevent.stop="move_down()" class="dashicons dashicons-arrow-down-alt2"></a>
			</div>
			<a href="#" class="btn-remove dashicons dashicons-no-alt" @click.prevent="delete_rule()"></a>
		</div>
	</header>

	<table class="table-discount-rule-of-coupon" v-show="!collapsed">
		<tr>
			<th class="vcenter"><?php esc_html_e('Title', 'tiered-discount-for-woocommerce'); ?></th>
			<td>
				<input class="tiered-discount-input" v-model="title" type="text">
			</td>
		</tr>

		<tr>
			<th><?php esc_html_e('Private note', 'tiered-discount-for-woocommerce'); ?></th>
			<td>
				<textarea class="tiered-discount-input" rows="2" v-model="private_note"></textarea>
			</td>
		</tr>

		<tr>
			<th class="vcenter"><?php esc_html_e('Discount type', 'tiered-discount-for-woocommerce'); ?></th>
			<td>
				<select v-model="discount_type">
					<option value="fixed_discount"><?php esc_html_e('Fixed amount', 'tiered-discount-for-woocommerce'); ?></option>
					<option value="percentage_discount"><?php esc_html_e('Percentage', 'tiered-discount-for-woocommerce'); ?></option>
				</select>
			</td>
		</tr>

		<tr>
			<th class="vcenter"><?php esc_html_e('Discount', 'tiered-discount-for-woocommerce'); ?></th>
			<td>
				<input class="tiered-discount-amount" type="number" v-model="discount">
				<p class="field-note" v-if="is_free_shipping"><?php esc_html_e('You have checked "allow free shipping" for this coupon. The discount amount will not apply to the cart.', 'tiered-discount-for-woocommerce'); ?></p>
			</td>
		</tr>

		<tr>
			<th :class="{vcenter: conditions.length === 0}">
				<?php esc_html_e('Conditions', 'tiered-discount-for-woocommerce'); ?>
				<div v-if="conditions.length > 0" class="field-note">
					<?php
					$condition_note = sprintf(
						/* translators: %s link of contact page */
						esc_html__('If you don\'t see the condition you want within the list, please get in touch with us %1$shere%2$s.', 'tiered-discount-for-woocommerce'),
						'<a target="_blank" href="https://codiepress.com/contact/">',
						'</a>'
					);

					echo wp_kses($condition_note, array('a' => array('href' => true, 'target' => true)));
					?>
				</div>
			</th>
			<td>

				<a class="btn-large-border" v-if="conditions.length === 0" href="#" @click.prevent="conditions.push({})">
					<?php esc_html_e('Add a condition', 'tiered-discount-for-woocommerce'); ?>
				</a>

				<template v-else>
					<discount-condition v-for="(item, number) in conditions" :key="item.id" rule-no="ruleNo" :condition="item" :number="number" @delete="delete_condition(number)"></discount-condition>
					<a class="button btn-add-condition" href="#" @click.prevent="conditions.push({})"><?php esc_html_e('Add new condition', 'tiered-discount-for-woocommerce'); ?></a>
				</template>
			</td>
		</tr>

		<tr v-if="conditions.length > 1">
			<th class="vcenter"><?php esc_html_e('Conditions Relationship', 'tiered-discount-for-woocommerce'); ?></th>
			<td>
				<div class="condition-relationship-options">
					<label>
						<input type="radio" value="match_all" v-model="condition_relationship">
						<?php esc_html_e('Match All', 'tiered-discount-for-woocommerce'); ?>
					</label>

					<label>
						<input type="radio" value="match_any" v-model="condition_relationship">
						<?php esc_html_e('Match Any', 'tiered-discount-for-woocommerce'); ?>
					</label>
				</div>
			</td>
		</tr>
	</table>

</div>