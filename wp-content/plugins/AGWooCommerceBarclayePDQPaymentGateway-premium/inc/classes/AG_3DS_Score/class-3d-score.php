<?php
/*-----------------------------------------------------------------------------------*/
/*	AG 3D Secure score
/*-----------------------------------------------------------------------------------*/
defined( 'ABSPATH' ) || die( "No script kiddies please!" );


if ( class_exists( 'ePDQ_3D_score' ) ) {
	return;
}

class ePDQ_3D_score {

	public static $data = array();
	public static $single_instance = null;
	public static $args = array();

	public static function run_instance( $args = array() ) {
		if ( self::$single_instance === null ) {
			self::$args            = $args;
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}


	public static function AG_3D_score_colours( $data ) {

		//todo fix low risk, colour not showing.

		$colour_rating = array(
			'#40F99B' => 'Low Risk',
			'#FD5A40' => 'Medium Risk',
			'#FE1A3B' => 'High Risk',
			'#40F99B' => 'Perfect'
		);

		if ( in_array( $data, $colour_rating ) ) {
			return array_search( $data, $colour_rating );
		}

	}


	// checking customer data

	public static function AG_display_warnings( $warnings ) {

		if ( ! empty( $warnings ) ) {

			echo '<hr /><div class="report"><ul class="ag-warning">';

			foreach ( $warnings as $warning ) {
				if ( isset( $warning['data'] ) ) {
					echo '<li><span class="dashicons dashicons-warning"></span> ' . $warning['data'] . '</li>';
				}
			}

			echo '</ul></div>';

		}


	}

	public static function strip_points( $score, $subtract ) {

		if ( $score == '' ) {
			return;
		}

		$new_score = ( $score * ( $subtract / 100 ) );

		return number_format( $new_score, 2 );

	}

	public static function AG_build_risk_level() {

		$final_score = ePDQ_3D_score::AG_build_final_score_number();

		if ( ( $final_score >= 0 ) && ( $final_score <= 33 ) ) {
			$risk = 'High Risk';
		} else if ( ( $final_score >= 34 ) && ( $final_score <= 66 ) ) {
			$risk = 'Medium Risk';
		} else if ( ( $final_score >= 67 ) && ( $final_score <= 98 ) ) {
			$risk = 'Low Risk';
		} else if ( ( $final_score >= 99 ) ) {
			$risk = 'Perfect';
		}

		//TODO - above could do with some work too.

		return $risk;
	}

	public static function AG_build_final_score_number() {

		$warning_score = ePDQ_3D_score::AG_get_warning_score();

		$psp_score = '50'; //todo pull in from the above.

		return $warning_score + $psp_score;
	}

	public static function AG_get_warning_score() {

		$warning_data = ePDQ_3D_score::AG_get_order_data();
		$warnings     = $warning_data['data'];

		$score = array();

		foreach ( $warnings as $warning ) {
			$score[] = '1'; //$warning['score'];
		}

		return array_sum( $score );

	}

	public static function AG_get_order_data() {

		global $woocommerce, $post;
		$order_id = $post->ID;
		$order    = wc_get_order( $order_id );

		// Warning data array
		$data                    = array();
		$data['first_order']     = ePDQ_3D_score::AG_first_order( $order );
		$data['ip_check']        = ePDQ_3D_score::AG_IP_check( $order );
		$data['international']   = ePDQ_3D_score::AG_international_check( $order );
		$data['failed_before']   = ePDQ_3D_score::AG_customer_failed_orders( $order );
		$data['refunded_before'] = ePDQ_3D_score::AG_customer_refunded( $order );

		// PSP order data
		ePDQ_3D_score::AG_get_PSP_score( $order );

		return array( 'data' => $data, 'object' => $order );

	}

	public static function AG_first_order( $order ) {

		global $wpdb;
		$customer_id = $order->get_customer_id();
		if ( $customer_id ) {

			$paid_order_statuses = array_map( 'esc_sql', wc_get_is_paid_statuses() );
			// Set the score if this is the customers first order.
			$score = '10';

			$results = $wpdb->get_col( "
                SELECT p.ID FROM {$wpdb->prefix}posts AS p
                INNER JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id
                WHERE p.post_status IN ( 'wc-" . implode( "','wc-", $paid_order_statuses ) . "' )
                AND p.post_type LIKE 'shop_order'
                AND pm.meta_key = '_customer_user'
                AND pm.meta_value = $customer_id
            " );

			if ( count( $results ) == 1 ) {
				return array( 'data' => 'This is the customers first order.', 'score' => $score );
			}

		}
	}

	public static function AG_IP_check( $order ) {

		// Customer IP and location
		$customer_ip = $order->get_customer_ip_address();
		$location    = WC_Geolocation::geolocate_ip( $customer_ip, true );

		// Customer billing country
		$billing = $order->get_billing_country();

		// Set the score if the customers billing and IP country dont match
		$score = '10';

		if ( $location['country'] != $billing ) {
			return array(
				'data'  => 'This customers IP address did not match the given billing country',
				'score' => $score
			);
		}

	}

	public static function AG_international_check( $order ) {

		// Customer billing country
		$country = $order->get_billing_country();

		// Store base country
		$woo_countries = new WC_Countries();
		$base          = $woo_countries->get_base_country();

		// Set the score if the customers billing country is international
		$score = '10';

		if ( $country != $base ) {
			return array( 'data' => 'This order is an international order.', 'score' => $score );
		}

	}

	public static function AG_customer_failed_orders( $order ) {

		$customer_email = $order->get_billing_email();
		$customer       = get_user_by( 'email', $customer_email );

		// Set the score if the customers billing country is international
		$score = '10';

		$count = 0;

		if ( ! empty( $customer ) ) {

			$customer_orders = get_posts( array(
				'numberposts' => - 1,
				'meta_key'    => '_customer_user',
				'meta_value'  => $customer->ID,
				'post_type'   => 'shop_order',
				'post_status' => 'wc-failed'
			) );

			foreach ( $customer_orders as $customer_order ) {
				$count ++;
			}

			if ( $count > 0 ) {
				return array( 'data' => 'This customer has had failed orders before.', 'score' => $score );
			}

		}

	}

	public static function AG_customer_refunded( $order ) {

		$customer_email = $order->get_billing_email();
		$customer       = get_user_by( 'email', $customer_email );

		// Set the score if the customers billing country is international
		$score = '10';

		$count = 0;

		if ( ! empty( $customer ) ) {

			$customer_orders = get_posts( array(
				'numberposts' => - 1,
				'meta_key'    => '_customer_user',
				'meta_value'  => $customer->ID,
				'post_type'   => 'shop_order',
				'post_status' => 'wc-refunded'
			) );

			foreach ( $customer_orders as $customer_order ) {
				$count ++;
			}

			if ( $count > 0 ) {
				return array( 'data' => 'This customer has been refunded before.', 'score' => $score );
			}

		}

	}

	public static function AG_get_PSP_score( $order ) {

		// Score per item send back from PSP
		$psp_score = '5';

		$psp_data = ePDQ_score::check_ePDQ_order_data( $order, $psp_score );

		// Add all the PSP data points together
		$points = 0;
		foreach ( $psp_data as $data ) {
			$points += $data[2];
		}

		echo $points;

		$items = sizeof( $psp_data );

		// todo - Need to work out the score logic
		// NOTE - Might be an idea to add scores to the array get_vc_data() ??

		//return $psp_data;

	}

	public static function activate_3d() {
		if ( ! get_option( 'AG_ePDQ_3D' ) ) {
			add_option( 'AG_ePDQ_3D', true );
		}
	}

}


$ePDQ_3D_score = new ePDQ_3D_score();
register_activation_hook( __FILE__, array( $ePDQ_3D_score, 'activate_3d' ) );