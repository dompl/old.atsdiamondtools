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

if ( class_exists( 'AG_Token' ) ) {
	return;
}


class AG_Token {


	public static function save( $args, $userID, $login ) {

		if ( empty( $args['ALIAS'] ) || empty( $userID ) || $login === false ) {
			return;
		}

		// Used token before
		$savedCard     = get_post_meta( $args['orderID'], 'use_saved_card', true );
		$customerToken = self::check( $login, $savedCard );
		if ( isset( $customerToken ) || ! empty( $customerToken ) ) {
			$middle = strlen( $args['ED'] ) / 2;
			$brand  = $args['BRAND'];

			// Customer token already stored, change default to just used card.
			if ( isset( $savedCard ) && $customerToken->get_last4() === substr( $args['CARDNO'], - 4 ) && $customerToken->get_expiry_month() === substr( $args['ED'], 0, $middle )
			     && $customerToken->get_expiry_year() === '20' . substr( $args['ED'], 2, $middle ) && $brand === $customerToken->get_card_type() ) {
				$token = WC_Payment_Tokens::get( $savedCard );
				$token->set_default( true );
				$token->save();

				return;
			}

		}


		// Check that brand is supported Woo brand for token
		$brand = $args['BRAND'];
		if ( ! in_array( $brand, array( 'american express', 'jcb', 'MasterCard', 'VISA' ) ) ) {
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

		if ( $login === false || empty( $savedCard ) ) {
			return null;
		}

		return WC_Payment_Tokens::get( $savedCard );
	}

	public static function get( $userID, $login, $savedCard ) {

		if ( empty( $userID ) || $login === false || empty( $savedCard ) ) {
			return null;
		}

		$token = WC_Payment_Tokens::get( $savedCard );

		$customerToken = array( 'token' => null, 'brand' => null );
		if ( $token ) {
			$customerToken['token'] = $token->get_token();
			$customerToken['brand'] = $token->get_card_type();
		}

		return $customerToken;
	}

	public static function selectSavedCards( $userID, $login ) {

		$tokens = WC_Payment_Tokens::get_customer_tokens( $userID, 'epdq_checkout' );

		if ( $tokens ) {

			return '<div class="ag-select-cards">
				<p>' . __( 'Your credit cards: ', 'ag_epdq_server' ) . '</p>
				<ul class="card-list">
					' . self::displayCards( $userID, $login, $tokens ) . '
					<li><input type="radio" id="ag_cards" name="saved_cards" value="0"><label for="saved_cards"><p><strong>' . __( 'Use a new card', 'ag_epdq_server' ) . '</strong></p></label></li>
				</ul>
			</div>
			<script>
			  jQuery("#cards-1").prop( "checked", true ).parent().addClass("selected");
			  jQuery(".ag-select-cards .card-list li input:radio").click(function () {
				  jQuery(".ag-select-cards .card-list li input:radio").parent().removeClass("selected");
				  jQuery(this).parent(this).addClass("selected");
			  });
			</script>';

		}

	}

	public static function displayCards( $userID, $login, $tokens ) {

		if ( empty( $userID ) || $login === false || empty( $tokens ) ) {
			return null;
		}

		$getCards = array();

		foreach ( $tokens as $token ) {

			if ( $token->is_default() ) {

				$getCards[] = '<li><input type="radio" id="cards-1" name="saved_cards" value="' . $token->get_id() . '"><p>' . sprintf( esc_html__( '%1$s %2$s ending in %3$s %4$s', 'ag_epdq_server' ), '<img src="' . AG_ePDQ_server_path . 'inc/assets/img/new-card/' . strtolower( $token->get_card_type() ) . '.png" alt="' . strtolower( $token->get_card_type() ) . '" />', '<strong>' . esc_html( ucfirst( $token->get_card_type() ) ) . '</strong>', esc_html( $token->get_last4() ), ' (' . $token->get_expiry_month() . '/' . $token->get_expiry_year() . ') </p></li>' );

			}
		}

		foreach ( $tokens as $token ) {

			if ( ! $token->is_default() ) {

				$getCards[] = '<li><input type="radio" id="cards" name="saved_cards" value="' . $token->get_id() . '"><p>' . sprintf( esc_html__( '%1$s %2$s ending in %3$s %4$s', 'ag_epdq_server' ), '<img src="' . AG_ePDQ_server_path . 'inc/assets/img/new-card/' . strtolower( $token->get_card_type() ) . '.png" alt="' . strtolower( $token->get_card_type() ) . '" />', '<strong>' . esc_html( ucfirst( $token->get_card_type() ) ) . '</strong>', esc_html( $token->get_last4() ), ' (' . $token->get_expiry_month() . '/' . $token->get_expiry_year() . ') </p></li>' );

			}
		}

		return implode( $getCards );

	}

}