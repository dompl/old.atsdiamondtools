<?php
/**
 * YITH WooCommerce EU VAT
 *
 * Show country confirmation field
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<p class="form-row ywev-country-confirmation terms">
	<input type="checkbox" name="ywev-country-confirmation" id="ywev-country-confirmation">
	<label for="ywev-country-confirmation" class="checkbox"><?php printf(__("I confirm that my base location is %s", 'yith-woocommerce-eu-vat'), $country); ?><span class="required">*</span></label>
</p>