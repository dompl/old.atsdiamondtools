<?php
/**
 * This file belongs to the YIT Plugin Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


$general_settings = array(

	'general' => array(
		array(
			'name' => __( 'General Settings', 'yith-woocommerce-eu-vat' ),
			'type' => 'title',
			'desc' => '',
		),
		array(
			'name'    => __( 'Forbid EU customer checkout', 'yith-woocommerce-eu-vat' ),
			'desc'    => __( 'If the cart of an European customer contains digital goods, the checkout will not be allowed.', 'yith-woocommerce-eu-vat' ),
			'id'      => 'ywev_forbid_checkout',
			'std'     => 'no',
			'default' => 'no',
			'type'    => 'checkbox'
		),
        array(
            'name'    => __( 'EU VAT field label', 'yith-woocommerce-eu-vat' ),
            'desc'    => __( 'Set the text to use as label for the field EU VAT in checkout page', 'yith-woocommerce-eu-vat' ),
            'id'      => 'ywev_eu_vat_field_label',
            'std'     => __ ( 'VAT number', 'yith-woocommerce-eu-vat' ),
            'default' => __ ( 'VAT number', 'yith-woocommerce-eu-vat' ),
            'type'    => 'text'
        ),
        array(
            'name'    => __( 'EU VAT field placeholder', 'yith-woocommerce-eu-vat' ),
            'desc'    => __( 'Set the text to use as placeholder for the field EU VAT in checkout page', 'yith-woocommerce-eu-vat' ),
            'id'      => 'ywev_eu_vat_field_placeholder',
            'std'     => __ ( 'Enter your VAT number', 'yith-woocommerce-eu-vat' ),
            'default' => __ ( 'Enter your VAT number', 'yith-woocommerce-eu-vat' ),
            'css'     => 'width:80%; height: 90px;',
            'type'    => 'textarea'
        ),
        array(
            'name'    => __( 'EU VAT field description', 'yith-woocommerce-eu-vat' ),
            'desc'    => __( 'Set the text to use as description for the field EU VAT in checkout page', 'yith-woocommerce-eu-vat' ),
            'id'      => 'ywev_eu_vat_field_description',
            'std'     => __ ( 'European companies with valid EU VAT number will be exempt of VAT.', 'yith-woocommerce-eu-vat' ),
            'default' => __ ( 'European companies with valid EU VAT number will be exempt of VAT.', 'yith-woocommerce-eu-vat' ),
            'css'     => 'width:80%; height: 90px;',
            'type'    => 'textarea'
        ),
		array(
			'name'    => __( 'EU customer warning', 'yith-woocommerce-eu-vat' ),
			'desc'    => __( 'If "Forbid EU customer checkout" is selected, you can choose to show a warning message in cart page to remind customers of digital goods in the cart.', 'yith-woocommerce-eu-vat' ),
			'id'      => 'ywev_show_forbid_warning',
			'std'     => 'no',
			'default' => 'no',
			'type'    => 'checkbox'
		),
		array(
			'name'    => __( 'EU VAT warning message', 'yith-woocommerce-eu-vat' ),
			'id'      => 'ywev_forbid_warning_message',
			'std'     => __( 'For EU area customers.<br>Due to EU VAT law terms, some products may be not purchasable.', 'yith-woocommerce-eu-vat' ),
			'default' => __( 'For EU area customers.<br>Due to EU VAT law terms, some products may be not purchasable.', 'yith-woocommerce-eu-vat' ),
			'css'     => 'width:80%; height: 90px;',
			'type'    => 'textarea',
		),
		array(
			'name'    => __( 'EU VAT error message', 'yith-woocommerce-eu-vat' ),
			'id'      => 'ywev_forbid_error_message',
			'default' => __( "This order can't be accepted due to EU VAT laws. This shop doesn't allow EU area customers to purchase.", 'yith-woocommerce-eu-vat' ),
			'css'     => 'width:80%; height: 90px;',
			'type'    => 'textarea',
		),
		array(
			'id'      => 'ywev_custom_base_location',
			'type'    => 'text',
			'name'    => __( 'Custom base locations', 'yith-woocommerce-eu-vat' ),
			'desc'    => __( "Add comma separated country codes that will be used as base location(s) instead of the base location(s) specified in the WooCommerce settings.", 'yith-woocommerce-eu-vat' ),
			'default' => '',
			'std'     => '',
		),
		array(
			'id'      => 'ywev_mandatory_vat_number',
			'type'    => 'checkbox',
			'name'    => __( 'Mandatory VAT number', 'yith-woocommerce-eu-vat' ),
			'desc'    => __( "The VAT number is a mandatory field.", 'yith-woocommerce-eu-vat' ),
			'default' => 'no',
			'std'     => 'no',
		),
		array(
			'id'      => 'ywev_enable_vat_number_same_country',
			'type'    => 'checkbox',
			'name'    => __( 'Enable VAT number in same country', 'yith-woocommerce-eu-vat' ),
			'desc'    => __( "Show the VAT number field to all EU customers even the ones coming from the same country as the shop base location.", 'yith-woocommerce-eu-vat' ),
			'default' => 'no',
			'std'     => 'no',
		),
        array(
            'id'      => 'ywev_disable_vat_exception_same_country',
            'type'    => 'checkbox',
            'name'    => __( 'Disable VAT exception in same country', 'yith-woocommerce-eu-vat' ),
            'desc'    => __( "Disable VAT exception in calculate totals in same country", 'yith-woocommerce-eu-vat' ),
            'default' => 'no',
            'std'     => 'no',
        ),
		array(
			'id'      => 'ywev_apply_to_physical',
			'type'    => 'checkbox',
			'name'    => __( 'Apply to physical products', 'yith-woocommerce-eu-vat' ),
			'desc'    => __( "Choose if you want to apply the VAT exemption on physical products too.", 'yith-woocommerce-eu-vat' ),
			'default' => 'no',
			'std'     => 'no',
		),
		array(
			'id'      => 'ywev_country_confirmation_message',
			'type'    => 'textarea',
			'name'    => __( 'Country confirmation message', 'yith-woocommerce-eu-vat' ),
			'desc'    => __( 'Configure here the message to show to EU customers when the IP address and the billing country do not match', 'yith-woocommerce-eu-vat' ),
			'default' => __( 'Your IP Address does not match the billing country. In order to fit European VAT laws, you should confirm your billing country using the checkbox below.', 'yith-woocommerce-eu-vat' ),
			'css'     => 'width:80%; height: 90px;',
		),
		array(
			'id'      => 'ywev_missing_vat_number_message',
			'type'    => 'textarea',
			'name'    => __( 'Mandatory VAT number message', 'yith-woocommerce-eu-vat' ),
			'desc'    => __( 'Set the message to show when the mandatory VAT number is not provided', 'yith-woocommerce-eu-vat' ),
			'default' => __( 'The VAT number is mandatory, please fill the VAT number field.', 'yith-woocommerce-eu-vat' ),
			'css'     => 'width:80%; height: 90px;',
		),
		array(
			'type' => 'sectionend',
		)
	)
);


return apply_filters( 'yith_ywev_tab_options', $general_settings );

