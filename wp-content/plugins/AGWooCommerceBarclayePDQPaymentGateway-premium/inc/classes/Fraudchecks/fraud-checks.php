<?php
/*-----------------------------------------------------------------------------------*/

/*	AG Fraud Checks
/*-----------------------------------------------------------------------------------*/

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || die( "No script kiddies please!" );

if( class_exists( 'ag_ePDQ_fraud_checks' ) ) {
	return;
}

class ag_ePDQ_fraud_checks {

	public static $single_instance = NULL;

	public static $args = array();

	public function __construct() {

		add_action( 'admin_enqueue_scripts', array( $this, 'AG_fraud_css' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_order_check' ) );

		// Catch when old version of Woo used.
		if( ! class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			AG_ePDQ_Helpers::ag_log( 'Your version of WooCommerce is out of date, please update your WooCommerce plugin to 7.0+ this will allow the use of HPOS and our traffic light system', 'warning', 'yes' );
		}
		try {
			OrderUtil::custom_orders_table_usage_is_enabled();
		} catch ( Error $e ) {
			AG_ePDQ_Helpers::ag_log( 'Error: OrderUtil class or custom_orders_table_usage_is_enabled method is not available.', 'warning', 'yes' );

			return;
		}

		if( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			add_filter( 'woocommerce_shop_order_list_table_columns', array( $this, 'custom_shop_order_column_HPOS' ), 20 );
			add_action( 'woocommerce_shop_order_list_table_custom_column', array( $this, 'custom_orders_list_column_content_HPOS' ), 10, 2 );
		} else {
			add_filter( 'manage_edit-shop_order_columns', array( $this, 'custom_shop_order_column' ), 20 );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'custom_orders_list_column_content' ), 20, 2 );
		}

	}

	public function custom_shop_order_column_HPOS( $columns ) {

		if( ! defined( 'disable_ag_checks' ) ) {
			$columns['ag_tls'] = 'AG Traffic Light System';
		}

		return $columns;
	}

	public function custom_orders_list_column_content_HPOS( $column, $order ) {

		if( 'ag_tls' !== $column ) {
			return;
		}

		$display = ag_ePDQ_fraud_checks::get_order_details( $order );

		if( defined( 'disable_ag_checks' ) ) {
			return;
		}

		if( ! $order ) {
			return;
		}

		if( $order->get_status() === 'cancelled' || $order->get_status() === 'pending' ) {
			return;
		}

		// Is FD order check
		if( $order->get_payment_method() !== 'epdq_checkout' ) {
			return;
		}

		// Is MOTO payment
		if( $order->get_meta( 'is_moto' ) ) {
			return;
		}

		if( ! $display ) {
			return;
		}

		echo $display['address_code_display']['display'];
		echo $display['postal_code_display']['display'];
		echo $display['cvc_code_display']['display'];
		echo $display['Code_3DS_display']['display'];

	}

	/**
	 * @param $args
	 *
	 * @return ag_ePDQ_fraud_checks|null
	 */

	public static function run_instance( $args = array() ) {

		if( self::$single_instance === NULL ) {
			self::$args = $args;
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * @return void
	 */
	public function add_order_check() {

		$screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';

		add_meta_box( 'ag_fraud_check', __( 'AG Traffic Light System', 'ag_epdq_server' ), array( $this, 'order_check_preview' ), $screen, 'side', 'core' );

	}

	/**
	 * @return void
	 */
	public function AG_fraud_css() {

		wp_enqueue_style( 'AG_fraud_css', AG_ePDQ_server_path . 'inc/assets/css/fraud-style.css', FALSE, AG_ePDQ_server::$AGversion ); // @phpstan-ignore-line
	}

	/**
	 * @return void
	 */
	public function order_check_preview( $post_or_order_object ) {

		$order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;

		if( ! $order ) {
			return;
		}

		$ePDQ_settings = new epdq_checkout();
		$fraudCheck = $ePDQ_settings->fraudCheck;

		$keys = array_column( $order->get_meta_data(), 'key' );
		$search_postal_check = array_search( 'AAVZIP', $keys, TRUE );
		$search_CVC_check = array_search( 'CVCCheck', $keys, TRUE );
		$search_3D_check = array_search( 'ECI', $keys, TRUE );
		$search_aav_check = array_search( 'AAVCheck', $keys, TRUE );

		// Is EPDQ order check
		if( $order->get_payment_method() !== 'epdq_checkout' ) {
			return;
		}

		if( $order->get_meta( 'is_moto' ) ) {
			return;
		}

		if( $order->get_status() === 'cancelled' || $order->get_status() === 'pending' ) {
			return;
		}

		// Merchant has set to hide feature
		if( defined( 'disable_ag_checks' ) ) {
			return;
		}

		if( isset( $fraudCheck ) && $fraudCheck === 'no' ) {
			$display = $this->loading_get_order_details();
			$loading = 'is-loading';
			$error = NULL;
		} elseif( ( empty( $search_3D_check ) || empty( $search_CVC_check ) || empty( $search_postal_check ) || empty( $search_aav_check ) ) && isset( $fraudCheck ) && $fraudCheck === 'yes' ) {
			$display = $this->loading_get_order_details();
			$loading = 'is-loading';
			$error = 'Please double check you have set all the correct parameters in the ePDQ back office.';
		} else {
			$display = $this->get_order_details( $order );
			$loading = '';
			$error = NULL;

		}

		$status = $order->get_meta( 'Status' );

		if( $display ) {
			echo '<span class="' . $loading . '"><br>';
			echo( $display['address_code_display']['display'] );
			echo( $display['postal_code_display']['display'] );
			echo( $display['cvc_code_display']['display'] );
			echo( $display['Code_3DS_display']['display'] );
			echo '<br><br> <span class="fraud-message">' . $display['address_code_display']['message'] . ' </span>';
			echo '<br><hr> <span class="fraud-message">' . $display['postal_code_display']['message'] . ' </span>';
			echo '<br><hr> <span class="fraud-message">' . $display['cvc_code_display']['message'] . ' </span>';
			echo '<br><hr> <span class="fraud-message">' . $display['Code_3DS_display']['message'] . ' </span>';
			if( ! empty( $status ) ) {
				echo '<br><hr> <span class="fraud-message"><strong>ePDQ Status: </strong> ' . $status . ' </span>';
			}
			if( isset( $error ) ) {
				echo '<br> <hr><a class="fraud-link" href="https://weareag.co.uk/docs/barclays-epdq-payment-gateway/setup-barclays-epdq-payment-gateway/setting-up-ag-order-check/">' . $error . '</a></span>';
			} else {
				echo '<br> <hr><a class="fraud-link" href="https://weareag.co.uk/docs/barclays-epdq-payment-gateway/setup-barclays-epdq-payment-gateway/setting-up-ag-order-check/">Learn more about this feature here</a></span>';
			}
		}

		return;

	}

	public function loading_get_order_details() {

		$address_code_display = [];
		$postal_code_display = [];
		$cvc_code_display = [];
		$Code_3DS_display = [];

		$address_code_display['message'] = '';
		$postal_code_display['message'] = '';
		$cvc_code_display['message'] = '';
		$Code_3DS_display['message'] = '';

		$address_code_display['display'] = '<div class="fraud-tooltip">
		<span class="fraud-badge" > </span> </div>';

		$postal_code_display['display'] = '<div class="fraud-tooltip">
		<span class="fraud-badge" > </span> </div>';

		$cvc_code_display['display'] = '<div class="fraud-tooltip">
		<span class="fraud-badge" > </span> </div>';

		$Code_3DS_display['display'] = '<div class="fraud-tooltip">
		<span class="fraud-badge">  </span> </div>';

		return [
			'address_code_display' => $address_code_display,
			'postal_code_display'  => $postal_code_display,
			'cvc_code_display'     => $cvc_code_display,
			'Code_3DS_display'     => $Code_3DS_display
		];
	}

	/**
	 * @param $order
	 *
	 * @return array|void
	 */
	public function get_order_details( $order ) {

		$Code_3DS = 'EMPTY';
		$address_code = 'EMPTY';
		$postal_code = 'EMPTY';
		$cvc_code = 'EMPTY';
		$code_aav = 'EMPTY';

		if( empty( $order ) ) {
			return;
		}

		$keys = array_column( $order->get_meta_data(), 'key' );
		$search_address_check = array_search( 'AAVADDRESS', $keys, TRUE );
		$search_postal_check = array_search( 'AAVZIP', $keys, TRUE );
		$search_CVC_check = array_search( 'CVCCheck', $keys, TRUE );
		$search_3D_check = array_search( 'ECI', $keys, TRUE );
		$search_aav_check = array_search( 'AAVCheck', $keys, TRUE );

		if( $search_aav_check ) {
			$code_aav = $order->get_meta_data()[ $search_aav_check ]->value;
			$address_code_display = self::approval_code_display( 'A', $code_aav );
		} else {
			$address_code_display = self::approval_code_display( 'A', $code_aav );
		}

		if( $search_address_check ) {
			$address_code = $order->get_meta_data()[ $search_address_check ]->value;
			$address_code_display = self::approval_code_display( 'A', $address_code );
		} else {
			$address_code_display = self::approval_code_display( 'A', $address_code );
		}

		if( $search_postal_check ) {
			$postal_code = $order->get_meta_data()[ $search_postal_check ]->value;
			$postal_code_display = self::approval_code_display( 'P', $postal_code );
		} else {
			$postal_code_display = self::approval_code_display( 'P', $postal_code );
		}

		if( $search_3D_check ) {
			$Code_3DS = $order->get_meta_data()[ $search_3D_check ]->value;
			$Code_3DS_display = self::secure3D_display( $Code_3DS );
		} else {
			$Code_3DS_display = self::secure3D_display( $Code_3DS );
		}

		if( $search_CVC_check ) {
			$cvc_code = $order->get_meta_data()[ $search_CVC_check ]->value;
			$cvc_code_display = self::approval_code_display( 'C', $cvc_code );
		} else {
			$cvc_code_display = self::approval_code_display( 'C', $cvc_code );
		}

		return [
			'address_code_display' => $address_code_display,
			'postal_code_display'  => $postal_code_display,
			'cvc_code_display'     => $cvc_code_display,
			'Code_3DS_display'     => $Code_3DS_display
		];

	}

	/**
	 * @param $type
	 * @param $result
	 *
	 * @return string[]
	 */
	public static function approval_code_display( $type, $result ) {

		$message = 'No data was returned';
		$display = self::display_tooltip( $type, 0 );

		switch ( $type ) {
			case 'A' :
				if( $result === 'OK' ) {
					$message = __( 'The Address is Correct', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 1 );
				} elseif( $result === 'KO' ) {
					$message = __( 'The address has been sent but the acquirer has given a negative response for the address check.', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 0 );
				} elseif( $result === 'NO' ) {
					$message = __( 'Invalid or no Address has been transmitted.', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 0 );
				} else {
					$message = 'Parameters for this Address has not been set.';
					$display = self::display_tooltip( $type, 0 );
				}
				break;
			case 'P':
				if( $result === 'OK' ) {
					$message = __( 'The Postal Code is Correct', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 1 );
				} elseif( $result === 'KO' ) {
					$message = __( 'The Postal Code has been sent but the acquirer has given a negative response for the address check.', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 0 );
				} elseif( $result === 'NO' ) {
					$message = __( 'Invalid or no Postal Code has been transmitted.', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 0 );
				} else {
					$message = __( 'Parameters for Postal Code has not been set.', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 0 );
				}
				break;
			case 'C':
				if( $result === 'OK' ) {
					$message = __( 'The CVC has been sent and the acquirer has given a positive response to the CVC check', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 1 );
				} elseif( $result === 'KO' ) {
					$message = __( 'The CVC has been sent but the acquirer has given a negative response to the CVC check, i.e. the CVC is wrong.', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 0 );
				} elseif( $result === 'NO' ) {
					$message = __( 'Invalid or no CVC has been transmitted', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 0 );
				} else {
					$message = __( 'Parameters for CVC has not been set.', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 0 );
				}
				break;
		}

		return [
			'display' => $display,
			'message' => '<strong>' . $type . ': </strong> ' . $message,

		];
	}

	/**
	 * @param $type
	 * @param $status
	 *
	 * @return string
	 */
	public static function display_tooltip( $type, $status ) {

		$tooltip = '';
		switch ( $type ) {
			case 'A':
				$tooltip = __( 'Address Check', 'ag_epdq_server' );
				break;
			case 'P':
				$tooltip = __( 'Postcode check', 'ag_epdq_server' );
				break;
			case 'C':
				$tooltip = __( 'CVC check', 'ag_epdq_server' );
				break;
			case '3':
				$tooltip = __( '3D Secure check', 'ag_epdq_server' );
				break;
		}
		// Error = 0, Success = 1, Warning = 2
		if( $status === 1 ) {
			return '<div class="fraud-tooltip">
                <span class="fraud-badge-success" >' . $type . '</span>
                <span class="fraud-tooltiptext">' . $tooltip . '</span>
                </div>';

		}

		if( $status === 2 ) {
			return '<div class="fraud-tooltip">
                <span class="fraud-badge-warning" >' . $type . '</span>
                <span class="fraud-tooltiptext">' . $tooltip . '</span>
                </div>';

		} elseif( $status === 3 ) {
			return '<div class="fraud-tooltip">
                <span class="fraud-badge-sub" >' . $type . '</span>
                <span class="fraud-tooltiptext">' . $tooltip . '</span>
                </div>';

		} else {

			return '<div class="fraud-tooltip">
		<span class="fraud-badge-danger" >' . $type . '</span>
		<span class="fraud-tooltiptext">' . $tooltip . '</span>
		</div>';

		}

	}

	/**
	 * @param $Code_3DS
	 *
	 * @return array
	 */
	public static function secure3D_display( $Code_3DS ) {

		switch ( $Code_3DS ) {
			case 1:
				$display = self::display_tooltip( '3', 0 );
				$message = __( 'Manually keyed (MOTO) (card not present)', 'ag_epdq_server' );
				break;
			case 2:
				$display = self::display_tooltip( '3', 3 );
				$message = __( 'Recurring (from MOTO)', 'ag_epdq_server' );
				break;
			case 3:
				$display = self::display_tooltip( '3', 2 );
				$message = __( 'Installment payments', 'ag_epdq_server' );
				break;
			case 4:
				$display = self::display_tooltip( '3', 2 );
				$message = __( 'Manually keyed, card present', 'ag_epdq_server' );
				break;
			case 5:
				$display = self::display_tooltip( '3', 1 );
				$message = __( 'Cardholder identification successful', 'ag_epdq_server' );
				break;
			case 6:
				$display = self::display_tooltip( '3', 2 );
				$message = __( 'Merchant supports identification but not cardholder,', 'ag_epdq_server' );
				break;
			case 7:
				$display = self::display_tooltip( '3', 0 );
				$message = __( 'E-commerce with SSL encryption', 'ag_epdq_server' );
				break;
			case 9:
				$display = self::display_tooltip( '3', 3 );
				$message = __( 'Recurring (from e-commerce)', 'ag_epdq_server' );
				break;
			case 'EMPTY':
				$display = self::display_tooltip( '3', 0 );
				$message = __( 'Parameters for 3D has not been set.', 'ag_epdq_server' );
				break;
			default:
				$display = self::display_tooltip( '3', 0 );
				$message = "No data was returned";
		}

		return [
			'display' => $display,
			'message' => '<strong> 3D: </strong> ' . $message
		];
	}

	/**
	 * @param $columns
	 *
	 * @return array
	 */
	public function custom_shop_order_column( $columns ) {

		$ePDQ_settings = new epdq_checkout();
		$fraudCheck = $ePDQ_settings->fraudCheck;

		$reordered_columns = array();
		foreach( $columns as $key => $column ) {
			$reordered_columns[ $key ] = $column;

			if( ( $key === 'order_status' ) && isset( $fraudCheck ) && $fraudCheck === 'yes' ) {
				$reordered_columns['checks'] = __( 'AG Traffic Light System', 'ag_epdq_server' );
			}
		}

		return $reordered_columns;
	}

	/**
	 * @param $column
	 * @param $post_id
	 *
	 * @return void
	 */
	public function custom_orders_list_column_content( $column, $post_id ) {

		if( $column === 'checks' ) {
			$order = new WC_Order( $post_id );
			$ePDQ_settings = new epdq_checkout();
			$fraudCheck = $ePDQ_settings->fraudCheck;

			$keys = array_column( $order->get_meta_data(), 'key' );
			$search_postal_check = array_search( 'AAVZIP', $keys, TRUE );
			$search_CVC_check = array_search( 'CVCCheck', $keys, TRUE );
			$search_3D_check = array_search( 'ECI', $keys, TRUE );
			$search_aav_check = array_search( 'AAVCheck', $keys, TRUE );

			if( isset( $fraudCheck ) && $fraudCheck === 'no' ) {
				return;
			}

			// Is EPDQ order check
			if( $order->get_payment_method() !== 'epdq_checkout' ) {
				return;
			}

			if( $order->get_meta( 'is_moto' ) ) {
				return;
			}

			if( $order->get_status() === 'cancelled' || $order->get_status() === 'pending' ) {
				return;
			}

			if( empty( $search_3D_check ) || empty( $search_CVC_check ) || empty( $search_postal_check ) || empty( $search_aav_check ) ) {
				return;
			}

			$display = $this->get_order_details( $order );

			if( ! $display ) {
				return;
			}
			echo $display['address_code_display']['display'];
			echo $display['postal_code_display']['display'];
			echo $display['cvc_code_display']['display'];
			echo $display['Code_3DS_display']['display'];
		}

	}

}