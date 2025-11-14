<?php
/**
 * Public class to handle all frontend functionality
 *
 * @package recaptcha-for-woocommerce
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Public class
 *
 * @package reCAPTCHA for WooCommerce
 * @since 1.0.0
 */
class WooRecaptcha_Public {

	/**
	 * Reacaptcha Site Key
	 *
	 * @var string
	 */
	private $sitekey;

	/**
	 * Recaptcha Theme
	 *
	 * @var string
	 */
	private $theme;

	/**
	 * Recaptcha Size
	 *
	 * @var string
	 */
	private $size;

	/**
	 * Count Total Recaptcha on Page
	 *
	 * @var int
	 */
	private static $captcha_count = 0;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// get size key.
		$this->sitekey = get_option( 'woo_recaptcha_site_key' );
		// get theme.
		$this->theme = get_option( 'woo_recaptcha_theme' );
		// get size.
		$this->size = get_option( 'woo_recaptcha_size' );
	}

	/**
	 * Return total captcha
	 *
	 * @return init
	 */
	public function woo_total_captcha() {
		return self::$captcha_count;
	}

	/**
	 * Handle to add action and filter if particular display settins is enabled
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.0
	 */
	public function woorecaptcha_display() {

		// check if login is enabled.
		$login = get_option( 'woo_recaptcha_login' );
		if ( ! empty( $login ) && 'yes' === $login ) {

			// add action to display captcha to the login form.
			add_filter( 'woocommerce_login_form', array( $this, 'woorecaptcha_display_login_recaptcha' ) );

			// add action to validate recaptcha.
			add_action( 'woocommerce_process_login_errors', array( $this, 'woorecaptcha_validate_login_recaptcha' ), 15, 3 );
		}

		// Check if registration is activated.
		$registration = get_option( 'woo_recaptcha_registration' );
		if ( ! empty( $registration ) && 'yes' === $registration ) {

			// add action to display captcha to the registration form.
			add_action( 'woocommerce_register_form', array( $this, 'woorecaptcha_display_register_recaptcha' ) );

			// add action to validate recaptcha.
			add_filter( 'woocommerce_registration_errors', array( $this, 'woorecaptcha_validate_registration_recaptcha' ), 10, 3 );
		}

		// check if lost paassword is activated.
		$lost_password = get_option( 'woo_recaptcha_lost_password' );
		if ( ! empty( $lost_password ) && 'yes' === $lost_password ) {

			// add action to display captcha to lost password form.
			add_filter( 'woocommerce_lostpassword_form', array( $this, 'woorecaptcha_display_lostpassword_recaptcha' ) );

			// add action to validate recaptcha.
			add_action( 'lostpassword_post', array( $this, 'woorecaptcha_validate_lost_password_recaptcha' ), 10 );
		}

		// check if checkout recaptcha is activated.
		$checkout = get_option( 'woo_recaptcha_checkout' );
		if ( ! empty( $checkout ) && 'yes' === $checkout ) {

			// get position of recaptcha on checkout page.
			$position = get_option( 'woo_recaptcha_checkout_position' );

			// add action to display capcaptcha on checkout page.
			if ( empty( $position ) || 'after_checkout_form' === $position ) {
				add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'woorecaptcha_display_checkout_recaptcha' ) );
			} elseif ( 'before_chekout_form' === $position ) {
				add_action( 'woocommerce_before_checkout_form', array( $this, 'woorecaptcha_display_checkout_recaptcha' ) );
			} elseif ( 'checkout_order_review' === $position ) {
				add_action( 'woocommerce_checkout_order_review', array( $this, 'woorecaptcha_display_checkout_recaptcha' ) );
			} elseif ( 'checkout_after_order_review' === $position ) {
				add_action( 'woocommerce_checkout_after_order_review', array( $this, 'woorecaptcha_display_checkout_recaptcha' ), 0 );
			} elseif ( 'before_place_order' === $position ) {
				add_action( 'woocommerce_review_order_before_submit', array( $this, 'woorecaptcha_display_checkout_bpo_recaptcha' ) );
			}

			// add action to validate recaptcha.
			add_action( 'woocommerce_checkout_process', array( $this, 'woorecaptcha_validate_checkout_recaptcha' ), 10 );
		}

		// Checkout order pay.
		$order_pay = get_option( 'woorecaptcha_checkout_order_pay' );
		if ( ! empty( $order_pay ) && 'yes' === $order_pay ) {
			add_action( 'woocommerce_pay_order_before_submit', array( $this, 'woorecaptcha_display_order_pay_recaptcha' ) );
			add_action( 'woocommerce_before_pay_action', array( $this, 'woorecaptcha_validate_pay_action' ) );
		}

		// WordPress Login form.
		$wp_login_form = get_option( 'woorecaptcha_wp_login' );
		if ( ! empty( $wp_login_form ) && 'yes' === $wp_login_form ) {
			add_action( 'login_form', array( $this, 'woorecaptcha_display_captcha_on_wordpress_login' ) );
			add_filter( 'wp_authenticate_user', array( $this, 'woorecaptcha_validate_captcha_on_wordpress_login' ), 10, 2 );
		}

		// WordPress Register form.
		$wp_register = get_option( 'woorecaptcha_wp_register' );
		if ( ! empty( $wp_register ) && 'yes' === $wp_register ) {
			add_action( 'register_form', array( $this, 'woorecaptcha_display_captcha_on_wordpress_login' ) );
			add_filter( 'registration_errors', array( $this, 'woorecaptcha_validate_captcha_on_wordpress_register' ), 10, 3 );
		}

		// Comments form.
		$wordpress_comment_form = get_option( 'woorecaptcha_wp_comments' );
		if ( ! empty( $wordpress_comment_form ) && 'yes' === $wordpress_comment_form ) {
			if ( ! is_user_logged_in() ) {
				add_action( 'comment_form_after_fields', array( $this, 'woorecaptcha_display_captcha_on_comment_form' ) );
			} else {
				add_action( 'comment_form_logged_in_after', array( $this, 'woorecaptcha_display_captcha_on_comment_form' ) );
			}

			add_filter( 'preprocess_comment', array( $this, 'woorecaptcha_validate_comment_captcha' ) );
		}
	}

	/**
	 * Output the reCAPTCHA field on login form
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.2
	 */
	public function woorecaptcha_display_login_recaptcha() {

		self::$captcha_count++;
		$total_captcha = $this->woo_total_captcha();

		if ( is_checkout() ) {  // if its checkout page.
			$class = apply_filters( 'woorecaptcha_display_login_recaptcha', 'form-row' );
		} else { // if its my account page.
			$class = apply_filters( 'woorecaptcha_display_login_recaptcha', 'woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide' );
		}

		echo '<div class="woorecaptcha_field ' . esc_attr( $class ) . '">
                <div data-sitekey="' . esc_html( $this->sitekey ) . '" id="woorecaptcha_field_' . esc_html( $total_captcha ) . '" class="g-recaptcha"></div>
            </div>';
	}

	/**
	 * Output the reCAPTCHA field on registraiton form
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.2
	 */
	public function woorecaptcha_display_register_recaptcha() {

		self::$captcha_count++;
		$total_captcha = $this->woo_total_captcha();

		$class = apply_filters( 'woorecaptcha_display_register_recaptcha', 'woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide' );

		echo '<div class="woorecaptcha_field ' . esc_attr( $class ) . '">
                <div data-sitekey="' . esc_html( $this->sitekey ) . '" id="woorecaptcha_field_' . esc_html( $total_captcha ) . '" class="g-recaptcha"></div>
            </div>';
	}

	/**
	 * Output the reCAPTCHA field on lost password form
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.2
	 */
	public function woorecaptcha_display_lostpassword_recaptcha() {

		self::$captcha_count++;
		$total_captcha = $this->woo_total_captcha();

		$class = apply_filters( 'woorecaptcha_display_lostpassword_recaptcha', 'woocommerce-FormRow woocommerce-FormRow--first form-row' );

		echo '<div class="woorecaptcha_field ' . esc_attr( $class ) . '">
                <div data-sitekey="' . esc_html( $this->sitekey ) . '" id="woorecaptcha_field_' . esc_html( $total_captcha ) . '" class="g-recaptcha"></div>
            </div>';
	}

	/**
	 * Output the reCAPTCHA field
	 * Before place order button on checkout page
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.2
	 */
	public function woorecaptcha_display_checkout_bpo_recaptcha() {

		$class = apply_filters( 'woorecaptcha_display_checkout_bpo_recaptcha', '' );
		$style = apply_filters( 'woorecaptcha_display_checkout_bpo_recaptcha_style', 'display:inline-block;float:left;' );

		echo '<div class="woorecaptcha_field ' . esc_attr( $class ) . '" style="' . esc_attr( $style ) . '">
                <div data-sitekey="' . esc_html( $this->sitekey ) . '" id="woorecaptcha_field_2" class="g-recaptcha"></div>
            </div>';
	}

	/**
	 * Output the reCAPTCHA field on checkout page
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.0
	 */
	public function woorecaptcha_display_checkout_recaptcha() {

		self::$captcha_count++;
		$total_captcha = $this->woo_total_captcha();

		echo '<div class="woorecaptcha_field form-row form-row-wide">
                <div data-sitekey="' . esc_html( $this->sitekey ) . '" id="woorecaptcha_field_' . esc_html( $total_captcha ) . '" class="g-recaptcha"></div>
            </div>';
	}

	/**
	 * Dispaly recaptcha on checkout order page page
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @version 1.1.1
	 */
	public function woorecaptcha_display_order_pay_recaptcha() {

		self::$captcha_count++;
		$total_captcha = $this->woo_total_captcha();

		echo '<div class="woorecaptcha_field form-row form-row-wide">
				<div data-sitekey="' . esc_html( $this->sitekey ) . '" id="woorecaptcha_field_' . esc_html( $total_captcha ) . '" class="g-recaptcha"></div>
			</div>';
	}

	/**
	 * Display recaptcha on WordPress login form.
	 */
	public function woorecaptcha_display_captcha_on_wordpress_login() {
		self::$captcha_count++;
		$total_captcha = $this->woo_total_captcha();
		$style         = apply_filters( 'woorecaptcha_display_captcha_on_wordpress_login_style', 'transform: scale(.9);transform-origin: 0 0;clear: both;' );

		echo '<div class="woorecaptcha_field">
				<div style="' . esc_attr( $style ) . '" data-sitekey="' . esc_html( $this->sitekey ) . '" id="woorecaptcha_field_' . esc_html( $total_captcha ) . '" class="g-recaptcha"></div>
			</div>';
	}

	/**
	 * Display recaptcha on WordPress comment form.
	 */
	public function woorecaptcha_display_captcha_on_comment_form() {
		self::$captcha_count++;
		$total_captcha = $this->woo_total_captcha();

		echo '<div class="woorecaptcha_field">
				<label>Recaptcha</label>
				<div data-sitekey="' . esc_html( $this->sitekey ) . '" id="woorecaptcha_field_' . esc_html( $total_captcha ) . '" class="g-recaptcha"></div>
			</div>';
	}

	/**
	 * Verify the captcha answer on login form
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.0
	 *
	 * @param WP_Error $validation_error WP_Error object.
	 * @param string   $username username.
	 * @param string   $password login password.
	 *
	 * @return WP_Error|WP_user
	 */
	public function woorecaptcha_validate_login_recaptcha( $validation_error, $username, $password ) {		

		if ( ! isset( $_POST['g-recaptcha-response'] ) || ! $this->woorecaptcha_verify() ) {
			$error_message = get_option( 'woo_recaptcha_error_message' );
			$validation_error = new WP_Error( 'recaptcha_error', $error_message );
		}

		return $validation_error;
	}

	/**
	 * Verify the captcha answer on registration form
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.0
	 *
	 * @param string $validation_errors validation error object.
	 * @param string $username username.
	 * @param string $email email.
	 *
	 * @return WP_Error
	 */
	public function woorecaptcha_validate_registration_recaptcha( $validation_errors, $username, $email ) {

		// to check that function is called from registeration form.
		if ( isset( $_POST['register'] ) && ! empty( $_POST['register'] ) ) {

			$error_message = get_option( 'woo_recaptcha_error_message' );

			if ( ! isset( $_POST['g-recaptcha-response'] ) || ! $this->woorecaptcha_verify() ) {
				$validation_errors = new WP_Error( 'recaptcha_error', $error_message );
			}
		}
		return $validation_errors;
	}

	/**
	 * Verify the captcha answer on lost password form.
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.0
	 *
	 * @param WP_Error $errors WP_Error object.
	 * @return WP_Error
	 */
	public function woorecaptcha_validate_lost_password_recaptcha( $erros ) {		

		if ( ! isset( $_POST['g-recaptcha-response'] ) || ! $this->woorecaptcha_verify() ) {
			$error_message = get_option( 'woo_recaptcha_error_message' );
			// return new WP_Error('recaptcha_error', $error_message);
			$erros->add( 'recaptcha_error', $error_message );
		}

		return $erros;
	}

	/**
	 * Verify the captcha answer on checkout page
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.0
	 */
	public function woorecaptcha_validate_checkout_recaptcha() {

		$error_message = get_option( 'woo_recaptcha_error_message' );

		if ( isset( $_POST['g-recaptcha-response'] ) ) {

			$response = $this->woorecaptcha_verify( true );

			// get recaptcha verified value.
			$g_recaptcha_verified = WC()->session->get( 'g_recaptcha_verified' );

			// check that recaptcha on checkout is already verified. if yes then no need to validation again
			// as google recaptcha not validate second time untill page is refreshed.
			if ( true === $g_recaptcha_verified && isset( $response['error-codes'] ) && 'timeout-or-duplicate' === $response['error-codes'][0] ) {
				return;
			}

			// If response success empty that means it not valid, so throw an error.
			if ( empty( $response['success'] ) ) {
				wc_add_notice( $error_message, 'error' );
			}
		}
	}

	/**
	 * Validate captcha on order pay page
	 *
	 * @param Object $order Order Object.
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.1.1
	 */
	public function woorecaptcha_validate_pay_action( $order ) {

		if ( ! isset( $_POST['g-recaptcha-response'] ) || ! $this->woorecaptcha_verify() ) {
			$error_message = get_option( 'woo_recaptcha_error_message' );
			wc_add_notice( $error_message, 'error' );
			return;
		}
	}

	/**
	 * Validate recaptcha for WordPress login form.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_User|WP_Error $user     WP_User or WP_Error object.
	 * @param string           $password Password to check against the user.
	 * @return WP_User|WP_Error Modified object.
	 */
	public function woorecaptcha_validate_captcha_on_wordpress_login( $user, $password ) {

		if ( isset( $_POST['wp-submit'] ) ) {
			if ( ! isset( $_POST['g-recaptcha-response'] ) || ! $this->woorecaptcha_verify() ) {
				$error_message = get_option( 'woo_recaptcha_error_message' );
				$error_message = '<strong>' . esc_html__( 'Error: ', 'recaptcha-for-woocommerce' ) . '</strong>' . $error_message;
				$user          = new WP_Error( 'recaptcha_error', $error_message );
			}
		}

		return $user;
	}

	/**
	 * Validate recaptcha for WordPress register form.
	 *
	 * @param WP_Error $errors      A WP_Error object.
	 * @param string   $user_login  User's username after it has been sanitized.
	 * @param string   $user_email  User's email.
	 * @return WP_Error Modified error object.
	 */
	public function woorecaptcha_validate_captcha_on_wordpress_register( $errors, $user_login, $user_email ) {

		if ( isset( $_POST['wp-submit'] ) ) {
			if ( ! isset( $_POST['g-recaptcha-response'] ) || ! $this->woorecaptcha_verify() ) {
				$error_message = get_option( 'woo_recaptcha_error_message' );
				$error_message = '<strong>' . esc_html__( 'Error: ', 'recaptcha-for-woocommerce' ) . '</strong>' . $error_message;
				$errors        = new WP_Error( 'recaptcha_error', $error_message );
			}
		}

		return $errors;
	}

	/**
	 * Validation captcha for WordPress comment form.
	 *
	 * @param Object $commentdata Comment data object.
	 */
	public function woorecaptcha_validate_comment_captcha( $commentdata ) {

		if ( ! isset( $_POST['g-recaptcha-response'] ) || ! $this->woorecaptcha_verify() ) {
			$error_message = get_option( 'woo_recaptcha_error_message' );
			wp_die(
				'<p><strong>' . esc_html__( 'Error: ', 'recaptcha-for-woocommerce' ) . '</strong> ' . esc_html( $error_message ) . '</p>',
				'recaptcha-for-woocommerce',
				array(
					'response'  => 403,
					'back_link' => 1,
				)
			);
		}

		return $commentdata;
	}

	/**
	 * Verify capcha selected by user
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.0
	 *
	 * @param string $full_response full response.
	 * @return bool
	 */
	public function woorecaptcha_verify( $full_response = false ) {

		// get secret key.
		$secret_key = get_option( 'woo_recaptcha_secret_key' );
		// get user selected value.
		$response = isset( $_POST['g-recaptcha-response'] ) ? esc_attr( $_POST['g-recaptcha-response'] ) : '';
		// remote ip.
		$remote_ip = $_SERVER['REMOTE_ADDR'];

		// make a GET request to the Google reCAPTCHA Server.
		$request = wp_remote_get(
			'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $response . '&remoteip=' . $remote_ip
		);

		// get the request response body.
		$response_body = wp_remote_retrieve_body( $request );
		$result        = json_decode( $response_body, true );
		if ( ! empty( $result['success'] ) ) {
			// if recaptcha is validated then set session variable so that problem will not come on second time.
			WC()->session->set( 'g_recaptcha_verified', true );
		}

		if ( $full_response ) {
			return $result;
		}

		return $result['success'];
	}

	/**
	 * Clear session variable if its set.
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.0
	 */
	public function woorecaptcha_clear_session_var() {

		if ( ! is_admin() ) {
			WC()->session->set( 'g_recaptcha_verified', 'null' );
		}
	}

	/**
	 * Clear localstorage after successful order
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.1.0
	 */
	public function woorecaptcha_clear_data() {
		?>
		<script type="text/javascript">
			localStorage.removeItem("woorecaptcha_checkout_attempts");
		</script>
		<?php
	}

	/**
	 * Add hooks
	 *
	 * @package reCAPTCHA for WooCommerce
	 * @since 1.0.0
	 */
	public function add_hooks() {

		// add action to display recaptcha if any display settings is enbabled.
		add_action( 'init', array( $this, 'woorecaptcha_display' ) );

		// add action to clear recaptcha variable from session.
		add_action( 'wp_footer', array( $this, 'woorecaptcha_clear_session_var' ) );

		add_action( 'woocommerce_thankyou', array( $this, 'woorecaptcha_clear_data' ), 20 );
	}
}
