<?php
/*
 * Author: We are AG
 * Author URI: https://www.weareag.co.uk/
 * File: class-tokenization.php
 * Project: ag-ePDQ-hosted-checkout-for-woocommerce
 * -----
 * Created: 15 March 2022 12:54 PM
 * -----
 * Version: 1.0.0
 * WC requires at least: 3.0.0
 * WC tested up to: 5.5
 * License: GPL3
*/

/*-----------------------------------------------------------------------------------*/
/*	AG Tokenization
/*-----------------------------------------------------------------------------------*/
defined( 'ABSPATH' ) || die( "No script kiddies please!" );

if( class_exists( 'AG_ePDQ_Token' ) ) {
	return;
}


class AG_ePDQ_Token {

	private static $test_url = 'https://mdepayments.epdq.co.uk/ncol/test/orderstandard.asp';
	private static $live_url = 'https://payments.epdq.co.uk/ncol/prod/orderstandard.asp';

	public static function save( $args, $userID, $login ) {

		if( empty( $args['ALIAS'] ) ) {
			return;
		}
		$order = new WC_Order( $args['orderID'] );
		//$customer_id = $order->get_user_id();

		// Catch and stop if order is already paid for or is processing.
		if( $order->has_status( array( 'processing', 'completed', 'on-hold', 'failed' ) ) ) {
			AG_ePDQ_Helpers::ag_log( 'Aborting, token save for #' . $args['orderID'] . ' is already paid for.', 'debug', 'yes' );
			wp_redirect( $order->get_checkout_order_received_url() );
			exit;
		}

		// Used token before
		$savedCard = $order->get_meta( 'use_saved_card' );
		$customerToken = self::check( $login, $savedCard );
		if( isset( $customerToken ) ) {
			$middle = strlen( $args['ED'] ) / 2;
			$brand = $args['BRAND'];

			// Customer token already stored, change default to just used card.
			if( isset( $savedCard ) && $customerToken->get_last4() === substr( $args['CARDNO'], - 4 ) && $customerToken->get_expiry_month() === substr( $args['ED'], 0, $middle ) && $customerToken->get_expiry_year() === '20' . substr( $args['ED'], 2, $middle ) && $brand === $customerToken->get_card_type() ) {
				$token = WC_Payment_Tokens::get( $savedCard );
				$token->set_default( TRUE );
				$token->save();

				return;
			}

		}

		// Check that brand is supported Woo brand for token
		$brand = $args['BRAND'];
		if( ! in_array( $brand, array( 'american express', 'jcb', 'MasterCard', 'VISA' ) ) ) {
			AG_ePDQ_Helpers::ag_log( 'The card brand selected is not supported for token right now.', 'debug', 'yes' );

			return;
		}

		// Build new token
		$middle = strlen( $args['ED'] ) / 2;

		$token = new WC_Payment_Token_CC();
		$token->set_token( $args['ALIAS'] );
		$token->set_gateway_id( 'epdq_checkout' );
		$token->set_last4( substr( $args['CARDNO'], - 4 ) );
		$token->set_expiry_year( '20' . substr( $args['ED'], 2, $middle ) );
		$token->set_expiry_month( substr( $args['ED'], 0, $middle ) );
		$token->set_card_type( $brand );
		$token->set_user_id( $userID );
		// Save the new token to the database
		$token->save();
		AG_ePDQ_Helpers::ag_log( 'Token saved.', 'debug', 'yes' );
		// Set this token as the users new default token
		WC_Payment_Tokens::set_users_default( $userID, $token->get_id() );

	}

	public static function check( $login, $savedCard ) {

		if( $login === FALSE || empty( $savedCard ) ) {
			return NULL;
		}

		return WC_Payment_Tokens::get( $savedCard );
	}

	public static function get( $userID, $login, $savedCard ) {

		if( empty( $userID ) || $login === FALSE || empty( $savedCard ) ) {
			return NULL;
		}

		$token = WC_Payment_Tokens::get( $savedCard );

		$customerToken = array( 'token' => NULL, 'brand' => NULL );
		if( $token ) {
			$customerToken['token'] = $token->get_token();
			$customerToken['brand'] = $token->get_card_type();
		}

		return $customerToken;
	}

	public static function selectSavedCards( $userID, $login ) {

		$tokens = WC_Payment_Tokens::get_customer_tokens( $userID, 'epdq_checkout' );

		if( $tokens ) {

			return '<div class="ag-select-cards">
				<p>' . __( 'Your credit cards: ', 'ag_epdq_server' ) . '</p>
				<ul class="card-list">
					' . self::displayCards( $userID, $login, $tokens ) . '
					<li><input type="radio" id="ag_cards" name="saved_cards" value="0"><label for="saved_cards"><p><strong>' . __( 'Use a new card', 'ag_epdq_server' ) . '</strong></p></label></li>
				</ul>
			</div>';

		} else {

			return '<div class="ag-select-cards savecard">
      				<label for="saveCard"><strong>Save your card details for future payments</strong></label>
					<input type="checkbox" id="saveCard" name="saveCard">
				</div>';
		}

	}

	public static function displayCards( $userID, $login, $tokens ) {

		if( empty( $userID ) || $login === FALSE || empty( $tokens ) ) {
			return NULL;
		}

		$getCards = array();
		$defaultCardTemplate = '<li><input type="radio" id="cards-1" name="saved_cards" value="%1$s"><p>%2$s %3$s ending in %4$s %5$s</p></li>';
		$nonDefaultCardTemplate = '<li><input type="radio" id="cards" name="saved_cards" value="%1$s"><p>%2$s %3$s ending in %4$s %5$s</p></li>';

		foreach( $tokens as $token ) {
			$cardType = strtolower( $token->get_card_type() );
			$cardImage = '<img src="' . AG_ePDQ_server_path . 'inc/assets/img/new-card/' . $cardType . '.png" alt="' . $cardType . '" />';
			$cardName = '<strong>' . esc_html( ucfirst( $token->get_card_type() ) ) . '</strong>';
			$cardEnding = esc_html( $token->get_last4() );
			$cardExpiry = ' (' . $token->get_expiry_month() . '/' . $token->get_expiry_year() . ')';

			if( $token->is_default() ) {
				$getCards[] = sprintf( $defaultCardTemplate, $token->get_id(), $cardImage, $cardName, $cardEnding, $cardExpiry );
			} else {
				$getCards[] = sprintf( $nonDefaultCardTemplate, $token->get_id(), $cardImage, $cardName, $cardEnding, $cardExpiry );
			}
		}

		return implode( '', $getCards );
	}

	public static function addNewCard() {

		$user_id = get_current_user_id();
		$wc_customer = new \WC_Customer( $user_id );
		$ePDQ_settings = new epdq_checkout();
		$settings = ePDQ_crypt::key_settings();
		$redirect_url = '';
		if( get_woocommerce_currency() !== 'GBP' && defined( 'ePDQ_PSPID' ) ) {
			$PSPID = ePDQ_PSPID;
		} else {
			$PSPID = $settings['pspid'];
		}

		// Use different PSPID (This is useful for stores that are franchisees)
		$ePDQ_PSPID = NULL;
		$multi_PSPID = apply_filters( 'ePDQ_PSPID', $ePDQ_PSPID );
		if( ! empty( $multi_PSPID ) ) {
			$PSPID = $multi_PSPID;
		}

		$site_name = get_bloginfo( 'name' );
		$m_site_name = preg_replace( "/[^a-zA-Z0-9]/", "", $site_name );
		$modified_site_name = str_replace( "039", "", $m_site_name );

		$fields = array(
			'PSPID'                                  => $PSPID,
			'ORDERID'                                => 'AGDT-' . $user_id . '-' . mt_rand(),
			'AMOUNT'                                 => '0',
			'CURRENCY'                               => get_woocommerce_currency(),
			'LANGUAGE'                               => get_locale(),
			'CN'                                     => $wc_customer->get_first_name() . ' ' . $wc_customer->get_last_name(),
			'COM'                                    => 'Customer is adding a new card to token',
			'EMAIL'                                  => $wc_customer->get_email(),
			'OWNERZIP'                               => $wc_customer->get_billing_postcode(),
			'OWNERADDRESS'                           => $wc_customer->get_billing_address_1(),
			'OWNERADDRESS2'                          => substr( $wc_customer->get_billing_address_2(), 0, 34 ),
			'OWNERCTY'                               => $wc_customer->get_billing_country(),
			'OWNERTOWN'                              => $wc_customer->get_billing_city(),
			'OWNERTELNO'                             => $wc_customer->get_billing_phone(),
			'ACCEPTURL'                              => WC()->api_request_url( 'epdq_checkout_token' ),
			'DECLINEURL'                             => wc_get_account_endpoint_url( 'payment-methods' ) . '?token_success=0',
			'HOMEURL'                                => wc_get_account_endpoint_url( 'payment-methods' ) . '?token_success=0',
			'TP'                                     => ( $ePDQ_settings->template ?? '' ),
			'LOGO'                                   => ( $ePDQ_settings->logo ?? '' ),
			'TITLE'                                  => '',
			'FLAG3D'                                 => 'Y',
			'MPI.THREEDSREQUESTORCHALLENGEINDICATOR' => 04
		);
		$fields['ALIAS'] = 'VALUE';
		$fields['ALIASOPERATION'] = 'BYPSP';
		$fields['ALIASUSAGE'] = 'Saving a new card for use on ' . $modified_site_name . ' Website. Please authorise Barclaycard to store your details for your future payments.';
		$fields['COF_INITIATOR'] = 'CIT';
		$fields['COF_TRANSACTION'] = 'FIRST';
		$fields['COF_SCHEDULE'] = 'SCHED';
		$fields['BRAND'] = '';
		$fields['PM'] = 'CreditCard';

		$shasign_arg = array();
		ksort( $fields );
		foreach( $fields as $key => $value ) {
			if( $value == '' ) {
				continue;
			}
			$shasign_arg[] = $key . '=' . $value;
		}

		$shasign = hash( ePDQ_crypt::get_sha_method(), implode( $settings['shain'], $shasign_arg ) . $settings['shain'] );
		$fields['SHASIGN'] = $shasign;

		if( $ePDQ_settings->status === 'test' ) {
			$redirect_url = self::$test_url;
		}
		if( $ePDQ_settings->status === 'live' ) {
			$redirect_url = self::$live_url;
		}

		// Construct the query string from your $fields array
		$query_string = http_build_query( $fields );

		// Combine the PSP URL and query string
		$redirect_url .= '?' . $query_string;

		// Perform the redirect
		header( 'Location: ' . $redirect_url );
		exit;

	}

}