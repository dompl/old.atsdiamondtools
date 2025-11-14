<?php
/*
 * Author: We are AG
 * Author URI: https://www.weareag.co.uk/
 * File: class-order.php
 * Project: AGWooCommerceBarclayePDQPaymentGateway-premium
 * -----
 * Created: 05 April 2023 11:21
 * -----
 * Version: 1.0.0
 * WC requires at least: 3.0.0
 * WC tested up to: 5.5
 * License: GPL3
*/

/*-----------------------------------------------------------------------------------*/
/*	ePDQ Order
/*-----------------------------------------------------------------------------------*/
defined( 'ABSPATH' ) || die( "No script kiddies please!" );

if( class_exists( 'epdq_order' ) ) {
	return;
}

/**
 * Class epdq_order
 */
class epdq_order {

	/**
	 * @param $args array Data from ePDQ
	 * @param $type string Order type
	 * @param $order WC_Order Woo order object
	 *
	 * @return void
	 */
	public static function process( array $args, string $type, WC_Order $order ): void {

		global $woocommerce;
		$epdq_settings = new epdq_checkout();

		$note = '<p>' . __( 'ePDQ Status:', 'ag_epdq_server' ) . AG_errors::get_epdq_status_code( $args['STATUS'] ?? '') . '</p>'; // @phpstan-ignore-line
		$errorNote = '<p>' . __( 'ePDQ NCERROR:', 'ag_epdq_server' ) . AG_errors::get_epdq_ncerror( $args['NCERROR'] ?? '' ) . '</p>'; // @phpstan-ignore-line

		$order_data = array(
			'Status' => AG_errors::get_epdq_status_code( $args['STATUS'] ) ?? '',
			'PAYID'  => $args['PAYID'] ?? '',

		);
		AG_ePDQ_Helpers::update_order_meta_data( $order->get_id(), $order_data, $order );

		switch ( $args['STATUS'] ):

			case '4':
			case '9':
				$order->add_order_note( $note );
				AG_ePDQ_Helpers::ag_log( $type . 'Barclays ePDQ transaction is confirmed. No issues to report.', 'debug', $epdq_settings->debug );
				$order->add_order_note( $type . __( 'Barclays ePDQ transaction is confirmed.', 'ag_epdq_server' ) );
				if( $order->get_meta( 'is_moto' ) ) {
					$order->add_order_note( '<strong>' . __( 'This was a MOTO payment.', 'ag_epdq_server' ) . '</strong>' );
				}
				$order->update_meta_data( 'HTML_ANSWER', '' );
				$order->save();
				$order->payment_complete( $args['PAYID'] );
				break;

			case '5':
				$order->add_order_note( $note );
				AG_ePDQ_Helpers::ag_log( $type . 'Barclays ePDQ transaction has been Authorised. No issues to report.', 'debug', $epdq_settings->debug );
				$order->add_order_note( $type . __( 'Barclays ePDQ transaction has been Authorised.', 'ag_epdq_server' ) );
				$order->add_order_note( '<strong>' . __( 'The order must be captured before funds will be sent to you. You can capture from within your ePDQ account or here in this order by clicking the capture payment button.', 'ag_epdq_server' ) . '</strong>' );
				$order->update_status( 'on-hold' );
				break;

			case '41':
			case '51':
				$order->add_order_note( $type . __( 'The authorisation will be processed offline. Please confirm the payment in the ePDQ back office.', 'ag_epdq_server' ) );
				AG_ePDQ_Helpers::ag_log( $type . 'The data capture will be processed offline. This is the standard response if you have selected offline processing in your account configuration. Check the  the "Global transaction parameters" tab in the ePDQ back office.', 'debug', $epdq_settings->debug );
				$order->update_status( 'on-hold' );
				$order->update_meta_data( 'HTML_ANSWER', '' );
				$order->save();
				wc_add_notice( __( 'The data capture will be processed offline.', 'ag_epdq_server' ), 'error' );
				wc_add_notice( $errorNote, 'error' );
				break;

			case '91':
				$order->add_order_note( $type . __( 'Barclays ePDQ capture is processing.', 'ag_epdq_server' ) );
				AG_ePDQ_Helpers::ag_log( $type . 'Barclays ePDQ capture is processing.', 'debug', $epdq_settings->debug );
				$order->add_order_note( '<strong>' . __( 'It can take upto 15 minutes for the capture processing to complete on the ePDQ platform. If you have our webhook enabled in your ePDQ back office, the order will auto update. Without the webhook you will need to manually check the status of the order using the "Status Check" feature after 15 minutes of capturing the payment to double check the order.', 'ag_epdq_server' ) . '</strong>' );
				$order->update_status( 'on-hold' );
				wc_add_notice( __( 'The data capture will be processed offline.', 'ag_epdq_server' ), 'error' );
				wc_add_notice( $errorNote, 'error' );
				break;

			case '2':
			case '93':
				$order->add_order_note( $type . __( 'Barclays ePDQ transaction was refused.', 'ag_epdq_server' ) );
				AG_ePDQ_Helpers::ag_log( $type . 'The authorisation has been refused by the financial institution. The customer can retry the authorisation process after selecting another card or another payment method.', 'debug', $epdq_settings->debug );
				$order->update_meta_data( 'HTML_ANSWER', '' );
				$order->save();
				$order->update_status( 'failed' );
				$woocommerce->cart->empty_cart();
				wc_add_notice( __( 'The authorisation has been refused by the financial institution, please try again.', 'ag_epdq_server' ), 'error' );
				wc_add_notice( $errorNote, 'error' );
				break;

			case '52':
			case '92':
				$order->add_order_note( $type . __( 'Barclays ePDQ payment uncertain.', 'ag_epdq_server' ) );
				AG_ePDQ_Helpers::ag_log( $type . 'A technical problem arose during the authorisation/payment process, giving an unpredictable result.', 'debug', $epdq_settings->debug );
				$order->update_meta_data( 'HTML_ANSWER', '' );
				$order->save();
				$order->update_status( 'failed' );
				$woocommerce->cart->empty_cart();
				wc_add_notice( __( 'A technical problem arose during the authorisation/payment process, giving an unpredictable result.', 'ag_epdq_server' ), 'error' );
				wc_add_notice( $errorNote, 'error' );
				break;

			case '8':
				$order->add_order_note( $note );
				AG_ePDQ_Helpers::ag_log( $type . 'Barclays ePDQ refund has been processed.', 'debug', $epdq_settings->debug );
				$order->add_order_note( $type . __( 'Barclays ePDQ refund has been processed.', 'ag_epdq_server' ) );
				$order->update_status( 'refunded' );
				break;

			case '81':
				$order->add_order_note( $note );
				AG_ePDQ_Helpers::ag_log( $type . 'Barclays ePDQ refund has been requested.', 'debug', $epdq_settings->debug );
				$order->add_order_note( $type . __( 'Barclays ePDQ refund has been requested.', 'ag_epdq_server' ) );
				break;

			case '82':
			case '83':
			case '94':
				$order->add_order_note( $note );
				AG_ePDQ_Helpers::ag_log( $type . 'There was an issue processing the Barclays ePDQ refund.', 'debug', $epdq_settings->debug );
				$order->add_order_note( $type . __( 'There was an issue processing the Barclays ePDQ refund.', 'ag_epdq_server' ) );
				break;

			case '1':
				$order->add_order_note( $type . __( 'The customer has cancelled the transaction', 'ag_epdq_server' ) );
				$order->add_order_note( $errorNote );
				$order->update_status( 'cancelled', $note );
				$orderCancel = array(
					'customer_canceled_order' => TRUE,
				);
				AG_ePDQ_Helpers::update_order_meta_data( $args['ORDERID'], $orderCancel, $order );
				$woocommerce->cart->empty_cart();
				break;

			case '0':
				$order->add_order_note( $type . __( 'Incomplete or invalid', 'ag_epdq_server' ) );
				$order->add_order_note( $errorNote );
				$order->update_status( 'failed', $note );
				break;

		endswitch;

	}

}