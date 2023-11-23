<?php
/*-----------------------------------------------------------------------------------*/

/*	AG ePDQ functions
/*-----------------------------------------------------------------------------------*/
defined( 'ABSPATH' ) || die( "No script kiddies please!" );

if( class_exists( 'AG_ePDQ_Helpers' ) ) {
	return;
}


class AG_ePDQ_Helpers {

	/**
	 * Loop through returned order data and store.
	 *
	 * @param $post_id
	 * @param $meta
	 *
	 * @return false
	 */
	public static function update_order_meta_data( $post_id, $meta, $order ) {

		if( ! get_post( $post_id ) || ! is_array( $meta ) ) {
			return FALSE;
		}

		foreach( $meta as $meta_key => $meta_value ) {
			if( ! empty( $meta_value ) ) {
				$order->update_meta_data( self::AG_decode( $meta_key ), self::AG_decode( $meta_value ) );
			}
		}
		$order->save();
	}

	/**
	 * Loop through returned order data and set as notes.
	 *
	 * @param $customer_order
	 * @param $meta
	 *
	 * @return false
	 */
	public static function update_order_notes( $customer_order, $meta ) {

		if( ! is_array( $meta ) ) {
			return FALSE;
		}

		$order_notes = array();
		foreach( $meta as $key => $value ) {
			if( ! empty( $value ) && ! empty( $key ) ) {
				if( $value == '' ) {
					continue;
				}
				$order_notes[] = self::AG_decode( $key ) . ' ' . self::AG_decode( $value ) . '<br />';

			}
		}
		$data_back = implode( '', $order_notes );
		$customer_order->add_order_note( $data_back );
	}

	/**
	 * Get post data and sanitise if set
	 *
	 * @param string $name name of post argument to get
	 *
	 * @return string|null post data, or null
	 */
	public static function AG_get_post_data( $name ) {

		if( isset( $_POST[ $name ] ) ) {
			return htmlspecialchars( trim( $_POST[ $name ] ), ENT_QUOTES, 'UTF-8' );
		}

		return NULL;
	}

	/**
	 * Get request data and sanitise if set
	 *
	 * @param string $name name of post argument to get
	 *
	 * @return string|null post data, or null
	 */
	public static function AG_get_request( $name ) {

		if( isset( $_REQUEST[ $name ] ) ) {
			return htmlspecialchars( trim( $_REQUEST[ $name ] ), ENT_QUOTES, 'UTF-8' );
		}

		return NULL;
	}

	public static function AG_decode( $data ) {

		return htmlspecialchars_decode( $data, ENT_QUOTES );
	}

	/**
	 * Luhn check
	 *
	 * @param $account_number
	 *
	 * @return void
	 */
	public static function luhn_algorithm_check( $account_number ) {

		$sum = 0;
		$account_number = (int) $account_number;

		// Loop through each digit and do the maths
		for( $i = 0, $ix = strlen( $account_number ); $i < $ix - 1; $i ++ ) {
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

		if( is_ssl() == FALSE ) {
			echo "<div class=\"error\"><p>" . sprintf( __( "<strong>%s</strong> is enabled, but you don't have an SSL certificate on your website. Please ensure that you have a valid SSL certificate.<br /><strong>ePDQ Direct Link will only work in test mode while there is no SSL</strong>", 'ag_epdq_server' ), 'ePDQ Direct Link Checkout', admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . "</p></div>";
		}
	}

	public static function order_contains_subscription( $order_id ) {

		return function_exists( 'wcs_order_contains_subscription' ) && ( wcs_order_contains_subscription( $order_id ) || wcs_order_contains_renewal( $order_id ) );
	}

	/**
	 * Get the right enviroment URL
	 *
	 * @param [type] $endpoint - where we are posting to
	 *
	 * @return string $enviroment_url
	 */
	public static function get_enviroment_url( $endpoint ) {

		$ePDQ_settings = new epdq_checkout();

		if( $ePDQ_settings->status === 'test' ) {
			$environment_url = 'https://mdepayments.epdq.co.uk/ncol/test/' . $endpoint . '.asp';
		}
		if( $ePDQ_settings->status === 'live' ) {
			$environment_url = 'https://payments.epdq.co.uk/ncol/prod/' . $endpoint . '.asp';
		}

		return $environment_url;
	}

	public static function remote_post( $environment_url, $data_post ) {

		$post_string = array();
		foreach( $data_post as $key => $value ) {
			$post_string[] = $key . '=' . urlencode( $value );
		}

		$actual_string = implode( '&', $post_string );
		$result = wp_safe_remote_post( esc_url( $environment_url ), array(
			'method'       => 'POST',
			'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
			'redirection'  => 5,
			'sslverify'    => TRUE,
			'body'         => $actual_string,
			'timeout'      => 60,
		) );

		// Check for error
		if( is_wp_error( $result ) ) {

			if( strpos( $result->get_error_message(), 'cURL error 28' ) !== FALSE ) {
				error_log( 'CURL error 28: Operation timed out' );
				self::ag_log( 'CURL error 28: Operation timed out for order #' . self::AG_escape( $data_post['ORDERID'] ), 'warning', 'yes' );
				// Set order status to on old.
				$customer_order = new WC_Order( self::AG_escape( $data_post['ORDERID'] ) );
				$customer_order->update_status( 'on-hold' );
				$customer_order->add_order_note( 'There was an issue processing this request, your website server returned: CURL error 28: Operation timed out. We have auto set the order status to on hold. Please try processing the request again.' );

				// TODO: Run status check to double-check the order.
				wp_die( 'There was an issue processing this request, your website server returned: CURL error 28: Operation timed out.' );
			}

			error_log( 'ERROR' );
			error_log( print_r( $result, TRUE ) );
			self::ag_log( print_r( $result, TRUE ), 'warning', 'yes' );
			wp_die( 'ERROR ' . $result );
		}

		// Sort out the response
		$xml_data = $result['body'];
		$xml_object = simplexml_load_string( $xml_data, 'SimpleXMLElement', LIBXML_NOCDATA );
		$json_string = json_encode( $xml_object );
		$response = json_decode( $json_string, TRUE );
		$values = array_values( $response )[0];

		//sanitize values
		foreach( $values as $key => $value ) {
			if( $value === "" ) {
				continue;
			}
			$values[ self::AG_escape( $key ) ] = self::AG_escape( $value );
		}

		return $values;

	}

	/**
	 * Log errors in WooCommerce logs if debug mode in plugin is enabled or WP_Debug is true.
	 *
	 * @param $message
	 * @param $level
	 * @param $log
	 *
	 * @return void
	 */
	public static function ag_log( $message, $level, $log ) {

		if( $log === 'yes' || WP_DEBUG === TRUE ) {

			// Log errors in WooCommerce logs
			$message = $message . PHP_EOL;
			$logger = wc_get_logger();
			$logger->$level( $message, array( 'source' => 'AG-WooCommerce-Barclays-ePDQ-Payment-Gateway' ) );

		}
	}

	public static function AG_escape( $data ) {

		return htmlspecialchars( $data, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Get order currency - used for refund and status check
	 *
	 * @param $order
	 *
	 * @return mixed
	 */
	public static function ag_get_order_currency( $order ) {

		return $order->get_currency();

	}

	/**
	 * Limit the address fields. ePDQ have a character 34 limit on their end.
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public static function ag_billing_address_limit( $fields ) {

		$fields['billing']['billing_address_1']['maxlength'] = 34;
		$fields['billing']['billing_address_2']['maxlength'] = 34;

		return $fields;

	}

	/**
	 * Get order ID
	 *
	 * @param $order
	 *
	 * @return string
	 */
	public static function ag_get_order_id( $order ) {

		// If merchant is using custom order id filter
		if( defined( 'ePDQ_custom_order_id' ) ) {
			return apply_filters( 'ePDQ_custom_order_id', $order );
		}

		// return $order->get_order_number();
		return $order->get_id();

	}

	public static function schedule_delete_old_log_files() {

		if( ! wp_next_scheduled( 'delete_old_log_files_event' ) ) {
			wp_schedule_event( strtotime( '2:00 AM' ), 'daily', 'delete_old_log_files_event' );
		}
	}

	// Function to delete old log files
	public static function delete_old_log_files() {

		$log_dir = WC_LOG_DIR;

		$files = glob( $log_dir . '/AG-WooCommerce-Barclays-ePDQ-Payment-Gateway-*.log' );

		$one_week_ago = strtotime( '-1 week' ); // Calculate the timestamp for one week ago

		foreach( $files as $file ) {
			$filename = basename( $file );
			$pattern = '/AG-WooCommerce-Barclays-ePDQ-Payment-Gateway-(\d{4}-\d{2}-\d{2})-.+\.log/';
			preg_match( $pattern, $filename, $matches );

			if( isset( $matches[1] ) ) {
				$file_date = strtotime( $matches[1] );

				if( $file_date < $one_week_ago ) {
					unlink( $file );
				}
			}
		}
	}

}
