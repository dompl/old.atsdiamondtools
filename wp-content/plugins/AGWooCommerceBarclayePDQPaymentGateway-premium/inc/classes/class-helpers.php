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
                                // Reverse the array so that prepending each yields the correct final order.
                                targetIDs.reverse().forEach(function (id) {
                                    var $target = $("#" + id);
                                    if ($target.length) {
                                        $target.prependTo($container);
                                    }
                                });
                                $("span.woocommerce-status-badge--info").remove();
                                $("img.woocommerce-list__item-image").remove();

                                // Dummy card brand icons markup using the AG_SagePay_Server_path constant for the image URLs.
                                var PluginIcon = '<img class="woocommerce-list__item-image" src="' + basePath + '/inc/assets/img/plugin-icon.png" alt="AG Trust Payments for WooCommerce logo">';
                                // The images have inline CSS to set object-fit, border, width, border-radius and margin.
                                var cardIconsMarkup = '<span class="woocommerce-list__item-content">Credit/debit cards and more.</span>' +
                                    '<div class="woocommerce-woopayments-payment-methods-logos">' +
                                    '<svg width="64" height="40" viewBox="0 0 64 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="64" height="40" rx="3" fill="white"></rect><path d="M28.6564 27.2545H24.7949L27.2102 13.2538H31.0714L28.6564 27.2545Z" fill="#1C34C3"></path><path d="M42.6537 13.5961C41.8921 13.3128 40.684 13 39.1903 13C35.3769 13 32.6916 14.9064 32.6751 17.6319C32.6435 19.6428 34.5977 20.7597 36.0594 21.4302C37.5534 22.1154 38.0612 22.5626 38.0612 23.1733C38.046 24.1112 36.854 24.5436 35.7422 24.5436C34.2006 24.5436 33.3745 24.3207 32.1191 23.7989L31.6107 23.5752L31.0703 26.718C31.976 27.1048 33.6446 27.4481 35.3769 27.4631C39.4287 27.4631 42.0665 25.5863 42.0977 22.6818C42.1131 21.088 41.0812 19.8667 38.8564 18.8688C37.5058 18.2282 36.6787 17.7962 36.6787 17.1408C36.6946 16.5449 37.3783 15.9346 38.9029 15.9346C40.1582 15.9047 41.0806 16.1876 41.7793 16.4707L42.1286 16.6194L42.6537 13.5961Z" fill="#1C34C3"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M49.5665 13.2538H52.5534L55.6686 27.2543H52.0933C52.0933 27.2543 51.7434 25.6456 51.6325 25.1541H46.6747C46.5313 25.5263 45.8642 27.2543 45.8642 27.2543H41.8125L47.5482 14.4154C47.9456 13.5068 48.6454 13.2538 49.5665 13.2538ZM49.3285 18.3773C49.3285 18.3773 48.1049 21.4902 47.7869 22.2945H50.9965C50.8377 21.5945 50.1064 18.2432 50.1064 18.2432L49.8366 17.0368C49.7229 17.3475 49.5586 17.7746 49.4478 18.0626C49.3726 18.2579 49.322 18.3893 49.3285 18.3773Z" fill="#1C34C3"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M8.06356 13.2538H14.2763C15.1184 13.2833 15.8017 13.5365 16.0241 14.4307L17.3743 20.8634C17.3744 20.8638 17.3745 20.8643 17.3747 20.8647L17.7879 22.8009L21.5696 13.2538H25.6528L19.5832 27.2396H15.4998L12.058 15.0743C10.8705 14.4234 9.51527 13.8999 8 13.5367L8.06356 13.2538Z" fill="#1C34C3"></path></svg>' +
                                    '<svg width="64" height="40" viewBox="0 0 64 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="64" height="40" fill="white"></rect><g clip-path="url(#clip0_1_132)"><path d="M37.376 10.284H26.56V29.716H37.376V10.284Z" fill="#FF5A00"></path><path d="M27.28 20C27.28 16.052 29.136 12.548 31.984 10.284C29.888 8.636 27.244 7.64 24.36 7.64C17.528 7.64 12 13.168 12 20C12 26.832 17.528 32.36 24.36 32.36C27.244 32.36 29.888 31.364 31.984 29.716C29.132 27.484 27.28 23.948 27.28 20Z" fill="#EB001B"></path><path d="M51.968 20C51.968 26.832 46.44 32.36 39.608 32.36C36.724 32.36 34.08 31.364 31.984 29.716C34.868 27.448 36.688 23.948 36.688 20C36.688 16.052 34.832 12.548 31.984 10.284C34.076 8.636 36.72 7.64 39.604 7.64C46.44 7.64 51.968 13.204 51.968 20Z" fill="#F79E1B"></path></g><defs><clipPath id="clip0_1_132"><rect width="40" height="24.72" fill="white" transform="translate(12 7.64)"></rect></clipPath></defs></svg>' +
                                    '<svg xmlns="http://www.w3.org/2000/svg" width="146.776" height="120.641" viewBox="0 0 146.776 120.641"><rect width="146.776" height="120.641" style="fill: none"/><path d="M372.97079,350.9185v-5.9914a3.55354,3.55354,0,0,0-3.7545-3.7945,3.69772,3.69772,0,0,0-3.3551,1.6976,3.50632,3.50632,0,0,0-3.1555-1.6976,3.15709,3.15709,0,0,0-2.7959,1.418v-1.1783h-2.0771v9.5462h2.097v-5.2923a2.23259,2.23259,0,0,1,2.3366-2.5362c1.378,0,2.0771.8986,2.0771,2.5162v5.3123h2.097v-5.2923A2.25125,2.25125,0,0,1,368.777,343.09c1.41779,0,2.09689.8986,2.09689,2.5162v5.3123Zm11.70321-4.7732v-4.773h-2.07711v1.1583a3.62159,3.62159,0,0,0-3.0157-1.398,5.01888,5.01888,0,0,0,0,10.0257,3.62139,3.62139,0,0,0,3.0157-1.3981v1.1583H384.674Zm-7.72881,0a2.89047,2.89047,0,1,1,2.8957,3.0556A2.86354,2.86354,0,0,1,376.94519,346.1453Zm52.1715-5.0239a5.3805,5.3805,0,0,1,2.0486.3839,4.99816,4.99816,0,0,1,1.6296,1.0566,4.83745,4.83745,0,0,1,1.0766,1.5899,5.30278,5.30278,0,0,1,0,3.9871,4.83354,4.83354,0,0,1-1.0766,1.5902,4.99443,4.99443,0,0,1-1.6296,1.0564,5.65627,5.65627,0,0,1-4.0971,0,4.94,4.94,0,0,1-1.6249-1.0564,4.87929,4.87929,0,0,1-1.0716-1.5902,5.30784,5.30784,0,0,1,0-3.9871,4.88328,4.88328,0,0,1,1.0716-1.5899,4.9437,4.9437,0,0,1,1.6249-1.0566A5.38,5.38,0,0,1,429.11669,341.1214Zm0,1.9639a3.09937,3.09937,0,0,0-1.186.2243,2.80562,2.80562,0,0,0-.947.6278,2.927,2.927,0,0,0-.6281.9671,3.47314,3.47314,0,0,0,0,2.4821,2.924,2.924,0,0,0,.6281.9669,2.80464,2.80464,0,0,0,.947.628,3.24794,3.24794,0,0,0,2.3721,0,2.86167,2.86167,0,0,0,.952-.628,2.89613,2.89613,0,0,0,.63321-.9669,3.47328,3.47328,0,0,0,0-2.4821,2.899,2.899,0,0,0-.63321-.9671,2.86269,2.86269,0,0,0-.952-.6278A3.09988,3.09988,0,0,0,429.11669,343.0853Zm-33.1409,3.06c-.0199-2.9756-1.8573-5.0127-4.5334-5.0127a5.017,5.017,0,0,0,.1398,10.0256,5.8025,5.8025,0,0,0,3.9143-1.3381l-1.01849-1.5378a4.54855,4.54855,0,0,1-2.776.9985,2.65136,2.65136,0,0,1-2.856-2.3366h7.08989C395.95589,346.6846,395.97579,346.4251,395.97579,346.1453Zm-7.1097-.8387a2.45767,2.45767,0,0,1,2.5363-2.3166,2.37115,2.37115,0,0,1,2.41661,2.3166Zm15.8566-1.4977a6.15314,6.15314,0,0,0-2.9756-.8189c-1.1385,0-1.8174.4194-1.8174,1.1185,0,.6391.7189.8186,1.6177.9384l.9785.1398c2.07711.2996,3.33511,1.1783,3.33511,2.8559,0,1.8174-1.59761,3.1157-4.35361,3.1157a6.887,6.887,0,0,1-4.134-1.2385l.9785-1.6175a5.08619,5.08619,0,0,0,3.1755.9986c1.4178,0,2.1768-.4193,2.1768-1.1584,0-.5392-.5393-.8388-1.6775-.9985l-.97869-.1397c-2.13681-.2997-3.29511-1.2583-3.29511-2.8161,0-1.8973,1.5576-3.0556,3.9742-3.0556a7.138,7.138,0,0,1,3.8944.9987Zm9.9801-.5393h-3.395v4.3139c0,.9584.3395,1.5976,1.378,1.5976a3.83592,3.83592,0,0,0,1.8374-.5392l.599,1.7775a4.87991,4.87991,0,0,1-2.59619.7389c-2.45641,0-3.31511-1.3181-3.31511-3.5351v-4.3536h-1.9373v-1.8973h1.9373l-.0006-2.8957h2.0969l.0006,2.8957h3.395Zm7.188-2.137a4.43589,4.43589,0,0,1,1.478.2598l-.6391,1.9571a3.38388,3.38388,0,0,0-1.2981-.2397c-1.3581,0-2.0372.8788-2.0372,2.4564v5.3523h-2.0769v-9.5462h2.0571v1.1583a2.81526,2.81526,0,0,1,2.5162-1.398ZM436.27,349.5289a.9539.9539,0,0,1,.37619.0746.97775.97775,0,0,1,.3074.2037.95657.95657,0,0,1,.2071.3028.93363.93363,0,0,1,0,.737.96452.96452,0,0,1-.2071.3015.9964.9964,0,0,1-.3074.2048.93912.93912,0,0,1-.37619.0758.96809.96809,0,0,1-.89761-.5821.9359.9359,0,0,1,0-.737.96233.96233,0,0,1,.2062-.3028.95074.95074,0,0,1,.3085-.2037A.98158.98158,0,0,1,436.27,349.5289Zm0,1.6906a.70737.70737,0,0,0,.28659-.0582.75391.75391,0,0,0,.2327-.1583.74009.74009,0,0,0-.2327-1.205.7152.7152,0,0,0-.28659-.0572.74573.74573,0,0,0-.29221.0572.7297.7297,0,0,0-.2363.1569.74456.74456,0,0,0,0,1.0481.74452.74452,0,0,0,.52851.2165Zm.05589-1.1854a.40124.40124,0,0,1,.26211.0759.25268.25268,0,0,1,.09189.206.23989.23989,0,0,1-.0733.1803.349.349,0,0,1-.2085.0874l.2888.333h-.2258l-.2678-.3307h-.0862v.3307h-.1886v-.8826Zm-.2188.1655v.2352h.2166a.21318.21318,0,0,0,.1188-.0293.09949.09949,0,0,0,.0441-.0895.09807.09807,0,0,0-.0441-.0875.21492.21492,0,0,0-.1188-.0289Z" transform="translate(-322.61159 -245.6795)"/><g><rect x="57.6379" y="22.8343" width="31.5" height="56.6064" style="fill: #7375cf"/><path d="M382.24969,296.817a35.93765,35.93765,0,0,1,13.7499-28.3032,36,36,0,1,0,0,56.6064A35.9378,35.9378,0,0,1,382.24969,296.817Z" transform="translate(-322.61159 -245.6795)" style="fill: #eb001b"/><path d="M450.81019,319.1248v-1.1589h.4673v-.2361h-1.1901v.2361h.4675v1.1589Zm2.3105,0v-1.3973h-.3648l-.4196.9611-.4197-.9611h-.365v1.3973h.2576v-1.054l.3935.9087h.2671l.3935-.911v1.0563Z" transform="translate(-322.61159 -245.6795)" style="fill: #00a2e5"/><path d="M454.24479,296.817a35.99867,35.99867,0,0,1-58.2452,28.3032,36.00518,36.00518,0,0,0,0-56.6064,35.99867,35.99867,0,0,1,58.2452,28.3032Z" transform="translate(-322.61159 -245.6795)" style="fill: #00a2e5"/></g></g></g></svg>' +
                                    '<svg width="800px" height="800px" style="background: rgb(0, 121, 190);" viewBox="0 -140 780 780" enable-background="new 0 0 780 500" version="1.1" xml:space="preserve" xmlns="http://www.w3.org/2000/svg"><path d="M40,0h700c22.092,0,40,17.909,40,40v420c0,22.092-17.908,40-40,40H40c-22.091,0-40-17.908-40-40V40   C0,17.909,17.909,0,40,0z" fill="#0079BE"/><path d="m599.93 251.45c0-99.415-82.98-168.13-173.9-168.1h-78.242c-92.003-0.033-167.73 68.705-167.73 168.1 0 90.93 75.727 165.64 167.73 165.2h78.242c90.914 0.436 173.9-74.294 173.9-165.2z" fill="#ffffff"/><path d="m348.28 97.43c-84.07 0.027-152.19 68.308-152.21 152.58 0.02 84.258 68.144 152.53 152.21 152.56 84.09-0.027 152.23-68.303 152.24-152.56-0.011-84.272-68.149-152.55-152.24-152.58z" fill="#0079BE"/><path d="m252.07 249.6c0.08-41.181 25.746-76.297 61.94-90.25v180.48c-36.194-13.948-61.861-49.045-61.94-90.23zm131 90.274v-180.53c36.207 13.92 61.914 49.057 61.979 90.257-0.065 41.212-25.772 76.322-61.979 90.269z" fill="#ffffff"/></svg>' +
                                    '<svg width="800px" height="800px" style="background: rgb(14, 76, 150);" viewBox="0 -139.5 750 750" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><linearGradient x1="0.031607858%" y1="49.9998574%" x2="99.9743153%" y2="49.9998574%" id="linearGradient-1"><stop stop-color="#007B40" offset="0%"></stop><stop stop-color="#55B330" offset="100%"></stop></linearGradient><linearGradient x1="0.471693172%" y1="49.999826%" x2="99.9860086%" y2="49.999826%" id="linearGradient-2"><stop stop-color="#1D2970" offset="0%"></stop><stop stop-color="#006DBA" offset="100%"></stop></linearGradient><linearGradient x1="0.113880772%" y1="50.0008964%" x2="99.9860003%" y2="50.0008964%" id="linearGradient-3"><stop stop-color="#6E2B2F" offset="0%"></stop><stop stop-color="#E30138" offset="100%"></stop></linearGradient></defs><g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g id="jcb" fill-rule="nonzero"><rect id="Rectangle-1" fill="#0E4C96" x="0" y="0" width="750" height="471" rx="40"></rect><path d="M617.243183,346.766281 C617.243183,388.380887 583.514892,422.125974 541.88349,422.125974 L132.756823,422.125974 L132.756823,124.244916 C132.756823,82.6186826 166.489851,48.8744567 208.121683,48.8744567 L617.243183,48.874026 L617.242752,346.766281 L617.243183,346.766281 Z" id="path3494" fill="#FFFFFF"></path><path d="M483.858874,242.044797 C495.542699,242.298285 507.296188,241.528806 518.936004,242.444883 C530.723244,244.645678 533.563915,262.487874 523.09234,268.332511 C515.950746,272.182115 507.459496,269.764696 499.713328,270.446208 L483.858874,270.446208 L483.858874,242.044797 Z M525.691826,209.900487 C528.288491,219.064679 519.453903,227.292118 510.625917,226.030566 L483.858874,226.030566 C484.043758,217.388441 483.491345,208.008973 484.131053,199.821663 C494.854942,200.123386 505.679576,199.205849 516.340394,200.301853 C520.921799,201.451558 524.753935,205.217712 525.691826,209.900487 Z M590.120412,73.9972254 C590.617872,91.498454 590.191471,109.92365 590.33359,127.780192 C590.299137,200.376358 590.405942,272.974174 590.278896,345.569303 C589.81042,372.776592 565.696524,396.413678 538.678749,396.956694 C511.63292,397.068451 484.584297,396.972628 457.537396,397.004497 L457.537396,287.253291 C487.007,287.099803 516.49604,287.561 545.953521,287.021594 C559.62072,286.162769 574.586027,277.145695 575.22328,262.107374 C576.833661,247.005483 562.592128,236.557185 549.071096,234.905684 C543.872773,234.770542 544.027132,233.390846 549.071096,232.788972 C561.96307,230.002483 572.090675,216.655787 568.296786,203.290229 C565.06052,189.232374 549.523839,183.79142 536.600366,183.817768 C510.248548,183.638612 483.891299,183.792359 457.537396,183.74111 C457.708585,163.252408 457.182916,142.740653 457.82271,122.267364 C459.910361,95.5513766 484.628603,73.5195319 511.269759,73.997656 C537.553166,73.9973692 563.837737,73.9982301 590.120412,73.9972254 Z" id="path3496" fill="url(#linearGradient-1)"></path><path d="M159.740429,125.040498 C160.413689,97.8766592 184.628619,74.4290299 211.614797,74.0325398 C238.559493,73.9499686 265.506204,74.0209119 292.451671,73.9972254 C292.37764,164.882488 292.599905,255.773672 292.340301,346.655222 C291.302298,373.488802 267.350548,396.488661 240.661356,396.962292 C213.665015,397.060957 186.666275,396.976074 159.669012,397.004497 L159.669012,283.550875 C185.891623,289.745491 213.391138,292.382518 240.142406,288.272242 C256.134509,285.697368 273.629935,277.848026 279.044261,261.257567 C283.030122,247.066267 280.785723,232.131602 281.378027,217.566465 L281.378027,183.741541 L235.081246,183.741541 C234.873106,206.112145 235.507258,228.522447 234.746146,250.867107 C233.49785,264.601214 219.900147,273.326996 206.946428,272.861801 C190.879747,273.030535 159.04755,261.221796 159.04755,261.221796 C158.967492,219.3048 159.514314,166.814385 159.740429,125.040498 Z" id="path3498" fill="url(#linearGradient-2)"></path><path d="M309.719995,197.390136 C307.285788,197.90738 309.229141,189.089459 308.606298,185.743964 C308.772233,164.593637 308.260045,143.420951 308.889718,122.285827 C310.972541,95.4570827 335.881262,73.3701105 362.628748,73.997656 L441.39456,73.997656 C441.320658,164.882346 441.542493,255.77294 441.283406,346.653934 C440.244412,373.488027 416.291344,396.487102 389.602087,396.962292 C362.604605,397.061991 335.604707,396.976504 308.606298,397.004928 L308.606298,272.707624 C327.04641,287.835846 352.105738,290.192248 375.077953,290.233484 C392.39501,290.227455 409.611861,287.557865 426.428143,283.562934 L426.428143,260.790297 C407.474658,270.236609 385.194808,276.235815 364.184745,270.807966 C349.529051,267.157367 338.89089,252.996683 339.128513,237.872204 C337.43001,222.143684 346.652631,205.536885 362.110237,200.860855 C381.300923,194.852545 402.217787,199.448454 420.206344,207.258795 C424.060526,209.27695 427.97066,211.780342 426.428143,205.338044 L426.428143,187.438358 C396.343581,180.280951 364.326644,177.646405 334.099438,185.433619 C325.351193,187.901774 316.82819,191.644647 309.719995,197.390136 Z" id="path3500" fill="url(#linearGradient-3)"></path></g></g></svg>' +
                                    '<svg viewBox="0 -9 58 58" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <rect x="0.5" y="0.5" width="57" height="39" rx="3.5" fill="#006FCF" stroke="#F3F3F3"></rect> <path fill-rule="evenodd" clip-rule="evenodd" d="M11.8632 28.8937V20.6592H21.1869L22.1872 21.8787L23.2206 20.6592H57.0632V28.3258C57.0632 28.3258 56.1782 28.8855 55.1546 28.8937H36.4152L35.2874 27.5957V28.8937H31.5916V26.6779C31.5916 26.6779 31.0867 26.9872 29.9953 26.9872H28.7373V28.8937H23.1415L22.1426 27.6481L21.1284 28.8937H11.8632ZM1 14.4529L3.09775 9.86914H6.7256L7.9161 12.4368V9.86914H12.4258L13.1346 11.7249L13.8216 9.86914H34.0657V10.8021C34.0657 10.8021 35.1299 9.86914 36.8789 9.86914L43.4474 9.89066L44.6173 12.4247V9.86914H48.3913L49.43 11.3247V9.86914H53.2386V18.1037H49.43L48.4346 16.6434V18.1037H42.8898L42.3321 16.8056H40.8415L40.293 18.1037H36.5327C35.0277 18.1037 34.0657 17.1897 34.0657 17.1897V18.1037H28.3961L27.2708 16.8056V18.1037H6.18816L5.63093 16.8056H4.14505L3.59176 18.1037H1V14.4529ZM1.01082 17.05L3.84023 10.8843H5.98528L8.81199 17.05H6.92932L6.40997 15.8154H3.37498L2.85291 17.05H1.01082ZM5.81217 14.4768L4.88706 12.3192L3.95925 14.4768H5.81217ZM9.00675 17.049V10.8832L11.6245 10.8924L13.147 14.8676L14.6331 10.8832H17.2299V17.049H15.5853V12.5058L13.8419 17.049H12.3996L10.6514 12.5058V17.049H9.00675ZM18.3552 17.049V10.8832H23.7219V12.2624H20.0171V13.3171H23.6353V14.6151H20.0171V15.7104H23.7219V17.049H18.3552ZM24.674 17.05V10.8843H28.3339C29.5465 10.8843 30.6331 11.5871 30.6331 12.8846C30.6331 13.9938 29.717 14.7082 28.8289 14.7784L30.9929 17.05H28.9831L27.0111 14.8596H26.3186V17.05H24.674ZM28.1986 12.2635H26.3186V13.5615H28.223C28.5526 13.5615 28.9776 13.3221 28.9776 12.9125C28.9776 12.5941 28.6496 12.2635 28.1986 12.2635ZM32.9837 17.049H31.3045V10.8832H32.9837V17.049ZM36.9655 17.049H36.603C34.8492 17.049 33.7844 15.754 33.7844 13.9915C33.7844 12.1854 34.8373 10.8832 37.052 10.8832H38.8698V12.3436H36.9856C36.0865 12.3436 35.4507 13.0012 35.4507 14.0067C35.4507 15.2008 36.1777 15.7023 37.2251 15.7023H37.6579L36.9655 17.049ZM37.7147 17.05L40.5441 10.8843H42.6892L45.5159 17.05H43.6332L43.1139 15.8154H40.0789L39.5568 17.05H37.7147ZM42.5161 14.4768L41.591 12.3192L40.6632 14.4768H42.5161ZM45.708 17.049V10.8832H47.7989L50.4687 14.7571V10.8832H52.1134V17.049H50.09L47.3526 13.0737V17.049H45.708ZM12.9885 27.8391V21.6733H18.3552V23.0525H14.6504V24.1072H18.2686V25.4052H14.6504V26.5005H18.3552V27.8391H12.9885ZM39.2853 27.8391V21.6733H44.6519V23.0525H40.9472V24.1072H44.5481V25.4052H40.9472V26.5005H44.6519V27.8391H39.2853ZM18.5635 27.8391L21.1765 24.7942L18.5012 21.6733H20.5733L22.1665 23.6026L23.7651 21.6733H25.756L23.1159 24.7562L25.7338 27.8391H23.6621L22.1151 25.9402L20.6057 27.8391H18.5635ZM25.9291 27.8401V21.6744H29.5619C31.0525 21.6744 31.9234 22.5748 31.9234 23.7482C31.9234 25.1647 30.8131 25.893 29.3482 25.893H27.617V27.8401H25.9291ZM29.4402 23.0687H27.617V24.4885H29.4348C29.9151 24.4885 30.2517 24.1901 30.2517 23.7786C30.2517 23.3406 29.9134 23.0687 29.4402 23.0687ZM32.6375 27.8391V21.6733H36.2973C37.51 21.6733 38.5966 22.3761 38.5966 23.6736C38.5966 24.7828 37.6805 25.4972 36.7923 25.5675L38.9563 27.8391H36.9465L34.9746 25.6486H34.2821V27.8391H32.6375ZM36.1621 23.0525H34.2821V24.3505H36.1864C36.5161 24.3505 36.9411 24.1112 36.9411 23.7015C36.9411 23.3831 36.6131 23.0525 36.1621 23.0525ZM45.4137 27.8391V26.5005H48.7051C49.1921 26.5005 49.403 26.2538 49.403 25.9833C49.403 25.7241 49.1928 25.462 48.7051 25.462H47.2177C45.9249 25.462 45.2048 24.7237 45.2048 23.6153C45.2048 22.6267 45.8642 21.6733 47.7854 21.6733H50.9881L50.2956 23.0606H47.5257C46.9962 23.0606 46.8332 23.321 46.8332 23.5697C46.8332 23.8253 47.0347 24.1072 47.4392 24.1072H48.9972C50.4384 24.1072 51.0638 24.8734 51.0638 25.8768C51.0638 26.9555 50.367 27.8391 48.9188 27.8391H45.4137ZM51.2088 27.8391V26.5005H54.5002C54.9873 26.5005 55.1981 26.2538 55.1981 25.9833C55.1981 25.7241 54.9879 25.462 54.5002 25.462H53.0129C51.72 25.462 51 24.7237 51 23.6153C51 22.6267 51.6594 21.6733 53.5806 21.6733H56.7833L56.0908 23.0606H53.3209C52.7914 23.0606 52.6284 23.321 52.6284 23.5697C52.6284 23.8253 52.8298 24.1072 53.2343 24.1072H54.7924C56.2336 24.1072 56.859 24.8734 56.859 25.8768C56.859 26.9555 56.1621 27.8391 54.7139 27.8391H51.2088Z" fill="white"></path> </g></svg>' +
                                    '<svg viewBox="0 -140 780 780" xmlns="http://www.w3.org/2000/svg" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g fill-rule="evenodd"> <path d="M54.992 0C0 0 0 0 0 0v0C0 0 0 0 0 501h670.016C755.373 501 780 476.37 780 445.996V0C0 0 755.381 0 725.008 0H54.992z" fill="#4D4D4D"></path> <path d="M327.152 161.893c8.837 0 16.248 1.784 25.268 6.09v22.751c-8.544-7.863-15.955-11.154-25.756-11.154-19.264 0-34.414 15.015-34.414 34.05 0 20.075 14.681 34.196 35.37 34.196 9.312 0 16.586-3.12 24.8-10.857v22.763c-9.341 4.14-16.911 5.776-25.756 5.776-31.278 0-55.582-22.596-55.582-51.737 0-28.826 24.951-51.878 56.07-51.878zm-97.113.627c11.546 0 22.11 3.72 30.943 10.994l-10.748 13.248c-5.35-5.646-10.41-8.028-16.564-8.028-8.853 0-15.3 4.745-15.3 10.989 0 5.354 3.619 8.188 15.944 12.482 23.365 8.044 30.29 15.176 30.29 30.926 0 19.193-14.976 32.553-36.32 32.553-15.63 0-26.994-5.795-36.458-18.872l13.268-12.03c4.73 8.61 12.622 13.222 22.42 13.222 9.163 0 15.947-5.952 15.947-13.984 0-4.164-2.055-7.734-6.158-10.258-2.066-1.195-6.158-2.977-14.2-5.647-19.291-6.538-25.91-13.527-25.91-27.185 0-16.225 14.214-28.41 32.846-28.41zm234.723 1.728h22.437l28.084 66.592 28.446-66.592h22.267l-45.494 101.686h-11.053l-44.687-101.686zm-397.348.152h30.15c33.312 0 56.534 20.382 56.534 49.641 0 14.59-7.104 28.696-19.118 38.057-10.108 7.901-21.626 11.445-37.574 11.445H67.414V164.4zm96.135 0h20.54v99.143h-20.54V164.4zm411.734 0h58.252v16.8H595.81v22.005h36.336v16.791h-36.336v26.762h37.726v16.785h-58.252V164.4zm71.858 0h30.455c23.69 0 37.265 10.71 37.265 29.272 0 15.18-8.514 25.14-23.986 28.105l33.148 41.766h-25.26l-28.429-39.828h-2.678v39.828h-20.515V164.4zm20.515 15.616v30.025h6.002c13.117 0 20.069-5.362 20.069-15.328 0-9.648-6.954-14.697-19.745-14.697h-6.326zM87.94 181.199v65.559h5.512c13.273 0 21.656-2.394 28.11-7.88 7.103-5.955 11.376-15.465 11.376-24.98 0-9.499-4.273-18.725-11.376-24.681-6.785-5.78-14.837-8.018-28.11-8.018H87.94z" fill="#FFF"></path> <path d="M415.13 161.213c30.941 0 56.022 23.58 56.022 52.709v.033c0 29.13-25.081 52.742-56.021 52.742s-56.022-23.613-56.022-52.742v-.033c0-29.13 25.082-52.71 56.022-52.71zM779.983 288.36c-26.05 18.33-221.077 149.34-558.754 212.623H724.99c30.365 0 54.992-0 54.992-0V0z" fill="#F47216"></path> </g> </g></svg>' +
                                    '</div>';

                                // Append the card icons markup after the description text in each gateway.
                                // The description is the span with the class woocommerce-list__item-content.
                                targetIDs.forEach(function (id) {
                                    var $gateway = $("#" + id);
                                    $gateway.find("div.drag-handle-wrapper").after(PluginIcon);
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
