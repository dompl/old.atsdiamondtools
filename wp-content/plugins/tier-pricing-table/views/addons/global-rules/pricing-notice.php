<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<h4>
	<?php 
	esc_html_e( 'Please note that the prices and quantity limits you set here could be overridden at the product level.',
		'tier-pricing-table' ); 
	?>
</h4>

<h4>
	<?php esc_html_e( 'Control pricing priorities in the', 'tier-pricing-table' ); ?>
	<a href="#" id="tpt-global-pricing-rule-pricing-notice-settings-link">
		<?php esc_html_e( 'Settings', 'tier-pricing-table' ); ?>
	</a>
</h4>

<script>
	jQuery('#tpt-global-pricing-rule-pricing-notice-settings-link').click(function () {

		jQuery('[data-target=tpt-global-pricing-rule-form-tab-settings]').trigger('click');
		jQuery('[name=_tpt_settings_priority_type]').closest('.tiered-pricing-form-block').addClass('tpt-heartbeat');

		setTimeout(function () {
			jQuery('[name=_tpt_settings_priority_type]').closest('.tiered-pricing-form-block').removeClass('tpt-heartbeat')
		}, 600);
	});
</script>
