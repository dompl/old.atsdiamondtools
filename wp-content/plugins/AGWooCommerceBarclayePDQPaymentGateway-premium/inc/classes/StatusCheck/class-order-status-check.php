<?php
/*-----------------------------------------------------------------------------------*/

/*	AG ePDQ order status check
    Fallback for when ePDQ HTTP server-to-server request fails due to website host issues.
/*-----------------------------------------------------------------------------------*/

use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || die( "No script kiddies please!" );

if( class_exists( 'AG_ePDQ_order_status_check' ) ) {
	return;
}

class AG_ePDQ_order_status_check {


	public static $single_instance = NULL;
	public static $args = array();

	public function __construct() {

		add_action( 'admin_enqueue_scripts', array( $this, 'status_check_js' ) );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'epdq_check' ) );
		add_action( 'woocommerce_order_item_add_action_buttons', array( $this, 'callback_button' ) );
		add_action( 'wp_ajax_ag_epdq_manually_check_status_call', array( $this, 'ag_epdq_manually_check_status_call' ) );
		add_filter( 'manage_edit-shop_order_sortable_columns', array( $this, 'ag_set_sortable_columns' ) );

		add_action( 'ag_check_unpaid_orders', array( $this, 'ag_check_unpaid_orders' ) );
		add_action( 'update_option', array( $this, 'check_epdq_gateway_setting' ), 10, 3 );

		add_filter( 'woocommerce_cancel_unpaid_order', array( $this, 'disable_automatic_cancellation_for_epdq_checkout' ), 10, 2 );
		add_action( 'init', array( $this, 'schedule_custom_unpaid_order_cancellation' ) );
		add_action( 'custom_handle_unpaid_epdq_orders', array( $this, 'handle_unpaid_epdq_orders' ) );

		if( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			add_filter( 'woocommerce_shop_order_list_table_columns', array( $this, 'ag_change_order_column_HPOS' ), 20 );
			add_action( 'woocommerce_shop_order_list_table_custom_column', array( $this, 'show_status_check_order_screen_HPOS' ), 10, 2 );
		}
		{
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'show_status_check_order_screen' ) );
			add_filter( 'manage_edit-shop_order_columns', array( $this, 'ag_change_order_column' ) );
		}

	}

	public static function check_epdq_gateway_setting( $option, $old_value, $value ) {

		$ePDQ_settings = new epdq_checkout();

		if( empty( $ePDQ_settings->autostatus ) || $ePDQ_settings->autostatus === 'no' ) {
			return;
		}

		if( empty( $ePDQ_settings->check_interval ) ) {
			return;
		}

		// Check if the option updated is the one storing check_interval
		if( $option === 'woocommerce_epdq_checkout_settings' ) {
			// Check if the 'check_interval' setting was changed
			if( isset( $value['check_interval'] ) && $old_value['check_interval'] !== $value['check_interval'] ) {
				// Reschedule the action with the new interval
				self::reschedule_ag_check_unpaid_orders( $value['check_interval'] );
				AG_ePDQ_Helpers::ag_log( 'ePDQ check interval has been updated', 'debug', 'yes' );

			}
		}
	}

	public static function reschedule_ag_check_unpaid_orders( $new_interval ) {

		// Base action name
		$base_action_name = 'ag_check_unpaid_orders';

		// Unschedule all existing actions that match the base name
		as_unschedule_all_actions( $base_action_name, array(), 'ag_epdq_order_check' );

		// Schedule the new action with the desired interval
		as_schedule_recurring_action( time(), $new_interval, $base_action_name, array(), 'ag_epdq_order_check' );

		AG_ePDQ_Helpers::ag_log( 'Scheduled ePDQ check unpaid orders with new interval: ' . $new_interval, 'debug', 'yes' );
	}

	/**
	 * run
	 */
	public static function run_instance( $args = array() ) {

		if( self::$single_instance === NULL ) {
			self::$args = $args;
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	public static function disable_automatic_cancellation_for_epdq_checkout( $cancel, $order ) {

		$ePDQ_settings = new epdq_checkout();

		if( $order->get_payment_method() === 'epdq_checkout' && empty( $ePDQ_settings->cancelblock ) || $ePDQ_settings->cancelblock === 'yes' ) {
			// Do not cancel orders automatically if using epdq_checkout

			return;
		}

		return $cancel;
	}


	public static function schedule_custom_unpaid_order_cancellation() {

		if( ! wp_next_scheduled( 'custom_handle_unpaid_epdq_orders' ) ) {
			wp_schedule_event( time(), 'hourly', 'custom_handle_unpaid_epdq_orders' );
		}
	}

	public static function handle_unpaid_epdq_orders() {

		$ePDQ_settings = new epdq_checkout();

		$interval = $ePDQ_settings->cancel_interval; // Interval should be retrieved correctly from the settings

		$args = array(
			'limit'        => 25,
			'status'       => $ePDQ_settings->order_status,
			'date_created' => '<' . date( 'Y-m-d H:i:s', strtotime( "-{$interval} days" ) ) // Use the interval from settings
		);

		$orders = wc_get_orders( $args );

		AG_ePDQ_Helpers::ag_log( "[Scheduled Status Check] Auto cancel order function has been triggered.", 'debug', 'yes' );

		foreach( $orders as $order ) {
			if( $order->get_payment_method() === 'epdq_checkout' && ! $order->is_paid() ) {

				// Check if it's time to cancel the order
				$order_date = $order->get_date_created()->getOffsetTimestamp();
				$cutoff_time = strtotime( "+{$interval} days", $order_date ); // Use the interval to calculate the cutoff time

				if( current_time( 'timestamp' ) > $cutoff_time ) {

					$order->update_status( 'cancelled', 'AG Order automatically cancelled due to non-payment within the extended period.' );
				}
			}
		}
	}

	/**
	 * Checks for unpaid orders with specific statuses and processes them accordingly.
	 * Targets orders that are either pending, sent for quote, or quote requested.
	 */
	public static function ag_check_unpaid_orders() {

		$ePDQ_settings = new epdq_checkout();

		if( empty( $ePDQ_settings->autostatus ) || $ePDQ_settings->autostatus === 'no' ) {
			return;
		}

		$orders = wc_get_orders( array(
			'status'  => $ePDQ_settings->order_status,
			'limit'   => 25,
			'orderby' => 'date',
			'order'   => 'ASC',
			'return'  => 'ids',
		) );

		// Log the number of orders that are being checked
		$number_of_orders = count( $orders );
		AG_ePDQ_Helpers::ag_log( "[Scheduled Status Check] Number of orders that are being checked: $number_of_orders", 'debug', 'yes' );

		foreach( $orders as $order_id ) {
			$order = wc_get_order( $order_id );
			if( $order->get_payment_method() === 'epdq_checkout' ) {
				self::auto_epdq_check( $order_id );
			}
		}

		as_unschedule_action( 'ag_check_unpaid_orders', array(), 'ag_epdq_order_check' );

	}

	/**
	 * Check order status when order is cancelled...
	 *
	 * @param $order_id
	 */
	public static function epdq_check( $order_id ) {

		$order = new WC_Order( $order_id );
		$ePDQ_settings = new epdq_checkout();
		$settings = ePDQ_crypt::refund_settings();

		// Is auto check enabled?
		if( $ePDQ_settings->statusCheck === 'yes' ) {
			return;
		}

		// Check order is using our plugin
		if( $order->get_payment_method() !== 'epdq_checkout' ) {
			return;
		}

		// If customer cancels order.
		if( $order->get_meta( 'customer_canceled_order' ) ) {
			$order->add_order_note( 'The customer canceled the order. Status check did not run.' );

			return;
		}

		// if manual change.
		if( current_user_can( 'administrator' ) || current_user_can( 'shop_manager' ) ) {
			$order->add_order_note( 'Order was manually set to cancelled. Status check did not run.' );

			return;
		}

		if( empty( $settings['USERID'] ) || empty( $settings['PSWD'] ) ) {
			AG_ePDQ_Helpers::ag_log( 'AG Status check failed: API username has not been set.', 'debug', $ePDQ_settings->debug );
			$order->add_order_note( 'AG Status check failed: API details not set. <br />Check the guide on how to set up <a href="https://weareag.co.uk/docs/barclays-epdq-payment-gateway/setup-barclays-epdq-payment-gateway/how-to-setup-epdq-status-check/" target="_blank">here</a>.' );
		}

		if( empty( $settings['PSWD'] ) || empty( $settings['USERID'] ) ) {
			return;
		}

		// Status check has run log
		$auto_status_ran = array(
			'ag_auto_check_ran' => 'Yes',
		);
		AG_ePDQ_Helpers::update_order_meta_data( $order->get_id(), $auto_status_ran, $order );

		self::status_check_order_process( $order, '[Auto]', 'auto check' );

	}

	public static function callback_button( $order ) {

		$settings = ePDQ_crypt::refund_settings();

		// Check order is using our plugin
		if( $order->get_payment_method() !== 'epdq_checkout' ) {
			return;
		}

		if( empty( $settings['PSWD'] ) || empty( $settings['USERID'] ) ) {
			return;
		}

		if( 0 < $order->get_total() - $order->get_total_refunded() || 0 < absint( $order->get_item_count() - $order->get_item_count_refunded() ) ) {

			echo '<button id="ag-check-status-epdq"  type="button" id="ag-status" class="button ag-status" data-order_url="' . esc_attr( get_edit_post_link( $order->get_id() ) ) . '" data-order_id="' . esc_attr( $order->get_id() ) . '" data-plugin="' . AG_ePDQ_url . '">AG ePDQ Order Status Check</button>';  // @phpstan-ignore-line

			return;

		}

	}

	public function status_check_js( $hook ) {

		global $post;

		wp_enqueue_script( self::$args['plugin_name'] . '-status-check', AG_ePDQ_server_path . "inc/assets/js/ag-status-check-script.js", array( 'jquery' ), NULL, TRUE );  // @phpstan-ignore-line
		wp_localize_script( self::$args['plugin_name'] . '-status-check', 'ag_epdq_status_var', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'msg'     => __( 'Are you sure you wish to check the status of this order?', 'ag_epdq_server' ),
			'nonce'   => wp_create_nonce( self::$args['plugin_name'] . '-status-check' ),
			'error'   => __( 'Something went wrong, and the order status check could not be completed. Please try again.', 'ag_epdq_server' ),
		) );

		// }

	}


	/**
	 * Ajax
	 */
	function ag_epdq_manually_check_status_call() {

		check_ajax_referer( self::$args['plugin_name'] . '-status-check', 'nonce' );

		// the data from the ajax call
		$order_id = AG_ePDQ_Helpers::AG_decode( $_POST['order_id'] );
		$order = new WC_Order( $order_id );

		// Check order is using our plugin
		if( $order->get_payment_method() !== 'epdq_checkout' ) {
			return;
		}

		$order = new WC_Order( $order_id );
		$ePDQ_settings = new epdq_checkout();
		$settings = ePDQ_crypt::refund_settings();

		if( empty( $settings['USERID'] ) ) {
			AG_ePDQ_Helpers::ag_log( 'AG Status check failed: API username has not been set.', 'debug', $ePDQ_settings->debug );
			$order->add_order_note( 'AG Status check failed: API username has not been set.' );
		}

		if( empty( $settings['PSWD'] ) ) {
			AG_ePDQ_Helpers::ag_log( 'AG Status check failed: API password has not been set.', 'debug', $ePDQ_settings->debug );
			$order->add_order_note( 'AG Status check failed: API password has not been set.' );
		}

		if( empty( $settings['USERID'] ) || empty( $settings['PSWD'] ) ) {
			AG_ePDQ_Helpers::ag_log( 'AG Status check failed. API details not set.', 'debug', $ePDQ_settings->debug );
			$order->add_order_note( 'AG Status check failed: API details not set. <br />Check the guide on how to set up <a href="https://weareag.co.uk/docs/barclays-epdq-payment-gateway/setup-barclays-epdq-payment-gateway/how-to-setup-epdq-status-check/" target="_blank">here</a>.' );
		}

		// Status check has ran log
		$manual_status_ran = array(
			'ag_manual_check_ran' => 'Yes',
		);
		AG_ePDQ_Helpers::update_order_meta_data( $order->get_id(), $manual_status_ran, $order );

		self::status_check_order_process( $order, '[Manual]', 'manual check' );

	}

	public function ag_set_sortable_columns( $columns ) {

		// Disable new column from showing.
		if( defined( 'AG_disable_column' ) ) {
			return $columns;
		}

		$columns['order_number_new'] = 'order_number_new';

		return $columns;
	}


	public function ag_change_order_column( $columns ) {

		// Disable new column from showing.
		if( defined( 'AG_disable_column' ) ) {
			return $columns;
		}

		$new_columns = [];

		foreach( $columns as $key => $column ) {
			if( $key === 'order_number' ) {
				$new_columns['order_number_new'] = $column;
			} else {
				$new_columns[ $key ] = $column;
			}
		}

		return $new_columns;
	}


	public function ag_change_order_column_HPOS( $columns ) {

		// Disable new column from showing.
		if( defined( 'AG_disable_column' ) ) {
			return $columns;
		}

		$new_columns = [];

		foreach( $columns as $key => $column ) {
			if( $key === 'order_number' ) {
				$new_columns['order_number_new'] = $column;
			} else {
				$new_columns[ $key ] = $column;
			}
		}

		return $new_columns;
	}

	public function show_status_check_order_screen( $column ) {

		global $post;

		if( 'order_number_new' === $column ) {
			$order = wc_get_order( $post->ID );

			$buyer = '';

			if( $order->get_billing_first_name() || $order->get_billing_last_name() ) {
				/* translators: 1: first name 2: last name */
				$buyer = trim( sprintf( _x( '%1$s %2$s', 'full name', 'ag_epdq_server' ), $order->get_billing_first_name(), $order->get_billing_last_name() ) );
			} elseif( $order->get_billing_company() ) {
				$buyer = trim( $order->get_billing_company() );
			} elseif( $order->get_customer_id() ) {
				$user = get_user_by( 'id', $order->get_customer_id() );
				$buyer = ucwords( $user->display_name );
			}

			/**
			 * Filter buyer name in list table orders.
			 *
			 * @param string $buyer Buyer name.
			 * @param WC_Order $order Order data.
			 *
			 * @since 3.7.0
			 */
			$buyer = apply_filters( 'woocommerce_admin_order_buyer_name', $buyer, $order );

			if( $order->get_status() === 'trash' ) {
				echo '<strong>#' . esc_attr( $order->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong>';
			} else {
				echo '<a href="#" class="order-preview" data-order-id="' . absint( $order->get_id() ) . '" title="' . esc_attr( __( 'Preview', 'ag_epdq_server' ) ) . '">' . esc_html( __( 'Preview', 'ag_epdq_server' ) ) . '</a>';
				echo '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $order->get_id() ) ) . '&action=edit' ) . '" class="order-view"><strong>#' . esc_attr( $order->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong></a>';

				// Check order is using our plugin
				if( $order->get_payment_method() !== 'epdq_checkout' ) {
					return;
				}

				$keys = array_column( $order->get_meta_data(), 'key' );
				$ag_auto_check_ran = array_search( 'ag_auto_check_ran', $keys, TRUE );
				$ag_ag_manual_check_ran = array_search( 'ag_manual_check_ran', $keys, TRUE );

				if( $ag_auto_check_ran ) {

					echo '<p title="The AG auto status check has already ran on this order and updated it status.">AG Auto Status Check &#10004;</p>';

				} elseif( $ag_ag_manual_check_ran ) {

					echo '<p title="The AG manual status check has already ran on this order and updated it status.">AG Manual Status Check &#10004;</p>';

				}
			}
		}
	}

	public function show_status_check_order_screen_HPOS( $column, $order ) {

		if( 'order_number_new' === $column ) {
			$buyer = '';

			if( $order->get_billing_first_name() || $order->get_billing_last_name() ) {
				/* translators: 1: first name 2: last name */
				$buyer = trim( sprintf( _x( '%1$s %2$s', 'full name', 'ag_epdq_server' ), $order->get_billing_first_name(), $order->get_billing_last_name() ) );
			} elseif( $order->get_billing_company() ) {
				$buyer = trim( $order->get_billing_company() );
			} elseif( $order->get_customer_id() ) {
				$user = get_user_by( 'id', $order->get_customer_id() );
				$buyer = ucwords( $user->display_name );
			}

			/**
			 * Filter buyer name in list table orders.
			 *
			 * @param string $buyer Buyer name.
			 * @param WC_Order $order Order data.
			 *
			 * @since 3.7.0
			 */
			$buyer = apply_filters( 'woocommerce_admin_order_buyer_name', $buyer, $order );

			if( $order->get_status() === 'trash' ) {
				echo '<strong>#' . esc_attr( $order->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong>';
			} else {
				echo '<a href="#" class="order-preview" data-order-id="' . absint( $order->get_id() ) . '" title="' . esc_attr( __( 'Preview', 'ag_epdq_server' ) ) . '">' . esc_html( __( 'Preview', 'ag_epdq_server' ) ) . '</a>';
				echo '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $order->get_id() ) ) . '&action=edit' ) . '" class="order-view"><strong>#' . esc_attr( $order->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong></a>';

				// Check order is using our plugin
				if( $order->get_payment_method() !== 'epdq_checkout' ) {
					return;
				}

				$keys = array_column( $order->get_meta_data(), 'key' );
				$ag_auto_check_ran = array_search( 'ag_auto_check_ran', $keys, TRUE );
				$ag_ag_manual_check_ran = array_search( 'ag_manual_check_ran', $keys, TRUE );

				if( $ag_auto_check_ran ) {

					echo '<p title="The AG auto status check has already ran on this order and updated it status.">AG Auto Status Check &#10004;</p>';

				} elseif( $ag_ag_manual_check_ran ) {

					echo '<p title="The AG manual status check has already ran on this order and updated it status.">AG Manual Status Check &#10004;</p>';

				}

			}

		}
	}


	public static function auto_epdq_check( $order_id ) {

		$order = new WC_Order( $order_id );

		// Status check has run log
		$auto_status_ran = array(
			'ag_auto_check_ran' => 'Yes',
		);
		AG_ePDQ_Helpers::update_order_meta_data( $order->get_id(), $auto_status_ran, $order );

		self::status_check_order_process( $order, '[Scheduled Status Check]', 'auto' );

	}

	public static function status_check_order_process( $order, $label, $method ) {

		$ePDQ_settings = new epdq_checkout();
		$settings = ePDQ_crypt::refund_settings();
		$key_settings = ePDQ_crypt::key_settings();
		$environment_url = AG_ePDQ_Helpers::get_enviroment_url( 'querydirect' );
		$accepted = array( 4, 5, 9 );

		// Data to send
		$data_post = array();
		$data_post['ORDERID'] = AG_ePDQ_Helpers::ag_get_order_id( $order );
		if( AG_ePDQ_Helpers::ag_get_order_currency( $order ) !== 'GBP' && defined( 'ePDQ_PSPID' ) ) {
			$data_post['PSPID'] = ePDQ_PSPID;
		} else {
			$data_post['PSPID'] = $key_settings['pspid'];
		}
		$data_post['PSWD'] = $settings['PSWD'];
		$data_post['USERID'] = $settings['USERID'];

		// Post
		$result = AG_ePDQ_Helpers::remote_post( $environment_url, $data_post );

		if( ! isset( $result['STATUS'] ) ) {
			return;
		}

		// Dont allow schedule to flag not complete orders and close them
		if( $result['STATUS'] === '0' && $label === '[Scheduled Status Check]' || $result['STATUS'] === NULL && $label === '[Scheduled Status Check]' ) {
			return;
		}

		if( defined( 'ag_support_debug' ) && $result['NCSTATUS'] !== '5' ) {
			AG_ePDQ_Helpers::ag_log( $label . ' - data returned: ' . print_r( $result, TRUE ), 'debug', 'yes' );
		}

		// Update order meta data.
		AG_ePDQ_Helpers::update_order_meta_data( $order->get_id(), $result, $order );

		$status_check = sprintf( '<p><strong>%s</strong></p>', __( $label . ' AG ePDQ ' . $method . ' order status check has checked the status of this order.', 'ag_epdq_server' ) );

		$note = sprintf( '<p>%s - %s</p>', __( 'ePDQ Status:', 'ag_epdq_server' ), AG_errors::get_epdq_status_code( $result['STATUS'] ) );

		if( isset( $result['NCERROR'] ) ) {
			$error_note = sprintf( '<p>%s - %s</p>', __( 'ePDQ NCERROR:', 'ag_epdq_server' ), AG_errors::get_epdq_ncerror( $result['NCERROR'] ) );
			$error_note .= sprintf( '<p>%s - %s</p>', __( 'ePDQ NCERROR PLUS:', 'ag_epdq_server' ), $result['NCERRORPLUS'] );
		}

		$note .= sprintf( '<p>%s - %s</p>', __( 'Order ID:', 'ag_epdq_server' ), $order->get_id() );
		$note .= sprintf( '<p>%s - %s</p>', __( 'Payment Reference In ePDQ System:', 'ag_epdq_server' ), $result['PAYID'] );

		$order->update_meta_data( 'Status', AG_errors::get_epdq_status_code( $result['STATUS'] ) );
		$order->save();

		if( in_array( $result['STATUS'], $accepted ) ) {

			// 3DS v2 Frictionless flow
			$noteTitle = __( 'Barclays ePDQ has confirmed the transaction.', 'ag_epdq_server' ) . '</strong></p>';
			AG_ePDQ_Helpers::ag_log( 'Barclays ePDQ transaction is confirmed (The transaction was a 3DS v2 Frictionless transaction)', 'debug', $ePDQ_settings->debug );
			$note .= '<p><strong>' . __( 'Barclays ePDQ transaction is confirmed', 'ag_epdq_server' ) . '</strong></p>';
			$order->add_order_note( $note );
			$order->payment_complete( $result['PAYID'] );
			$order->update_meta_data( 'HTML_ANSWER', '' );
			$order->save();
			$order->add_order_note( $status_check . $noteTitle );

			$orderdata = array(
				'Status' => AG_errors::get_epdq_status_code( $result['STATUS'] ), // @phpstan-ignore-line
				'PAYID'  => $result['PAYID'] ?? '',

			);
			AG_ePDQ_Helpers::update_order_meta_data( $order->get_id(), $orderdata, $order );

		} elseif( in_array( $result['STATUS'], array( 41, 51 ) ) ) {

			$noteTitle = __( 'The authorisation will be processed offline. Please confirm the payment in the ePDQ back office.', 'ag_epdq_server' ) . '</strong></p>';
			AG_ePDQ_Helpers::ag_log( 'The data capture will be processed offline. This is the standard response if you have selected offline processing in your account configuration. Check the  the "Global transaction parameters" tab in the ePDQ back office.', 'debug', $ePDQ_settings->debug );
			$order->update_status( 'on-hold' );
			$order->update_meta_data( 'HTML_ANSWER', '' );
			$order->save();
			$order->add_order_note( $status_check . $noteTitle );

		} elseif( $result['STATUS'] === '91' ) {

			$noteTitle = __( 'You requested to confirm an authorisation request, we are waiting for a response from your acquirer, this transaction is in a pending status. Please check again soon.', 'ag_epdq_server' ) . '</strong></p>';
			//$order->add_order_note( $error_note );
			AG_ePDQ_Helpers::ag_log( 'You requested to confirm an authorisation request, we are waiting for a response from your acquirer, this transaction is in a pending status', 'debug', $ePDQ_settings->debug );
			$order->update_status( 'on-hold' );
			$order->update_meta_data( 'HTML_ANSWER', '' );
			$order->save();
			$order->add_order_note( $status_check . $noteTitle );

		} elseif( $result['STATUS'] === '2' || $result['STATUS'] === '93' ) {

			$noteTitle = __( 'Barclays ePDQ has refused the transaction.', 'ag_epdq_server' ) . '</strong></p>';
			$order->add_order_note( $error_note );
			AG_ePDQ_Helpers::ag_log( 'The authorisation has been refused by the financial institution. The customer can retry the authorisation process after selecting another card or another payment method.', 'debug', $ePDQ_settings->debug );
			$order->update_status( 'failed' );
			$order->update_meta_data( 'HTML_ANSWER', '' );
			$order->save();
			$order->add_order_note( $status_check . $noteTitle );

		} elseif( $result['STATUS'] === '52' || $result['STATUS'] === '92' ) {

			$noteTitle = __( 'Barclays ePDQ has reported the payment is uncertain.', 'ag_epdq_server' ) . '</strong></p>';
			if( isset( $errornote ) ) {
				$order->add_order_note( $errornote );
				$order->add_order_note( $error_note );
			}
			AG_ePDQ_Helpers::ag_log( 'A technical problem arose during the authorisation/payment process, giving an unpredictable result.', 'debug', $ePDQ_settings->debug );
			$order->update_status( 'failed' );
			$order->add_order_note( $status_check . $noteTitle );

		} elseif( $result['STATUS'] === '1' ) {

			$noteTitle = __( 'ePDQ has confirmed the customer has cancelled the transaction', 'ag_epdq_server' ) . '</strong></p>';
			if( isset( $errornote ) ) {
				$order->add_order_note( $errornote );
				$order->add_order_note( $error_note );
			}
			$order->update_status( 'failed' );
			AG_ePDQ_Helpers::ag_log( 'The customer has cancelled the transaction', 'debug', $ePDQ_settings->debug );
			$order->add_order_note( $status_check . $noteTitle );

		} elseif( $result['STATUS'] === '5' ) {

			$noteTitle = __( 'Barclays ePDQ transaction has been Authorised.', 'ag_epdq_server' );
			AG_ePDQ_Helpers::ag_log( 'Barclays ePDQ transaction has been Authorised. No issues to report.', 'debug', $ePDQ_settings->debug );
			//$order->add_order_note( $note );
			$order->add_order_note( '<strong>' . __( 'The order must be captured before funds will be sent to you. You can capture from within your ePDQ account or here in this order by clicking the capture payment button.', 'ag_epdq_server' ) . '</strong>' );
			$order->update_status( 'on-hold' );
			$order->add_order_note( $status_check . $noteTitle );
			$orderdata = array(
				'Status' => AG_errors::get_epdq_status_code( $result['STATUS'] ), // @phpstan-ignore-line
				'PAYID'  => $result['PAYID'] ?? '',

			);
			AG_ePDQ_Helpers::update_order_meta_data( $order->get_id(), $orderdata, $order );

		} elseif( $result['STATUS'] === '0' || $result['STATUS'] === NULL ) {

			$noteTitle = __( 'The order has come back as Incomplete or invalid', 'ag_epdq_server' ) . '</strong></p>';
			if( isset( $errornote ) ) {
				$order->add_order_note( $errornote );
				$order->add_order_note( $error_note );
			}
			$order->add_order_note( $note );
			$order->update_status( 'failed' );
			AG_ePDQ_Helpers::ag_log( 'Incomplete or invalid payment', 'debug', $ePDQ_settings->debug );
			$order->add_order_note( $status_check . $noteTitle );

		} else {

			if( isset( $errornote ) ) {
				$noteTitle = __( 'The order has come back with an issue', 'ag_epdq_server' ) . '</strong></p>';

				$order->add_order_note( $errornote );
				$order->add_order_note( $error_note );
				$order->add_order_note( $status_check . $noteTitle );
				$order->update_status( 'failed' );
			}

		}

	}

}




