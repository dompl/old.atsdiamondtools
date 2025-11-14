<?php
/**
 * Script class to handle CSS and Js
 *
 * @package recaptcha-for-woocommerce
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scripts Class
 *
 * Contains logic to add scripts and style in admin and public site
 *
 * @package reCAPTCHA for WooCommerce
 * @since 1.0.0
 */
class WooRecaptcha_Scripts {

	/**
	 * Public class variable
	 *
	 * @var WooRecaptcha_Publi
	 */
	public $public;

	/**
	 * Constructor
	 */
	public function __construct() {

		global $woorecaptcha_public;
		$this->public = $woorecaptcha_public;
	}

	/**
	 * Enqueue admin script
	 *
	 * @param string $hook_suffix Hooks suffix.
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.0
	 */
	public function woorecaptcha_admin_script( $hook_suffix ) {

		if ( 'woocommerce_page_wc-settings' === $hook_suffix && isset( $_REQUEST['tab'] ) && 'woo_recaptcha' === $_REQUEST['tab'] ) {
			wp_register_script( 'woo-recaptcha-admin-script', WOORECAPTCHA_URL . 'includes/js/woo-recaptcha-admin.js', array( 'jquery' ), WOORECAPTCHA_PLUGIN_VERSION, true );
			wp_enqueue_script( 'woo-recaptcha-admin-script' );
		}
	}

	/**
	 * Enqueue public script
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.2
	 */
	public function woorecaptcha_public_script() {

		if ( is_checkout() ) {
			wp_register_script( 'woo-recaptcha-public-script', WOORECAPTCHA_URL . 'includes/js/woo-recaptcha-public.js', array( 'jquery' ), WOORECAPTCHA_PLUGIN_VERSION, true );
			wp_enqueue_script( 'woo-recaptcha-public-script' );

			$sitekey  = get_option( 'woo_recaptcha_site_key' );
			$theme    = get_option( 'woo_recaptcha_theme' );
			$size     = get_option( 'woo_recaptcha_size' );
			$attempts = get_option( 'woo_recaptcha_display_after_attempts' );

			wp_localize_script(
				'woo-recaptcha-public-script',
				'WooRecaptchaPulicVar',
				array(
					'sitekey'  => $sitekey,
					'theme'    => $theme,
					'size'     => $size,
					'attempts' => $attempts,
				)
			);
		}
	}

	/**
	 * Add action to enqueue reCAPCHA script
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.0
	 */
	public function woorecaptcha_load_script() {

		$login         = get_option( 'woo_recaptcha_login' );
		$registration  = get_option( 'woo_recaptcha_registration' );
		$lost_password = get_option( 'woo_recaptcha_lost_password' );
		$checkout      = get_option( 'woo_recaptcha_checkout' );
		$wp_login      = get_option( 'woorecaptcha_wp_login' );
		$wp_comments   = get_option( 'woorecaptcha_wp_comments' );

		if ( ( ! empty( $login ) && 'yes' === $login ) ||
			( ! empty( $registration ) && 'yes' === $registration ) ||
			( ! empty( $lost_password ) && 'yes' === $lost_password ) ||
			( ! empty( $checkout ) && 'yes' === $checkout ) ||
			( 'yes' === $wp_comments )
		) {
			// add action to enqueue recapcha js.
			add_action( 'wp_footer', array( $this, 'woorecaptcha_script' ), 99 );
		}

		if ( ! empty( $wp_login ) && 'yes' === $wp_login ) {
			// add action to enqueue recapcha js.
			add_action( 'login_footer', array( $this, 'woorecaptcha_script' ), 99 );
		}
	}

	/**
	 * Enqueue reCAPCHA scripts
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.0
	 */
	public function woorecaptcha_script() {

		$include_script = get_option( 'woo_recaptcha_inlcude_script' );

		if ( is_checkout() ||
			is_account_page() ||
			is_checkout_pay_page() ||
			( is_single() && 'post' === get_post_type() ) ||
			( 'wp-login.php' === $GLOBALS['pagenow'] ) ||
			'yes' === $include_script ||
			apply_filters( 'woo_include_recaptcha_script', false )
		) {
			$selected_lang = get_option( 'woo_recaptcha_language' );
			// if language is empty (auto detected chosen) do nothing otherwise add the lang query to the
			// reCAPTCHA script url.
			if ( isset( $selected_lang ) && ( ! empty( $selected_lang ) ) ) {
				$lang = "&hl=$selected_lang";
			} else {
				$selected_lang = get_locale();
				if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
					$selected_lang = ICL_LANGUAGE_CODE;
				}
				$lang = "&hl=$selected_lang";
			}

			$total_captcha = $this->public->woo_total_captcha();

			$site_key = get_option( 'woo_recaptcha_site_key' );
			$theme    = get_option( 'woo_recaptcha_theme' );
			$size     = get_option( 'woo_recaptcha_size' );
			?>

			<script type="text/javascript">
				var recaptchaCallBack = function () {
			<?php for ( $i = 1; $i <= $total_captcha; $i++ ) { ?>
						var woorecaptcha_<?php echo $i; ?>;
						if( document.getElementById('woorecaptcha_field_<?php echo $i; ?>') ) {
							woorecaptcha_<?php echo $i; ?> = grecaptcha.render('woorecaptcha_field_<?php echo $i; ?>', {
								'sitekey': '<?php echo esc_js( $site_key ); ?>',
								'theme': '<?php echo esc_js( $theme ); ?>',
								'size': '<?php echo esc_js( $size ); ?>'
							});
						}
			<?php } ?>
				};
			</script>
			<script src="https://www.google.com/recaptcha/api.js?onload=recaptchaCallBack&render=explicit<?php echo esc_js( $lang ); ?>" async defer></script>
			<?php
		}
	}

	/**
	 * Add hooks ( action and filters).
	 *
	 * Contains all action and filter related to scripts
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.0
	 */
	public function add_hooks() {

		// add actio to add admin script.
		add_action( 'admin_enqueue_scripts', array( $this, 'woorecaptcha_admin_script' ) );

		// add action to load scripts.
		add_action( 'init', array( $this, 'woorecaptcha_load_script' ) );

		// add action to add public script.
		add_action( 'wp_enqueue_scripts', array( $this, 'woorecaptcha_public_script' ) );
	}
}
