<?php
/*
 * Author: We are AG
 * Author URI: https://www.weareag.co.uk/
 * File: class-webhook.php
 * Project: AGWooCommerceBarclayePDQPaymentGateway-premium
 * -----
 * Created: 15 March 2023 15:01
 * -----
 * Version: 1.0.0
 * WC requires at least: 3.0.0
 * WC tested up to: 5.5
 * License: GPL3
*/

/*-----------------------------------------------------------------------------------*/
/*	Webhook
/*-----------------------------------------------------------------------------------*/
defined( 'ABSPATH' ) || die( "No script kiddies please!" );

if( class_exists( 'ag_epdq_webhook' ) ) {
	return;
}


class ag_epdq_webhook {


	public static function webhook() {

		header( 'HTTP/1.1 200 OK' );
		$epdq_settings = new epdq_checkout();
		$settings = ePDQ_crypt::key_settings();

		if( ! isset( $_REQUEST['COMPLUS'] ) ) {
			AG_ePDQ_Helpers::ag_log( '[Webhook] The request failed, ePDQ didn\'t send any data back. Please check the email ePDQ send you. The server most likely returned a 403 error. Please work with your hosting provider.', 'warning', $epdq_settings->debug );
			wp_die( 'Webhook - There was an issue processing the request on the website.' );
		}

		$datacheck = array();
		foreach( $_REQUEST as $key => $value ) {
			if( $value == "" ) {
				continue;
			}
			$datacheck[ AG_ePDQ_Helpers::AG_decode( $key ) ] = AG_ePDQ_Helpers::AG_decode( $value );
		}

		$nonce = AG_ePDQ_Helpers::AG_escape( $datacheck['COMPLUS'] );

		// Hash
		if( defined( 'ePDQ_custom_order_id' ) ) {
			$hash_fields = array(
				$settings['pspid'],
				date( 'Y:m:d' ),
				$datacheck['PARAMVAR'],
				$settings['shain'],
				get_bloginfo( 'name' )
			);
		} else {
			$hash_fields = array(
				$settings['pspid'],
				date( 'Y:m:d' ),
				$datacheck['PARAMVAR'],
				$settings['shain'],
				get_bloginfo( 'name' )
			);
		}

		$encrypted_string = ePDQ_crypt::ripemd_crypt( implode( $hash_fields ), $settings['shain'] );

		if( ! hash_equals( $encrypted_string, $nonce ) ) {
			AG_ePDQ_Helpers::ag_log( '[Webhook] Webhooks only work if ePDQ is set up for use with a single website. It looks like the webhook was sent for another website linked in ePDQ', 'debug', 'yes' );
			wp_die( 'Webhook - There was an issue processing the payment on the website. Please check the debug logs' );
		}

		if( ! isset( $datacheck['STATUS'] ) ) {
			AG_ePDQ_Helpers::ag_log( '[Webhook] The request failed, ePDQ didn\'t send any data back. Please check the email ePDQ send you.', 'warning', $epdq_settings->debug );
			AG_ePDQ_Helpers::ag_log( print_r( $datacheck, TRUE ), 'warning', $epdq_settings->debug );
			wp_die( 'Webhook - There was an issue processing the request on the website. Please check the debug logs' );
		}

		$result = self::decrypt_webhook( $datacheck );

		self::process( $result );

		exit;
	}

	public static function token() {

		header( 'HTTP/1.1 200 OK' );
		$epdq_settings = new epdq_checkout();

		$datacheck = array();
		foreach( $_REQUEST as $key => $value ) {
			if( $value == "" ) {
				continue;
			}
			$datacheck[ AG_ePDQ_Helpers::AG_decode( $key ) ] = AG_ePDQ_Helpers::AG_decode( $value );
		}

		if( ! isset( $datacheck['STATUS'] ) ) {
			AG_ePDQ_Helpers::ag_log( '[Webhook] The request failed, ePDQ didn\'t send any data back. Please check the email ePDQ send you.', 'warning', $epdq_settings->debug );
			AG_ePDQ_Helpers::ag_log( print_r( $datacheck, TRUE ), 'warning', $epdq_settings->debug );
			wp_die( 'Webhook - There was an issue processing the request on the website. Please check the debug logs' );
		}

		$result = self::decrypt_webhook( $datacheck );

		if( $result['STATUS'] === '5' ) {
			AG_ePDQ_Token::save( $result, get_current_user_id(), is_user_logged_in() );
			$url = wc_get_account_endpoint_url( 'payment-methods' ) . '?token_success=1';
		} else {
			$url = wc_get_account_endpoint_url( 'payment-methods' ) . '?token_success=0';
		}
		wp_redirect( $url );
		exit;

	}

	public static function decrypt_webhook( $args ) {

		$epdq_settings = new epdq_checkout();

		$SHA_check = self::SHA_check( $args );
		if( $SHA_check ) {
			// Process
			return $args;
		} else {

			AG_ePDQ_Helpers::ag_log( 'The ePDQ webhook hash sent back didn\'t match what the website had. Please check the docs https://weareag.co.uk/docs/barclays-epdq-payment-gateway/troubleshooting-barclays-epdq-payment-gateway/transaction-is-unsuccessful-due-to-a-sha-out-issue/', 'warning', $epdq_settings->debug );
			wp_die( 'Webhook - Hash fail.' );

		}

	}

	/**
	 * Check SHA data
	 *
	 * @param $datatocheck
	 *
	 * @return bool
	 */
	protected static function SHA_check( $datatocheck ) {

		$settings = ePDQ_crypt::key_settings();
		$SHA_out = $settings['shaout'];
		$origsig = $datatocheck['SHASIGN'];

		// Remove parameters before doing decryption
		unset( $datatocheck['CN'], $datatocheck['SHASIGN'], $datatocheck['wc-api'], $datatocheck['idOrder'], $datatocheck['PARAMVAR'], $datatocheck['callback'], $datatocheck['doing_wp_cron'], $datatocheck['woocs_order_emails_is_sending'], $datatocheck['q'], $datatocheck['somdn_error_logs_export_errors'], $datatocheck['inner_section'], $datatocheck['woof_parse_query'] );

		uksort( $datatocheck, 'strcasecmp' );

		// Enable deeper debugging, useful for when the ePDQ team require data to debug.
		if( defined( 'ag_support_debug' ) ) {
			$args = array(
				'AAVADDRESS' => $datatocheck['AAVADDRESS'] ?? '',
				'ACCEPTANCE' => $datatocheck['ACCEPTANCE'] ?? '',
				'COMPLUS'    => $datatocheck['COMPLUS'] ?? '',
				'NCERROR'    => $datatocheck['NCERROR'] ?? '',
				'orderID'    => $datatocheck['orderID'] ?? '',
				'PAYID'      => $datatocheck['PAYID'] ?? '',
				'STATUS'     => $datatocheck['STATUS'] ?? ''
			);
			AG_ePDQ_Helpers::ag_log( 'Debug data sent back ' . print_r( $args, TRUE ), 'debug', 'yes' );
		}

		$SHAsig = '';
		foreach( $datatocheck as $key => $value ) {
			$SHAsig .= trim( strtoupper( $key ) ) . '=' . utf8_encode( trim( $value ) ) . $SHA_out;
		}

		$SHAsig = strtoupper( hash( ePDQ_crypt::get_sha_method(), $SHAsig ) );

		if( hash_equals( $SHAsig, $origsig ) ) {
			return TRUE;
		}

		return FALSE;

	}

	public static function process( $args ) {

		global $woocommerce;

		$order = new WC_Order( $args['PARAMVAR'] );
		$epdq_settings = new epdq_checkout();

		// Catch and stop if order is already paid for or is processing.
		if( $order->has_status( array( 'processing', 'completed' ) ) ) {
			AG_ePDQ_Helpers::ag_log( 'Aborting, Order #' . $args['PARAMVAR'] . ' is already paid for.', 'debug', 'yes' );
			wp_redirect( $order->get_checkout_order_received_url() );
			exit;
		}

		// Save payment token to user
		if( $epdq_settings->token === 'yes' || ( class_exists( 'WC_Subscriptions_Order' ) && wcs_order_contains_subscription( $order ) ) ) { // @phpstan-ignore-line
			AG_ePDQ_Token::save( $args, get_current_user_id(), is_user_logged_in() );
			// Drop BIN
			unset( $args['BIN'] );
			$order->update_meta_data( 'use_saved_card', '' );
			$order->save();
		}
		// END

		$order_notes = array(
			'Order ID                            : ' => $args['orderID'] ?? '',
			'Amount                              : ' => $args['AMOUNT'] = $args['AMOUNT'] ?? '',
			'Order Currency                      : ' => $args['CURRENCY'] = $args['CURRENCY'] ?? '',
			'Payment Method                      : ' => $args['PM'] = $args['PM'] ?? '',
			'Acceptance Code Returned By Acquirer: ' => $args['ACCEPTANCE'] = $args['ACCEPTANCE'] ?? '',
			'Payment Reference In ePDQ System    : ' => $args['PAYID'] = $args['PAYID'] ?? '',
			'Error Code                          : ' => $args['NCERROR'] = $args['NCERROR'] ?? '',
			'Card Brand                          : ' => $args['BRAND'] = $args['BRAND'] ?? '',
			'Transaction Date                    : ' => $args['TRXDATE'] = $args['TRXDATE'] ?? '',
			'Cardholder/Customer Name            : ' => $args['CN'] = $args['CN'] ?? '',
			'Customer IP                         : ' => $args['IP'] = $args['IP'] ?? '',
			'AAV Result For Address              : ' => $args['AAVADDRESS'] = $args['AAVADDRESS'] ?? '',
			'Result for AAV Check                : ' => $args['AAVCheck'] = $args['AAVCheck'] ?? '',
			'AAV Result For Postcode             : ' => $args['AAVZIP'] = $args['AAVZIP'] ?? '',
		);
		AG_ePDQ_Helpers::update_order_notes( $order, $order_notes );

		unset( $args['SHASIGN'], $args['COMPLUS'], $args['CARDNO'], $args['ALIAS'] );
		AG_ePDQ_Helpers::update_order_meta_data( $args['PARAMVAR'], $args, $order );

		// Process order data and update order status
		epdq_order::process( $args, '[Webhook] ', $order );

		wp_redirect( $order->get_checkout_order_received_url() );

	}

}