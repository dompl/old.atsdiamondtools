<?php
/*-----------------------------------------------------------------------------------*/

/*	AG ePDQ Subscriptions
/*-----------------------------------------------------------------------------------*/

defined( 'ABSPATH' ) || die( "No script kiddies please!" );

if( class_exists( 'ePDQ_Sub' ) ) {
	return;
}

class ePDQ_Sub {

	/**
	 * @param $order
	 *
	 * @return void
	 */
	public static function ag_store_process_retry_renewal_payment( $order ) {

		if( self::can_retry_renewal_order( $order ) ) {

			$process = self::process_subscription_payment( $order->get_total(), $order );

			return $process;

		}

	}

	/**
	 * @param $renewal_order
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function can_retry_renewal_order( $renewal_order ) {

		// Check if the order is a renewal order and is failed.
		if( ! wcs_order_contains_subscription( $renewal_order ) || 'failed' !== $renewal_order->get_status() ) {
			return FALSE;
		}

		// Check if the retry limit has been reached.
		$retry_limit = wcs_get_renewal_retry_limit();
		if( $retry_limit > 0 && $renewal_order->get_meta( '_wcs_renewal_retry_count', TRUE ) >= $retry_limit ) {
			return FALSE;
		}

		// Check if the retry window has passed.
		$retry_interval = wcs_get_renewal_retry_interval();
		if( $retry_interval > 0 ) {
			$retry_date = $renewal_order->get_date_created()->add( new DateInterval( 'PT' . $retry_interval . 'S' ) );
			if( $retry_date > current_time( 'timestamp' ) ) {
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Process subscription payment.
	 *
	 * @param float $amount
	 * @param WC_Order $order
	 *
	 * @return void
	 */
	public static function process_subscription_payment( $amount, $order ) {

		$ePDQ_settings = new epdq_checkout();
		$settings = ePDQ_crypt::key_settings();
		$amount = (float) preg_replace( '#[^\d.]#', '', $order->get_total() * 100 );
		$environment_url = AG_ePDQ_Helpers::get_enviroment_url( 'orderdirect' );

		// Multi Currency
		$data_post = array();
		if( AG_ePDQ_Helpers::ag_get_order_currency( $order ) !== 'GBP' && defined( 'ePDQ_PSPID' ) ) {
			$data_post['PSPID'] = ePDQ_PSPID;
		} else {
			$data_post['PSPID'] = $ePDQ_settings->api_REFID;
		}
		$data_post['PSWD'] = $ePDQ_settings->api_password;
		$data_post['USERID'] = $ePDQ_settings->api_user;

		// New hash - replaces the WP nonce
		$hash_fields = array(
			$ePDQ_settings->api_user,
			date( 'Y:m:d' ),
			$order->get_order_number(),
			$settings['shain'],
			get_bloginfo( 'name' )
		);
		$encrypted_string = hash_hmac( 'ripemd160', implode( $hash_fields ), $settings['shain'] );

		// Get customer token
		$tokens = WC_Payment_Tokens::get_customer_tokens( $order->get_customer_id(), 'epdq_checkout' );
		if( empty( $tokens ) || ! isset( $tokens ) ) {
			AG_ePDQ_Helpers::ag_log( 'There was an issue getting the customers default token for the subscription payment for order #' . $order->get_order_number(), 'debug', 'yes' );
			$order->add_order_note( 'There was an issue getting the customers default token for the subscription payment for order #' . $order->get_order_number() );

			return;
		}

		// get default token
		$getToken = array();
		foreach( $tokens as $token ) {
			if( $token->is_default() ) {
				$getToken = array(
					'token' => $token->get_token(),
					'brand' => $token->get_card_type()
				);
			}
		}

		$data_post['ORDERID'] = $order->get_order_number();
		$data_post['COMPLUS'] = $encrypted_string;
		$data_post['AMOUNT'] = $amount;
		$data_post['CURRENCY'] = get_woocommerce_currency();
		$data_post['EMAIL'] = $order->get_billing_email();
		$data_post['OWNERADDRESS'] = substr( preg_replace( '/[^A-Za-z0-9\. -]/', '', $order->get_billing_address_1() ) . ' ' . preg_replace( '/[^A-Za-z0-9\. -]/', '', $order->get_billing_address_2() ), 0, 34 );
		$data_post['OWNERZIP'] = $order->get_billing_postcode();
		$data_post['OWNERTOWN'] = substr( preg_replace( '/[^A-Za-z0-9\. -]/', ' ', $order->get_billing_city() ), 0, 34 );
		$data_post['OWNERCTY'] = substr( preg_replace( '/[^A-Za-z0-9\. -]/', ' ', $order->get_billing_country() ), 0, 34 );
		$data_post['OWNERTELNO'] = $order->get_billing_phone();
		$data_post['REMOTE_ADDR'] = htmlspecialchars( $_SERVER['REMOTE_ADDR'], ENT_QUOTES, 'UTF-8' );
		$data_post['LANGUAGE'] = get_locale();
		$data_post['OPERATION'] = 'SAL';
		$data_post['ECI'] = '9';
		$data_post['ALIAS'] = $getToken['token'] ?? '';
		$data_post['ALIASOPERATION'] = 'BYPSP';
		$data_post['COF_INITIATOR'] = 'MIT';
		$data_post['COF_TRANSACTION'] = 'SUBSEQ';
		//$data_post['COF_RECURRING_EXPIRY']    = '20251201';
		//$data_post['COF_RECURRING_FREQUENCY'] = '031';
		$data_post['COF_SCHEDULE'] = 'SCHED';
		$data_post['BRAND'] = $getToken['brand'] ?? '';

		ksort( $data_post );
		foreach( $data_post as $key => $value ) {
			if( $value == '' ) {
				continue;
			}
			$shasign_arg[] = $key . '=' . $value;
		}
		$SHAsig = hash( ePDQ_crypt::get_sha_method(), implode( $settings['shain'], $shasign_arg ) . $settings['shain'] );
		$data_post['SHASIGN'] = $SHAsig;

		// Make API call to ePDQ
		$result = AG_ePDQ_Helpers::remote_post( $environment_url, $data_post );

		if( ! isset( $result['STATUS'] ) ) {
			return;
		}

		if( defined( 'ag_support_debug' ) ) {
			AG_ePDQ_Helpers::ag_log( '[Subscriptions] data returned' . print_r( $result, TRUE ), 'debug', $ePDQ_settings->debug );
		}

		$order_notes = array(
			'Order ID                            : ' => $result['ORDERID'] ?? '',
			'Amount                              : ' => $result['AMOUNT'] ?? '',
			'Order Currency                      : ' => $result['CURRENCY'] ?? '',
			'Payment Method                      : ' => $result['PM'] ?? '',
			'Acceptance Code Returned By Acquirer: ' => $result['ACCEPTANCE'] ?? '',
			'Payment Reference In ePDQ System    : ' => $result['PAYID'] ?? '',
			'Error Code                          : ' => $result['NCERROR'] ?? '',
			'Card Brand                          : ' => $result['BRAND'] ?? '',
			'Transaction Date                    : ' => $result['TRXDATE'] ?? '',
			'Cardholder/Customer Name            : ' => $result['CN'] ?? '',
			'Customer IP                         : ' => $result['IP'] ?? '',
			'AAV Result For Address              : ' => $result['AAVADDRESS'] ?? '',
			'Result for AAV Check                : ' => $result['AAVCHECK'] ?? '',
			'AAV Result For Postcode             : ' => $result['AAVZIP'] ?? '',
		);

		AG_ePDQ_Helpers::update_order_notes( $order, $order_notes );

		AG_ePDQ_Helpers::update_order_meta_data( $order->get_id(), $result, $order );

		// Process order data and update order status
		epdq_order::process( $result, '[Subscriptions renewal] ', $order );

	}

}