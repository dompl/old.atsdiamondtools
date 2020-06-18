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
		if ($log == 'yes' || WP_DEBUG === true ) {
	
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
	 * @return void
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
	 * @return void
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
	 * @return mixed post data, or null
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
	 * @return mixed post data, or null
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
			echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled, but you dont have an SSL certificate on your website. Please ensure that you have a valid SSL certificate.<br /><strong>ePDQ Direct Link will only work in test mode while there is no SSL</strong>" ), 'ePDQ Direct Link Checkout', admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";
		}
	}


	public static function add_disable_to_input() { ?>
		
		<script type="text/javascript">
	
			jQuery(document).ready( function () { 
				jQuery('#woocommerce_epdq_checkout_threeds, [for="woocommerce_epdq_checkout_threeds"]').prop('disabled', true).addClass('disabled');
			});
	
		</script>

	<?php }


}
