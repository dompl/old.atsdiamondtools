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

		$sensitive_keys = [ 'BIN', 'ALIAS', 'ED', 'CARDNO' ];

		foreach( $meta as $meta_key => $meta_value ) {
			if( ! empty( $meta_value ) ) {
				$decoded_key = wc_clean( $meta_key );
				$decoded_value = wc_clean( $meta_value );

				// Check if the meta key is sensitive
				if( in_array( $decoded_key, $sensitive_keys, TRUE ) ) {
					$decoded_value = '[AG REDACTED]';
				}

				$order->update_meta_data( $decoded_key, $decoded_value );
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
				$order_notes[] = wc_clean( $key ) . ' ' . wc_clean( $value ) . '<br />';

			}
		}
		$data_back = implode( '', $order_notes );
		$customer_order->add_order_note( $data_back );

		return FALSE;
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


	public static function order_contains_subscription( $order_id ) {

		return function_exists( 'wcs_order_contains_subscription' ) && ( wcs_order_contains_subscription( $order_id ) || function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $order_id ) );
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
		$environment_url = '';
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
	 * Logs messages in the WooCommerce logs if the plugin's debug mode is enabled or if WP_DEBUG is set to true.
	 *
	 * This method will also redact sensitive fields (e.g. Token, Last4Digits, etc.) from the $message string
	 * before writing to the log.
	 *
	 * @param string $message The message to log.
	 * @param string $level The log level. Accepted values include 'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', and 'debug'.
	 * @param string $log Whether to log the message. Typically 'yes' or 'no'. Logs will also be written if WP_DEBUG is true.
	 *
	 * @return void
	 * @since 4.8.4
	 *
	 */
	public static function ag_log( $message, $level, $log ) {

		if( $log === 'yes' || WP_DEBUG === TRUE ) {

			$keys = [ 'BIN', 'ALIAS', 'ED', 'CARDNO', 'PSWD', 'USERID' ];

			foreach( $keys as $key ) {
				$pattern = '/(\[' . $key . '\]\s*=>\s*)(.*)/';
				$message = preg_replace( $pattern, '$1[AG REDACTED]', $message );
			}

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

		return $order->get_id();

	}

	public static function reorder_gateways_and_add_card_icons() {

		$screen = get_current_screen();
		if( $screen && 'woocommerce_page_wc-settings' === $screen->id && isset( $_GET['tab'] ) && 'checkout' === $_GET['tab'] ) :
			?>
        <style>
            .ag-card-icons .none { border: none !important; }
        </style>
            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    var basePath = '<?php echo esc_js( AG_ePDQ_server_path ); ?>';

                    window.addEventListener('load', function () {
                        var attempts = 0;
                        var maxAttempts = 20; // Approximately 5 seconds.
                        var intervalId = setInterval(function () {
                            attempts++;
                            var $container = $(".sortable-container.settings-payment-gateways__list");
                            if ($container.length) {
                                clearInterval(intervalId);
                                var targetIDs = ['epdq_checkout'];

                                // Plugin brand icons markup using the ePDQ path constant for the image URLs.
                                var PluginIcon = basePath + 'inc/assets/img/plugin-icon.png';
                                var cardIconsMarkup = '<span class="woocommerce-list__item-content">Barclaycard ePDQ: Simple, secure credit and debit card payments for your store.</span>' +
                                    '<div class="woocommerce-woopayments-payment-methods-logos ag-card-icons">' +
                                    '<svg width="64" height="40" viewBox="0 0 120 80" xmlns="http://www.w3.org/2000/svg"> <rect width="120" height="80" rx="4" fill="white"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M86.6666 44.9375L90.3238 35.0625L92.3809 44.9375H86.6666ZM100.952 52.8375L95.8086 27.1625H88.7383C86.3525 27.1625 85.7723 29.0759 85.7723 29.0759L76.1904 52.8375H82.8868L84.2269 49.0244H92.3947L93.148 52.8375H100.952Z" fill="#182E66"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M77.1866 33.5711L78.0952 28.244C78.0952 28.244 75.2896 27.1625 72.3648 27.1625C69.2031 27.1625 61.6955 28.5638 61.6955 35.3738C61.6955 41.7825 70.5071 41.8621 70.5071 45.2266C70.5071 48.5912 62.6034 47.9901 59.9955 45.8676L59.0476 51.4362C59.0476 51.4362 61.8919 52.8375 66.2397 52.8375C70.5869 52.8375 77.1467 50.5544 77.1467 44.3455C77.1467 37.8964 68.2552 37.296 68.2552 34.4921C68.2552 31.6882 74.4602 32.0484 77.1866 33.5711Z" fill="#182E66"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M54.6517 52.8375H47.6191L52.0144 27.1625H59.0477L54.6517 52.8375Z" fill="#182E66"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M42.3113 27.1625L35.9217 44.8213L35.1663 41.0185L35.167 41.0199L32.9114 29.4749C32.9114 29.4749 32.6394 27.1625 29.7324 27.1625H19.1709L19.0476 27.5966C19.0476 27.5966 22.2782 28.2669 26.057 30.5326L31.8793 52.8375H38.8617L49.5238 27.1625H42.3113Z" fill="#182E66"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M34.2857 40.9875L32.1534 29.4695C32.1534 29.4695 31.8963 27.1625 29.1482 27.1625H19.1641L19.0476 27.5955C19.0476 27.5955 23.8467 28.6432 28.4504 32.5652C32.8505 36.3145 34.2857 40.9875 34.2857 40.9875Z" fill="#182E66"/> </svg>' +
                                    '<svg width="64" height="40" viewBox="0 0 120 80" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" rx="4" fill="white"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M97.5288 54.6562V53.7384H97.289L97.0137 54.3698L96.7378 53.7384H96.498V54.6562H96.6675V53.9637L96.9257 54.5609H97.1011L97.36 53.9624V54.6562H97.5288ZM96.0111 54.6562V53.8947H96.318V53.7397H95.5361V53.8947H95.843V54.6562H96.0111Z" fill="#F79E1B"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M49.6521 58.595H70.3479V21.4044H49.6521V58.595Z" fill="#FF5F00"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M98.2675 40.0003C98.2675 53.063 87.6791 63.652 74.6171 63.652C69.0996 63.652 64.0229 61.7624 60 58.5956C65.5011 54.2646 69.0339 47.5448 69.0339 40.0003C69.0339 32.4552 65.5011 25.7354 60 21.4044C64.0229 18.2376 69.0996 16.348 74.6171 16.348C87.6791 16.348 98.2675 26.937 98.2675 40.0003Z" fill="#F79E1B"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M50.966 40.0003C50.966 32.4552 54.4988 25.7354 59.9999 21.4044C55.977 18.2376 50.9003 16.348 45.3828 16.348C32.3208 16.348 21.7324 26.937 21.7324 40.0003C21.7324 53.063 32.3208 63.652 45.3828 63.652C50.9003 63.652 55.977 61.7624 59.9999 58.5956C54.4988 54.2646 50.966 47.5448 50.966 40.0003Z" fill="#EB001B"/> </svg>' +
                                    '<svg width="64" height="40" viewBox="0 0 120 80" xmlns="http://www.w3.org/2000/svg"> <rect width="120" height="80" rx="4" fill="white"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M97.5288 54.6562V53.7384H97.289L97.0137 54.3698L96.7378 53.7384H96.498V54.6562H96.6675V53.9637L96.9257 54.5609H97.1011L97.36 53.9624V54.6562H97.5288ZM96.0111 54.6562V53.8947H96.318V53.7397H95.5361V53.8947H95.843V54.6562H96.0111Z" fill="#00A2E5"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M49.6521 58.595H70.3479V21.4044H49.6521V58.595Z" fill="#7375CF"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M98.2675 40.0003C98.2675 53.063 87.6791 63.652 74.6171 63.652C69.0996 63.652 64.0229 61.7624 60 58.5956C65.5011 54.2646 69.0339 47.5448 69.0339 40.0003C69.0339 32.4552 65.5011 25.7354 60 21.4044C64.0229 18.2376 69.0996 16.348 74.6171 16.348C87.6791 16.348 98.2675 26.937 98.2675 40.0003Z" fill="#00A2E5"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M50.966 40.0003C50.966 32.4552 54.4988 25.7354 59.9999 21.4044C55.977 18.2376 50.9003 16.348 45.3828 16.348C32.3208 16.348 21.7324 26.937 21.7324 40.0003C21.7324 53.063 32.3208 63.652 45.3828 63.652C50.9003 63.652 55.977 61.7624 59.9999 58.5956C54.4988 54.2646 50.966 47.5448 50.966 40.0003Z" fill="#EB001B"/> </svg>' +
                                    '<svg width="64" height="40" class="none" viewBox="0 0 120 80" xmlns="http://www.w3.org/2000/svg"> <rect width="120" height="80" rx="4" fill="url(#paint0_linear_804_2)"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M65.3997 64.8343C79.0213 64.8992 91.4542 53.7631 91.4542 40.2157C91.4542 25.4007 79.0213 15.1605 65.3997 15.1654H53.6768C39.8921 15.1605 28.5459 25.4038 28.5459 40.2157C28.5459 53.7661 39.8921 64.8993 53.6768 64.8343H65.3997Z" fill="#3477B9"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M53.6852 17.1522C41.0891 17.1561 30.8821 27.3313 30.8792 39.8896C30.8821 52.4456 41.089 62.6199 53.6852 62.6238C66.2843 62.6199 76.4934 52.4456 76.4952 39.8896C76.4933 27.3313 66.2843 17.1561 53.6852 17.1522ZM39.2291 39.8896C39.241 33.7529 43.0866 28.5199 48.5095 26.4404V53.3355C43.0866 51.2572 39.2409 46.0271 39.2291 39.8896ZM58.859 53.3415V26.4396C64.2838 28.514 68.1355 33.7499 68.1453 39.8896C68.1355 46.0311 64.2838 51.263 58.859 53.3415Z" fill="white"/> <defs> <linearGradient id="paint0_linear_804_2" x1="1.68141e-06" y1="21" x2="120" y2="54" gradientUnits="userSpaceOnUse"> <stop stop-color="#3479C0"/> <stop offset="1" stop-color="#133362"/> </linearGradient> </defs> </svg>' +
                                    '<svg width="64" height="40" viewBox="0 0 120 80" xmlns="http://www.w3.org/2000/svg"> <rect width="120" height="80" rx="4" fill="white"/> <path d="M100.9 58.8C100.9 65.8 95.1996 71.5 88.1996 71.5H19.0996V21.2C19.0996 14.2 24.7996 8.5 31.7996 8.5H100.9V58.8Z" fill="white"/> <path d="M78.3994 45.9H83.6494C83.7994 45.9 84.1494 45.85 84.2994 45.85C85.2994 45.65 86.1494 44.75 86.1494 43.5C86.1494 42.3 85.2994 41.4 84.2994 41.15C84.1494 41.1 83.8494 41.1 83.6494 41.1H78.3994V45.9Z" fill="url(#paint0_linear_833_6149)"/> <path d="M83.0494 12.75C78.0494 12.75 73.9494 16.8 73.9494 21.85V31.3H86.7994C87.0994 31.3 87.4494 31.3 87.6994 31.35C90.5994 31.5 92.7494 33 92.7494 35.6C92.7494 37.65 91.2994 39.4 88.5994 39.75V39.85C91.5494 40.05 93.7994 41.7 93.7994 44.25C93.7994 47 91.2994 48.8 87.9994 48.8H73.8994V67.3H87.2494C92.2494 67.3 96.3494 63.25 96.3494 58.2V12.75H83.0494Z" fill="url(#paint1_linear_833_6149)"/> <path d="M85.4994 36.2C85.4994 35 84.6494 34.2 83.6494 34.05C83.5494 34.05 83.2994 34 83.1494 34H78.3994V38.4H83.1494C83.2994 38.4 83.5994 38.4 83.6494 38.35C84.6494 38.2 85.4994 37.4 85.4994 36.2Z" fill="url(#paint2_linear_833_6149)"/> <path d="M57.8988 12.75C52.8988 12.75 48.7988 16.8 48.7988 21.85V33.75C51.0988 31.8 55.0988 30.55 61.5488 30.85C64.9988 31 68.6988 31.95 68.6988 31.95V35.8C66.8488 34.85 64.6488 34 61.7988 33.8C56.8988 33.45 53.9488 35.85 53.9488 40.05C53.9488 44.3 56.8988 46.7 61.7988 46.3C64.6488 46.1 66.8488 45.2 68.6988 44.3V48.15C68.6988 48.15 65.0488 49.1 61.5488 49.25C55.0988 49.55 51.0988 48.3 48.7988 46.35V67.35H62.1488C67.1488 67.35 71.2488 63.3 71.2488 58.25V12.75H57.8988Z" fill="url(#paint3_linear_833_6149)"/> <path d="M32.7496 12.75C27.7496 12.75 23.6496 16.8 23.6496 21.85V44.3C26.1996 45.55 28.8496 46.35 31.4996 46.35C34.6496 46.35 36.3496 44.45 36.3496 41.85V31.25H44.1496V41.8C44.1496 45.9 41.5996 49.25 32.9496 49.25C27.6996 49.25 23.5996 48.1 23.5996 48.1V67.25H36.9496C41.9496 67.25 46.0496 63.2 46.0496 58.15V12.75H32.7496Z" fill="url(#paint4_linear_833_6149)"/> <defs> <linearGradient id="paint0_linear_833_6149" x1="60.9804" y1="40.0821" x2="126.075" y2="40.0821" gradientUnits="userSpaceOnUse"> <stop stop-color="#007940"/> <stop offset="0.2285" stop-color="#00873F"/> <stop offset="0.7433" stop-color="#40A737"/> <stop offset="1" stop-color="#5CB531"/> </linearGradient> <linearGradient id="paint1_linear_833_6149" x1="73.9404" y1="40.0023" x2="96.4108" y2="40.0023" gradientUnits="userSpaceOnUse"> <stop stop-color="#007940"/> <stop offset="0.2285" stop-color="#00873F"/> <stop offset="0.7433" stop-color="#40A737"/> <stop offset="1" stop-color="#5CB531"/> </linearGradient> <linearGradient id="paint2_linear_833_6149" x1="73.9396" y1="36.1925" x2="96.409" y2="36.1925" gradientUnits="userSpaceOnUse"> <stop stop-color="#007940"/> <stop offset="0.2285" stop-color="#00873F"/> <stop offset="0.7433" stop-color="#40A737"/> <stop offset="1" stop-color="#5CB531"/> </linearGradient> <linearGradient id="paint3_linear_833_6149" x1="48.6689" y1="40.0023" x2="70.8287" y2="40.0023" gradientUnits="userSpaceOnUse"> <stop stop-color="#6C2C2F"/> <stop offset="0.1735" stop-color="#882730"/> <stop offset="0.5731" stop-color="#BE1833"/> <stop offset="0.8585" stop-color="#DC0436"/> <stop offset="1" stop-color="#E60039"/> </linearGradient> <linearGradient id="paint4_linear_833_6149" x1="23.6382" y1="40.0023" x2="46.4553" y2="40.0023" gradientUnits="userSpaceOnUse"> <stop stop-color="#1F286F"/> <stop offset="0.4751" stop-color="#004E94"/> <stop offset="0.8261" stop-color="#0066B1"/> <stop offset="1" stop-color="#006FBC"/> </linearGradient> </defs> </svg>' +
                                    '<svg width="64" height="40" class="none" viewBox="0 0 120 80" xmlns="http://www.w3.org/2000/svg"> <rect width="120" height="80" rx="4" fill="#0690FF"/> <g clip-path="url(#clip0_703_18)"> <rect x="40" width="80" height="80" rx="4" fill="white"/> <path d="M120 80V67.3237H110.349L105.38 61.8293L100.386 67.3237H68.5643V41.7163H58.2938L71.0333 12.8854H83.3194L87.7051 22.7624V12.8854H102.913L105.553 20.3283L108.211 12.8854H120V0H40V80H120ZM111.974 64.1176H120L109.384 52.8594L120 41.7291H112.102L105.546 48.8936L99.0525 41.7291H91.025L101.579 52.9234L91.025 64.1176H98.8291L105.418 56.8893L111.974 64.1176ZM113.852 52.8688L120 59.4094V46.3827L113.852 52.8688ZM78.0717 58.9363V55.4499H90.7048V50.3965H78.0717V46.9106H91.0245L91.025 41.7291H71.9626V64.1176H91.025L91.0245 58.9363H78.0717ZM113.955 38.4802H120V16.0917H110.597L105.575 30.0362L100.586 16.0917H91.0229V38.4802H97.0675V22.8083L102.825 38.4802H108.198L113.955 22.776V38.4802ZM84.1464 38.4802H91.0229L81.1405 16.0917H73.2723L63.389 38.4802H70.1056L71.961 34.0026H82.2594L84.1464 38.4802ZM80.1488 28.981H74.0715L77.1102 21.6568L80.1488 28.981Z" fill="#0690FF"/> </g> <defs> <clipPath id="clip0_703_18"> <rect x="40" width="80" height="80" rx="4" fill="white"/> </clipPath> </defs> </svg>' +
                                    '<svg width="64" height="40" viewBox="0 0 120 80" xmlns="http://www.w3.org/2000/svg"> <rect width="120" height="80" rx="4" fill="white"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M29 80H116.002C118.21 80 120 78.211 120 75.9957V48C120 48 87.8616 70.1063 29 80Z" fill="#E7792B"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M113.088 33.8624C113.088 30.7125 110.888 28.8951 107.053 28.8951H102.12V45.7197H105.443V38.9609H105.877L110.481 45.7197H114.571L109.202 38.6314C111.708 38.129 113.088 36.4383 113.088 33.8624ZM106.414 36.6411H105.443V31.5451H106.467C108.538 31.5451 109.665 32.4018 109.665 34.0385C109.665 35.7305 108.538 36.6411 106.414 36.6411Z" fill="#1A1918"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M90.4839 45.7197H99.9176V42.8713H93.8077V38.3298H99.6923V35.4802H93.8077V31.746H99.9176V28.8951H90.4839V45.7197Z" fill="#1A1918"/><path fill-rule="evenodd" clip-rule="evenodd" d="M80.7677 40.1959L76.2205 28.8951H72.5864L79.8236 46.1512H81.613L88.9799 28.8951H85.3742L80.7677 40.1959Z" fill="#1A1918"/> <path d="M64.6178 46.7197C69.7118 46.7197 73.8414 42.6454 73.8414 37.6197C73.8414 32.5939 69.7118 28.5197 64.6178 28.5197C59.5238 28.5197 55.3943 32.5939 55.3943 37.6197C55.3943 42.6454 59.5238 46.7197 64.6178 46.7197Z" fill="url(#paint0_radial_823_341)"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M41.2231 37.3191C41.2231 42.2643 45.159 46.0986 50.224 46.0986C51.6556 46.0986 52.8817 45.8211 54.3943 45.1184V41.2555C53.0642 42.5685 51.8869 43.0982 50.3788 43.0982C47.0287 43.0982 44.651 40.7017 44.651 37.2944C44.651 34.0645 47.1038 31.5165 50.224 31.5165C51.8104 31.5165 53.0115 32.0749 54.3943 33.4093V29.5483C52.9344 28.8177 51.7334 28.5148 50.3024 28.5148C45.2631 28.5148 41.2231 32.4272 41.2231 37.3191Z" fill="#1A1918"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M35.2687 35.3515C33.2725 34.6229 32.6868 34.1419 32.6868 33.2332C32.6868 32.173 33.731 31.3683 35.1646 31.3683C36.1614 31.3683 36.9803 31.772 37.8467 32.7307L39.5873 30.4824C38.157 29.248 36.446 28.6169 34.5763 28.6169C31.5589 28.6169 29.2576 30.6839 29.2576 33.4379C29.2576 35.7558 30.3295 36.9421 33.453 38.0516C34.7555 38.5047 35.4182 38.8063 35.7529 39.0097C36.417 39.4381 36.7497 40.0439 36.7497 40.7504C36.7497 42.1135 35.6515 43.1236 34.1671 43.1236C32.5807 43.1236 31.3032 42.341 30.537 40.8798L28.3879 42.9214C29.9204 45.1405 31.7611 46.124 34.2923 46.124C37.7485 46.124 40.1736 43.8568 40.1736 40.5996C40.1736 37.9268 39.0523 36.7165 35.2687 35.3515Z" fill="#1A1918"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M23.8091 28.8951H27.1355V45.7197H23.8091V28.8951Z" fill="#1A1918"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M13.1242 28.8951H8.2417V45.7197H13.0985C15.6811 45.7197 17.5456 45.1184 19.1828 43.7775C21.1283 42.1889 22.2786 39.7949 22.2786 37.319C22.2786 32.3537 18.5187 28.8951 13.1242 28.8951ZM17.01 41.5336C15.9644 42.4651 14.6073 42.8713 12.4582 42.8713H11.5655V31.746H12.4582C14.6073 31.746 15.9111 32.1249 17.01 33.1064C18.1603 34.1171 18.8521 35.683 18.8521 37.2943C18.8521 38.9096 18.1603 40.5235 17.01 41.5336Z" fill="#1A1918"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M115.21 29.5275C115.21 29.233 115.005 29.0712 114.643 29.0712H114.162V30.5499H114.52V29.9766L114.939 30.5499H115.376L114.883 29.9402C115.094 29.8843 115.21 29.7329 115.21 29.5275ZM114.58 29.7296H114.52V29.3429H114.584C114.761 29.3429 114.853 29.4059 114.853 29.5327C114.853 29.664 114.76 29.7296 114.58 29.7296Z" fill="#1A1918"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M114.715 28.5187C113.987 28.5187 113.41 29.092 113.41 29.8077C113.41 30.5233 113.994 31.0973 114.715 31.0973C115.424 31.0973 116.005 30.5175 116.005 29.8077C116.005 29.1018 115.424 28.5187 114.715 28.5187ZM114.71 30.8672C114.138 30.8672 113.669 30.3966 113.669 29.8096C113.669 29.2207 114.132 28.7508 114.71 28.7508C115.28 28.7508 115.745 29.2318 115.745 29.8096C115.745 30.3914 115.28 30.8672 114.71 30.8672Z" fill="#1A1918"/> <defs> <radialGradient id="paint0_radial_823_341" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(71.5 44) rotate(-142.431) scale(16.4012 16.1816)"> <stop stop-color="#F59900"/> <stop offset="0.210082" stop-color="#F39501"/> <stop offset="0.908163" stop-color="#CE3C0B"/> <stop offset="1" stop-color="#A4420A"/> </radialGradient> </defs> </svg>' +
                                    '</div>' +
                                    '<a href="https://weareag.co.uk/docs/barclays-epdq-payment-gateway/" target="_blank">View Documentation</a> -  <a href="https://weareag.co.uk/support/" target="_blank">Get Support</a>';

                                // Append the card icons markup after the description text in each gateway.
                                // The description is the span with the class woocommerce-list__item-content.
                                targetIDs.forEach(function (id) {
                                    var $gateway = $("#" + id);
                                    var $img = $gateway.find("img.woocommerce-list__item-image");
                                    if ($img.length) {
                                        $img.attr("src", PluginIcon);  // Replace the src with PluginIcon URL
                                    }
                                    $gateway.find("span.woocommerce-list__item-content").after(cardIconsMarkup);
                                });

                            } else if (attempts >= maxAttempts) {
                                clearInterval(intervalId);
                            }
                        }, 500);


                    });
                });



            </script>
		<?php
		endif;
	}


}
