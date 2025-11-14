<?php
/**
 * Settings tabs class
 *
 * @package recaptcha-for-woocommerce
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Setting page Class
 *
 * Handles Settings page functionality of plugin
 *
 * @package reCAPTCHA for WooCommerce
 * @since 1.0.0
 */
class WooRecaptcha_Settings_Tabs {

	/**
	 * Add plugin settings
	 *
	 * Handles to add plugin settings in Min/Max Quantites Settings Tab
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.0
	 */
	public function woorecaptcha_get_settings() {

		$languages = array(
			''       => esc_html__( 'Auto Detect', 'recaptcha-for-woocommerce' ),
			'ar'     => esc_html__( 'Arabic', 'recaptcha-for-woocommerce' ),
			'bg'     => esc_html__( 'Bulgarian', 'recaptcha-for-woocommerce' ),
			'ca'     => esc_html__( 'Catalan', 'recaptcha-for-woocommerce' ),
			'zh-CN'  => esc_html__( 'Chinese (Simplified)', 'recaptcha-for-woocommerce' ),
			'zh-TW'  => esc_html__( 'Chinese (Traditional)', 'recaptcha-for-woocommerce' ),
			'hr'     => esc_html__( 'Croatian', 'recaptcha-for-woocommerce' ),
			'cs'     => esc_html__( 'Czech', 'recaptcha-for-woocommerce' ),
			'da'     => esc_html__( 'Danish', 'recaptcha-for-woocommerce' ),
			'nl'     => esc_html__( 'Dutch', 'recaptcha-for-woocommerce' ),
			'en-GB'  => esc_html__( 'English (UK)', 'recaptcha-for-woocommerce' ),
			'en'     => esc_html__( 'English (US)', 'recaptcha-for-woocommerce' ),
			'fil'    => esc_html__( 'Filipino', 'recaptcha-for-woocommerce' ),
			'fi'     => esc_html__( 'Finnish', 'recaptcha-for-woocommerce' ),
			'fr'     => esc_html__( 'French', 'recaptcha-for-woocommerce' ),
			'fr-CA'  => esc_html__( 'French (Canadian)', 'recaptcha-for-woocommerce' ),
			'de'     => esc_html__( 'German', 'recaptcha-for-woocommerce' ),
			'de-AT'  => esc_html__( 'German (Austria)', 'recaptcha-for-woocommerce' ),
			'de-CH'  => esc_html__( 'German (Switzerland)', 'recaptcha-for-woocommerce' ),
			'el'     => esc_html__( 'Greek', 'recaptcha-for-woocommerce' ),
			'iw'     => esc_html__( 'Hebrew', 'recaptcha-for-woocommerce' ),
			'hi'     => esc_html__( 'Hindi', 'recaptcha-for-woocommerce' ),
			'hu'     => esc_html__( 'Hungarain', 'recaptcha-for-woocommerce' ),
			'id'     => esc_html__( 'Indonesian', 'recaptcha-for-woocommerce' ),
			'it'     => esc_html__( 'Italian', 'recaptcha-for-woocommerce' ),
			'ja'     => esc_html__( 'Japanese', 'recaptcha-for-woocommerce' ),
			'ko'     => esc_html__( 'Korean', 'recaptcha-for-woocommerce' ),
			'lv'     => esc_html__( 'Latvian', 'recaptcha-for-woocommerce' ),
			'lt'     => esc_html__( 'Lithuanian', 'recaptcha-for-woocommerce' ),
			'no'     => esc_html__( 'Norwegian', 'recaptcha-for-woocommerce' ),
			'fa'     => esc_html__( 'Persian', 'recaptcha-for-woocommerce' ),
			'pl'     => esc_html__( 'Polish', 'recaptcha-for-woocommerce' ),
			'pt'     => esc_html__( 'Portuguese', 'recaptcha-for-woocommerce' ),
			'pt-BR'  => esc_html__( 'Portuguese (Brazil)', 'recaptcha-for-woocommerce' ),
			'pt-PT'  => esc_html__( 'Portuguese (Portugal)', 'recaptcha-for-woocommerce' ),
			'ro'     => esc_html__( 'Romanian', 'recaptcha-for-woocommerce' ),
			'ru'     => esc_html__( 'Russian', 'recaptcha-for-woocommerce' ),
			'sr'     => esc_html__( 'Serbian', 'recaptcha-for-woocommerce' ),
			'sk'     => esc_html__( 'Slovak', 'recaptcha-for-woocommerce' ),
			'sl'     => esc_html__( 'Slovenian', 'recaptcha-for-woocommerce' ),
			'es'     => esc_html__( 'Spanish', 'recaptcha-for-woocommerce' ),
			'es-419' => esc_html__( 'Spanish (Latin America)', 'recaptcha-for-woocommerce' ),
			'sv'     => esc_html__( 'Swedish', 'recaptcha-for-woocommerce' ),
			'th'     => esc_html__( 'Thai', 'recaptcha-for-woocommerce' ),
			'tr'     => esc_html__( 'Turkish', 'recaptcha-for-woocommerce' ),
			'uk'     => esc_html__( 'Ukrainian', 'recaptcha-for-woocommerce' ),
			'vi'     => esc_html__( 'Vietnamese', 'recaptcha-for-woocommerce' ),
		);
		$languages = apply_filters( 'woo_recaptcha_add_language', $languages );

		// Global Settings for reCAPTCHA.
		$woorecaptcha_settings = array(
			array(
				'name' => esc_html__( 'Google reCAPTCHA Settings', 'recaptcha-for-woocommerce' ),
				'type' => 'title',
				'desc' => '',
				'id'   => 'woo_recaptcha_settings',
			),
			array(
				'name'  => esc_html__( 'Site Key', 'recaptcha-for-woocommerce' ),
				'desc'  => sprintf( esc_html__( 'Used for displaying the reCAPTCHA. Grab it %1$sHere%2$s.', 'recaptcha-for-woocommerce' ), '<a target="_blank" href="https://www.google.com/recaptcha/admin">', '<a/>' ),
				'id'    => 'woo_recaptcha_site_key',
				'type'  => 'text',
				'class' => 'large-text',
			),
			array(
				'name'  => esc_html__( 'Secret Key', 'recaptcha-for-woocommerce' ),
				'desc'  => sprintf( esc_html__( 'Used for communication between your site and Google. Grab it %1$sHere%2$s.', 'recaptcha-for-woocommerce' ), '<a target="_blank" href="https://www.google.com/recaptcha/admin">', '<a/>' ),
				'id'    => 'woo_recaptcha_secret_key',
				'type'  => 'text',
				'class' => 'large-text',
			),
			array(
				'name'    => esc_html__( 'Theme', 'recaptcha-for-woocommerce' ),
				'desc'    => esc_html__( 'Select reCAPTCHA theme', 'recaptcha-for-woocommerce' ),
				'id'      => 'woo_recaptcha_theme',
				'type'    => 'select',
				'css'     => 'min-width: 250px;',
				'default' => 'light',
				'options' => array(
					'light' => esc_html__( 'Light', 'recaptcha-for-woocommerce' ),
					'dark'  => esc_html__( 'Dark', 'recaptcha-for-woocommerce' ),
				),
			),
			array(
				'name'    => esc_html__( 'Language', 'recaptcha-for-woocommerce' ),
				'desc'    => esc_html__( 'Select reCAPTCHA language', 'recaptcha-for-woocommerce' ),
				'id'      => 'woo_recaptcha_language',
				'type'    => 'select',
				'css'     => 'min-width: 250px;',
				'default' => '',
				'options' => $languages,
			),
			array(
				'name'    => esc_html__( 'Size', 'recaptcha-for-woocommerce' ),
				'desc'    => esc_html__( 'Select reCAPTCHA size', 'recaptcha-for-woocommerce' ),
				'id'      => 'woo_recaptcha_size',
				'type'    => 'select',
				'css'     => 'min-width: 250px;',
				'default' => 'normal',
				'options' => array(
					'normal'  => esc_html__( 'Normal', 'recaptcha-for-woocommerce' ),
					'compact' => esc_html__( 'Compact', 'recaptcha-for-woocommerce' ),
				),
			),
			array(
				'name'    => esc_html__( 'Error Message', 'recaptcha-for-woocommerce' ),
				'desc'    => esc_html__( 'Enter the Error Message to display when reCAPTCHA is ignored or it is invalid.', 'recaptcha-for-woocommerce' ),
				'id'      => 'woo_recaptcha_error_message',
				'type'    => 'text',
				'default' => sprintf( esc_html__( 'Please retry CAPTCHA', 'recaptcha-for-woocommerce' ) ),
				'class'   => 'large-text',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'woo_recaptcha_settings',
			),
			array(
				'name' => esc_html__( 'WooCommerce Display Settings', 'recaptcha-for-woocommerce' ),
				'type' => 'title',
				'desc' => '',
				'id'   => 'woo_recaptcha_display_settings',
			),
			array(
				'title'   => esc_html__( 'Login Form', 'recaptcha-for-woocommerce' ),
				'desc'    => esc_html__( 'Check this box to enable reCAPTCHA in WooCommerce login form.', 'recaptcha-for-woocommerce' ),
				'id'      => 'woo_recaptcha_login',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'title'   => esc_html__( 'Registration Form', 'recaptcha-for-woocommerce' ),
				'desc'    => esc_html__( 'Check this box to enable reCAPTCHA in WooCommerce registration form.', 'recaptcha-for-woocommerce' ),
				'id'      => 'woo_recaptcha_registration',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'title'   => esc_html__( 'Lost Password Form', 'recaptcha-for-woocommerce' ),
				'desc'    => esc_html__( 'Check this box to enable reCAPTCHA in WooCommerce lost password form.', 'recaptcha-for-woocommerce' ),
				'id'      => 'woo_recaptcha_lost_password',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'title'   => esc_html__( 'Checkout Page', 'recaptcha-for-woocommerce' ),
				'desc'    => esc_html__( 'Check this box to enable reCAPTCHA in WooCommerce checkout page.', 'recaptcha-for-woocommerce' ),
				'id'      => 'woo_recaptcha_checkout',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'title'   => esc_html__( 'Captcha position', 'recaptcha-for-woocommerce' ),
				'desc'    => esc_html__( 'Select reCAPTCHA  position on checkout page', 'recaptcha-for-woocommerce' ),
				'id'      => 'woo_recaptcha_checkout_position',
				'type'    => 'select',
				'css'     => 'min-width: 250px;',
				'default' => 'after_checkout_form',
				'options' => array(
					'after_checkout_form'         => esc_html__( 'After Checkout Form', 'recaptcha-for-woocommerce' ),
					'before_chekout_form'         => esc_html__( 'Before Checkout Form', 'recaptcha-for-woocommerce' ),
					'checkout_order_review'       => esc_html__( 'Checkout Order Review', 'woo_reptcha' ),
					'checkout_after_order_review' => esc_html__( 'After Checkout Order Review', 'woo_reptcha' ),
					'before_place_order'          => esc_html__( 'Before Place Order Button', 'woo_reptcha' ),
				),
			),
			array(
				'name' => esc_html__( 'Display After Failed Attempts', 'recaptcha-for-woocommerce' ),
				'desc' => esc_html__( 'Recaptcha will only display after defined failed attempts. Leave it empty to display always. This will only work for Captcha position Before Place Order Button.', 'recaptcha-for-woocommerce' ),
				'id'   => 'woo_recaptcha_display_after_attempts',
				'type' => 'number',
			),
			array(
				'title'   => esc_html__( 'Checkout Order Pay Page', 'recaptcha-for-woocommerce' ),
				'desc'    => esc_html__( 'Check this box to enable reCAPTCHA in WooCommerce checkout order pay (checkout/order-pay/) page.', 'recaptcha-for-woocommerce' ),
				'id'      => 'woorecaptcha_checkout_order_pay',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'woo_recaptcha_display_settings',
			),
			array(
				'name' => esc_html__( 'WordPress Display Settings', 'recaptcha-for-woocommerce' ),
				'type' => 'title',
				'desc' => '',
				'id'   => 'woorecaptcha_wp_display_settings',
			),
			array(
				'title'   => esc_html__( 'WordPress Login', 'recaptcha-for-woocommerce' ),
				'desc'    => esc_html__( 'Check this box to enable reCAPTCHA in WordPress login form.', 'recaptcha-for-woocommerce' ),
				'id'      => 'woorecaptcha_wp_login',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'title'   => esc_html__( 'WordPress Register', 'recaptcha-for-woocommerce' ),
				'desc'    => esc_html__( 'Check this box to enable reCAPTCHA in WordPress register form.', 'recaptcha-for-woocommerce' ),
				'id'      => 'woorecaptcha_wp_register',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'title'   => esc_html__( 'WordPress Comments', 'recaptcha-for-woocommerce' ),
				'desc'    => esc_html__( 'Check this box to enable reCAPTCHA in WordPress comments form.', 'recaptcha-for-woocommerce' ),
				'id'      => 'woorecaptcha_wp_comments',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'woorecaptcha_wp_display_settings',
			),
			array(
				'name' => esc_html__( 'Misc Settings', 'recaptcha-for-woocommerce' ),
				'type' => 'title',
				'desc' => '',
				'id'   => 'woo_recaptcha_misc_settings',
			),
			array(
				'title'   => esc_html__( 'reCAPTCHA Not Working?', 'recaptcha-for-woocommerce' ),
				'desc'    => esc_html__( 'By default, reCAPTCHA will only work on WooCommerce pages. Check this box, If your theme using custom popup/page for WooCommerce login/registration and reCAPTCHA is not working.', 'recaptcha-for-woocommerce' ),
				'id'      => 'woo_recaptcha_inlcude_script',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'woo_recaptcha_misc_settings',
			),
		);

		return $woorecaptcha_settings;
	}

	/**
	 * Settings Tab
	 *
	 * Adds the reCapcha tab to the WooCommerce settings page.
	 *
	 * @param array $tabs tabs array.
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.0
	 */
	public function woorecaptcha_add_settings_tab( $tabs ) {

		$tabs['woo_recaptcha'] = esc_html__( 'reCAPTCHA', 'recaptcha-for-woocommerce' );
		return $tabs;
	}

	/**
	 * Settings Tab Content
	 *
	 * Adds the settings content to the min/max qunatities tab.
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.0
	 */
	public function woorecaptcha_settings_tab_content() {

		woocommerce_admin_fields( $this->woorecaptcha_get_settings() );
	}

	/**
	 * Update Settings
	 *
	 * Updates the settings when being saved.
	 *
	 *  @package reCAPTCHA for WooCommerce
	 * @since 1.0.0
	 */
	public function woorecaptcha_update_settings() {

		woocommerce_update_options( $this->woorecaptcha_get_settings() );
	}

	/**
	 * Adding Hooks
	 *
	 * Adding proper hooks for the shortcodes.
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.0
	 */
	public function add_hooks() {

		// Add filter to addd Min/Max Quantities tab on woocommerce setting page.
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'woorecaptcha_add_settings_tab' ), 99 );

		// Add action to add Min/Max Quantities tab content.
		add_action( 'woocommerce_settings_tabs_woo_recaptcha', array( $this, 'woorecaptcha_settings_tab_content' ) );

		// Add action to save custom update content.
		add_action( 'woocommerce_update_options_woo_recaptcha', array( $this, 'woorecaptcha_update_settings' ), 100 );
	}
}
