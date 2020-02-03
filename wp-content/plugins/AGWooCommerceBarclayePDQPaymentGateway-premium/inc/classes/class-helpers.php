<?php
/*-----------------------------------------------------------------------------------*/
/*	AG ePDQ functions
/*-----------------------------------------------------------------------------------*/
defined('ABSPATH') or die("No script kiddies please!");


if (class_exists('AG_ePDQ_Helpers')) {
	return;
}


class AG_ePDQ_Helpers
{

	/**
	 * Log errors in WooCommerce logs if debug mode in plugin is enabled or WP_Debug is true.
	 *
	 * @param $message
	 * @param $level
	 * @param $log
	 * @return void
	 */
	public static function ag_log($message, $level, $log)
	{
		if ($log == 'yes' || WP_DEBUG === true ) {
	
			// Log errors in WooCommerce logs
			$message = $message . PHP_EOL;
			$logger = wc_get_logger();
			$logger->$level( $message, array( 'source' => 'AG-WooCommerce-Barclays-ePDQ-Payment-Gateway' ) );

		}
	}


	/**
	 * Loop through returned order data and store.
	 *
	 * @param $post_id
	 * @param $meta
	 * @return void
	 */
	public static function update_order_meta_data($post_id, $meta)
	{
		if (!get_post($post_id) || !is_array($meta)) {
			return false;
		}

		foreach ($meta as $meta_key => $meta_value) {
			if (!empty($meta_value)) {
				update_post_meta($post_id, $meta_key, $meta_value);
			}
		}
	}

	/**
	 * Loop through returned order data and set as notes.
	 *
	 * @param $customer_order
	 * @param $meta
	 * @return void
	 */
	public static function update_order_notes($customer_order, $meta)
	{
		if (!is_array($meta)) {
			return false;
		}

		$order_notes = array();
		foreach ($meta as $key => $value) {
			if (!empty($value) && !empty($key)) {
				if ($value == '') continue;
				$order_notes[] =  $key . ' ' . $value . '<br />';
			}
		}
		$data_back = implode('', $order_notes);
		$customer_order->add_order_note($data_back);
	}
}
