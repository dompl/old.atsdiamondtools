<?php
/*-----------------------------------------------------------------------------------*/
/*	AG ePDQ functions
/*-----------------------------------------------------------------------------------*/
defined('ABSPATH') || die("No script kiddies please!");


if (class_exists('AG_ePDQ_Helpers')) {
	return;
}


class AG_ePDQ_Helpers
{

	/**
	 * Log errors in WooCommerce logs if debug mode in plugin is enabled or WP_Debug is true.
	 *
	 * @param $message
	 * @param $level
	 * @param $log
	 * @return void
	 */
	public static function ag_log($message, $level, $log)
	{
		if ($log === 'yes' || WP_DEBUG === true ) {
	
			// Log errors in WooCommerce logs
			$message = $message . PHP_EOL;
			$logger = wc_get_logger();
			$logger->$level( $message, array( 'source' => 'AG-WooCommerce-Barclays-ePDQ-Payment-Gateway' ) );

		}
	}


    /**
     * Loop through returned order data and store.
     *
     * @param $post_id
     * @param $meta
     * @return false
     */
	public static function update_order_meta_data($post_id, $meta)
	{
		if (!get_post($post_id) || !is_array($meta)) {
			return false;
		}

		foreach ($meta as $meta_key => $meta_value) {
			if (!empty($meta_value)) {
				update_post_meta($post_id, $meta_key, $meta_value);
			}
		}
	}

	/**
	 * Loop through returned order data and set as notes.
	 *
	 * @param $customer_order
	 * @param $meta
	 * @return false
     */
	public static function update_order_notes($customer_order, $meta)
	{
		if (!is_array($meta)) {
			return false;
		}

		$order_notes = array();
		foreach ($meta as $key => $value) {
			if (!empty($value) && !empty($key)) {
				if ($value == '') continue;
				$order_notes[] =  $key . ' ' . $value . '<br />';
			}
		}
		$data_back = implode('', $order_notes);
		$customer_order->add_order_note($data_back);
	}


	/**
	 * Get post data and sanitise if set
	 *
	 * @param string $name name of post argument to get
	 * @return string|null post data, or null
	 */
	 public static function AG_get_post_data( $name ) {
		if ( isset( $_POST[ $name ] ) ) {
			return htmlspecialchars(trim( $_POST[ $name ] ), ENT_QUOTES, 'UTF-8');
		}
		return null;
	}


	/**
	 * Get request data and sanitise if set
	 *
	 * @param string $name name of post argument to get
	 * @return string|null post data, or null
	 */
	public static function AG_get_request( $name ) {
		if ( isset( $_REQUEST[ $name ] ) ) {
			return htmlspecialchars(trim( $_REQUEST[ $name ] ), ENT_QUOTES, 'UTF-8');
		}
		return null;
	}


	public static function AG_escape( $data ) {
		return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
	}

	public static function AG_decode( $data ) {
		return htmlspecialchars_decode($data, ENT_QUOTES);
	}


	/**
	 * Luhn check
	 *
	 * @param $account_number
	 * @return void
	 */
	public static function luhn_algorithm_check( $account_number ) {
		
		$sum = 0;
		$account_number = (int) $account_number;

		// Loop through each digit and do the maths
		for ( $i = 0, $ix = strlen( $account_number ); $i < $ix - 1; $i++) {
			$weight = substr( $account_number, $ix - ( $i + 2 ), 1 ) * ( 2 - ( $i % 2 ) );
			$sum += $weight < 10 ? $weight : $weight - 9;
		}

		// If the total mod 10 equals 0, the number is valid
		return substr( $account_number, $ix - 1 ) == ( ( 10 - $sum % 10 ) % 10 );

	}

	/**
	 * SSL check
	 *
	 * @return void
	 */
	public static function do_ssl_check() {
		if( is_ssl() == false ) {
			echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled, but you dont have an SSL certificate on your website. Please ensure that you have a valid SSL certificate.<br /><strong>ePDQ Direct Link will only work in test mode while there is no SSL</strong>", 'ag_epdq_server' ), 'ePDQ Direct Link Checkout', admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";
		}
	}


	public static function add_disable_to_input() { ?>
		
		<script type="text/javascript">
	
			jQuery(document).ready( function () { 
				jQuery('#woocommerce_epdq_checkout_threeds, [for="woocommerce_epdq_checkout_threeds"]').prop('disabled', true).addClass('disabled');
			});
	
		</script>

	<?php }


	public static function order_contains_subscription($order_id) {

		return function_exists('wcs_order_contains_subscription') && (wcs_order_contains_subscription($order_id) || wcs_order_contains_renewal($order_id));
	}


    /**
     * Get the right enviroment URL
     *
     * @param [type] $endpoint - where we are posting to
     * @return string $enviroment_url
     */
	public static function get_enviroment_url($endpoint) {

		$ePDQ_settings  = new epdq_checkout();

		if ($ePDQ_settings->status === 'test')	$environment_url = 'https://mdepayments.epdq.co.uk/ncol/test/'. $endpoint. '.asp';
		if ($ePDQ_settings->status === 'live')	$environment_url = 'https://payments.epdq.co.uk/ncol/prod/'. $endpoint. '.asp';

		return $environment_url;
	}


	public static function remote_post( $environment_url, $data_post ) {
		
		$post_string = array();
		foreach ($data_post as $key => $value) {
			$post_string[] = $key . '=' . $value;
		}

		$actual_string = implode('&', $post_string);
		$result = wp_safe_remote_post(esc_url($environment_url), array(
			'method' => 'POST',
			'timeout'     => 6,
			'redirection' => 5,
			'body' => $actual_string,
		));

		// Check for error
		if ( is_wp_error( $result ) ) {
			error_log( 'ERROR' );
			error_log( print_r( $result, true ) );
			return;
		}
		
		return $result;

	}

	/**
     * Get order currency - used for refund and status check
	 * @param $order
	 * @return mixed
	 */
	public static function ag_get_order_currency($order) {

	    return $order->get_currency();

	}

	/**
     * Limit the address fields. ePDQ have a character 34 limit on their end.
	 * @param $fields
	 * @return array
	 */
	public static function ag_billing_address_limit( $fields ) {

	    $fields['billing']['billing_address_1']['maxlength'] = 34;
		$fields['billing']['billing_address_2']['maxlength'] = 34;

		return $fields;

	}

	// Not used.
	public static function word_translate($translate) {

		$pattern = array("'é'", "'è'", "'ë'", "'ê'", "'É'", "'È'", "'Ë'", "'Ê'", "'á'", "'à'", "'ä'", "'â'", "'å'", "'Á'", "'À'", "'Ä'", "'Â'", "'Å'", "'ó'", "'ò'", "'ö'", "'ô'", "'Ó'", "'Ò'", "'Ö'", "'Ô'", "'í'", "'ì'", "'ï'", "'î'", "'Í'", "'Ì'", "'Ï'", "'Î'", "'ú'", "'ù'", "'ü'", "'û'", "'Ú'", "'Ù'", "'Ü'", "'Û'", "'ý'", "'ÿ'", "'Ý'", "'ø'", "'Ø'", "'œ'", "'Œ'", "'Æ'", "'ç'", "'Ç'");
		$replace = array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E', 'a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A', 'A', 'o', 'o', 'o', 'o', 'O', 'O', 'O', 'O', 'i', 'i', 'i', 'I', 'I', 'I', 'I', 'I', 'u', 'u', 'u', 'u', 'U', 'U', 'U', 'U', 'y', 'y', 'Y', 'o', 'O', 'a', 'A', 'A', 'c', 'C');

		return preg_replace($pattern, $replace, $translate);

	}


}
