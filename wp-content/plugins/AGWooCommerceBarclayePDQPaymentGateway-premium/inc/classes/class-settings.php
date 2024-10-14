<?php
/*-----------------------------------------------------------------------------------*/

/*	AG ePDQ settings
/*-----------------------------------------------------------------------------------*/
defined( 'ABSPATH' ) || die( "No script kiddies please!" );

if( class_exists( 'AG_ePDQ_Settings' ) ) {
	return;
}

class AG_ePDQ_Settings {

	/**
	 * Plugin settings
	 *
	 * @return array[]
	 */
	public static function form_fields() {

		return array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'ag_epdq_server' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable ePDQ Checkout', 'ag_epdq_server' ),
				'default' => 'no'
			),

			'title'       => array(
				'title'       => __( 'Title', 'ag_epdq_server' ),
				'type'        => 'text',
				'description' => __( 'Title of the payment process. This name will be visible throughout the site and the payment page.', 'ag_epdq_server' ),
				'default'     => 'AG ePDQ Checkout',
				'desc_tip'    => TRUE
			),
			'description' => array(
				'title'       => __( 'Description', 'ag_epdq_server' ),
				'type'        => 'textarea',
				'description' => __( 'Description of the payment process. This description will be visible throughout the site and the payment page.', 'ag_epdq_server' ),
				'default'     => 'Use the secure payment processor of Barclaycard and checkout with your debit/credit card.',
				'desc_tip'    => TRUE
			),
			'access_key'  => array(
				'title'       => __( 'PSPID', 'ag_epdq_server' ),
				'type'        => 'text',
				'description' => __( 'The PSPID for your barclays account. This is the id which you use to login the admin panel of the barclays bank. <small><strong>Remember you must test using the test ePDQ account Barclays supply you</strong></small>', 'ag_epdq_server' ),
				'default'     => '',
				//'desc_tip'      => true
			),
			'status'      => array(
				'title'       => __( 'Store Status', 'ag_epdq_server' ),
				'type'        => 'select',
				'options'     => array( 'test' => 'Test Environment', 'live' => 'Live Store' ),
				'description' => __( 'The status of your store tells that are you actually ready to run your shop or its still a test environment. If the test is selected then no payments will be processed. For details please refer to the user guide provided by the Barclays EPDQ servise.', 'ag_epdq_server' ),
				'default'     => '',
				'css'         => 'height: 35px;',
				'desc_tip'    => TRUE,
			),
			'sha_in'      => array(
				'title'       => __( 'SHA-IN Passphrase', 'ag_epdq_server' ),
				'type'        => 'password',
				'description' => __( 'The SHA-IN signature will encode the data passed to the payment processor to ensure better security.', 'ag_epdq_server' ),
				//'desc_tip'      => true
			),
			'sha_out'     => array(
				'title'       => __( 'SHA-OUT Passphrase', 'ag_epdq_server' ),
				'type'        => 'password',
				'description' => __( 'The SHA-OUT signature will encode the data passed back from the payment processor to ensure better security.', 'ag_epdq_server' ),
				//'desc_tip'      => true
			),
			'sha_method'  => array(
				'title'       => __( 'SHA encryption method', 'ag_epdq_server' ),
				'type'        => 'select',
				'css'         => 'height: 35px;',
				'options'     => array(
					0 => 'SHA-1 (We do not recommend this option, please use another)',
					1 => 'SHA-256',
					2 => 'SHA-512'
				),
				'description' => __( 'Sha encryption method - this needs to match what you have set in the ePDQ back office.', 'ag_epdq_server' ),
				'default'     => '',
				'desc_tip'    => TRUE,
			),

			'webhook' => array(
				'title'       => __( 'Webhook', 'ag_epdq_server' ),
				'type'        => 'hidden',
				'description' => __( 'The plugin now has a custom webhook, read through our doc <a href="https://weareag.co.uk/docs/barclays-epdq-payment-gateway/webhook/setting-up-the-new-webhook-feature/" target="_blank">here</a>.<br />Your URL for the ePDQ back office is: <strong>' . rtrim( WC()->api_request_url( 'epdq_checkout_webhook/?PARAMVAR=' . htmlspecialchars( '<PARAMVAR>' ) ), '/' ) . '</strong>', 'ag_epdq_server' ),
			),

			'cardtypes' => array(
				'title'       => __( 'Accepted Cards', 'ag_epdq_server' ),
				'type'        => 'multiselect',
				'class'       => 'chosen_select',
				'css'         => 'width: 350px;',
				'description' => __( 'Select which card types to accept. This is to show card icons on the checkout only.', 'ag_epdq_server' ),
				'default'     => '',
				'options'     => array(
					'mastercard' => __( 'MasterCard', 'ag_epdq_server' ),
					'amex'       => __( 'American Express', 'ag_epdq_server' ),
					'maestro'    => __( 'Maestro', 'ag_epdq_server' ),
					'visa'       => __( 'Visa', 'ag_epdq_server' ),
					'jcb'        => __( 'JCB', 'ag_epdq_server' ),
					'diners'     => __( 'Diners', 'ag_epdq_server' ),
					'discover'   => __( 'Discover', 'ag_epdq_server' ),
				),
			),

			'debug' => array(
				'title'       => __( 'Enable Debug', 'ag_epdq_server' ),
				'type'        => 'checkbox',
				'label'       => 'Enable debug reporting',
				'default'     => 'no',
				'description' => 'To view the log go <a href="' . site_url( '/wp-admin/admin.php?page=wc-status&tab=logs' ) . '">here</a> and find <strong>AG-WooCommerce-Barclays-ePDQ-Payment-Gateway</strong> in the WooCommerce logs',
				'desc_tip'    => FALSE
			),

		);
	}

	public static function api_fields() {

		return array(
			'refund'       => array(
				'title'   => __( 'REST API', 'ag_epdq_server' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable the REST API', 'ag_epdq_server' ),
				'default' => '',
			),
			'api_user'     => array(
				'title' => __( 'API User IDs', 'ag_epdq_server' ),
				'type'  => 'text',
			),
			'api_password' => array(
				'title' => __( 'API User Password', 'ag_epdq_server' ),
				'type'  => 'password',
			),
			'api_REFID'    => array(
				'title'    => __( 'API REFID', 'ag_epdq_server' ),
				'type'     => 'text',
				'desc_tip' => __( 'you will find this under the user ID in edit user on the ePDQ back office.', 'ag_epdq_server' ),
			),
		);
	}

	public static function form_advanced() {

		return array(

			'notice' => array(
				'title'   => __( 'Enable the redirect notice', 'ag_epdq_server' ),
				'type'    => 'checkbox',
				'label'   => __( 'Show customers a notice at checkout that they will be redirected to Barclays server to complete the payment securely', 'ag_epdq_server' ),
				'default' => 'no'
			),

			'fraudCheck' => array(
				'title'       => __( 'Enable AG Traffic Light System', 'ag_epdq_server' ),
				'type'        => 'checkbox',
				'description' => 'You will need to setup some parameters on transaction feedback <a href="https://mdepayments.epdq.co.uk/Ncol/Test/BackOffice/login/"> here</a> to be able to use this',
				'default'     => 'no',
				'desc_tip'    => FALSE
			),

			'template'   => array(
				'title'       => __( 'Dynamic template URL', 'ag_epdq_server' ),
				'type'        => 'text',
				'description' => __( 'The dynamic template page allows you to customise the design of the payment pages in a more advanced way than the static template does.

                    When you use a dynamic template page, you fully design your own template page, leaving just one area in that page to be completed by our system. The URL of your template page needs to be sent to us in the hidden fields for each transaction.

                    Please bear in mind that using a dynamic template page involves an additional request from our system to look up your template page. This increases the time needed for the payment process.', 'ag_epdq_server' ),
				'desc_tip'    => TRUE
			),
			'pmlisttype' => array(
				'title'       => __( 'Layout of the payment methods', 'ag_epdq_server' ),
				'type'        => 'select',
				'class'       => 'chosen_select',
				'css'         => 'width: 350px;',
				'description' => __( 'You can arrange the layout/list of the payment methods on Barclays payment page', 'ag_epdq_server' ),
				'default'     => '0',
				'options'     => array(
					'0' => __( 'Horizontally grouped logos with the group name on the left (default value)', 'ag_epdq_server' ),
					'1' => __( 'Horizontally grouped logos with no group names', 'ag_epdq_server' ),
					'2' => __( 'Vertical list of logos with specific payment method or brand name', 'ag_epdq_server' ),
				),
			),

			'logo' => array(
				'title'       => __( 'Logo for payment page', 'ag_epdq_server' ),
				'type'        => 'text',
				'label'       => __( 'Upload image for payment page', 'ag_epdq_server' ),
				'default'     => '',
				'description' => 'Add an image to display on the payment page - paste in the full image url <strong>You must have an SSL for this to work correctly.</strong>',
				'desc_tip'    => FALSE
			),

			'token' => array(
				'title'       => __( 'Enable/Disable', 'ag_epdq_server' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Tokenization', 'ag_epdq_server' ),
				'default'     => 'no',
				'description' => __( 'Enable to allow customers to save their payment methods for future checkouts. Customers will be able to manage their saved payment methods on the WooCommerce My Account page. <br />When this is enabled, saved credit card information isn’t stored on your site’s server. It is tokenized and stored on ePDQ’s servers.<br /><strong><a href="https://weareag.co.uk/docs/barclays-epdq-payment-gateway/setup-barclays-epdq-payment-gateway/utilizing-credit-card-tokenization/" target="_blank">Please follow our guide here on the full setup of this feature.</a></strong>', 'ag_epdq_server' ),
			),

			'tip' => array(
				'title'       => __( 'Tip', 'ag_epdq_server' ),
				'type'        => 'hidden',
				'description' => __( 'Want to store your API details in a more secure way, read through our doc <a href="https://weareag.co.uk/docs/barclays-epdq-payment-gateway/setup-barclays-epdq-payment-gateway/storing-strong-api-credentials/" target="_blank">here</a>', 'ag_epdq_server' ),
			),

		);
	}


	public static function status_check() {

		// Get all order statuses, including custom ones
		$order_statuses = wc_get_order_statuses();

		// List of statuses to exclude
		$exclude_statuses = array(
			'wc-processing',
			'wc-completed',
			'wc-cancelled',
			'wc-refunded',
			'wc-failed',
			'wc-checkout-draft'
		);

		// Filter out the excluded statuses
		$filtered_order_statuses = array_diff_key( $order_statuses, array_flip( $exclude_statuses ) );

		return array(

			'statusCheck' => array(
				'title'    => __( 'Disable auto Status Check', 'ag_epdq_server' ),
				'type'     => 'checkbox',
				'label'    => 'Disable the Automated Status Checks. This uses the <a target="_blank" href="https://weareag.co.uk/docs/general/tips/how-to-resolve-unpaid-order-cancelled-time-limit-reached/">WooCommerce hold stock</a> timer.<br />When WooCommerce automatically cancels an order due to the expiration of the hold period, this function will perform a one-time check to verify the order\'s final status.<br /><strong>Note: This is distinct from the Scheduled Status Check feature, which periodically checks the status of orders.</strong>',
				'default'  => 'no',
				'desc_tip' => FALSE
			),

			'autostatus'      => array(
				'title'    => __( 'Enable Scheduled Status Check (Beta)', 'ag_epdq_server' ),
				'type'     => 'checkbox',
				'label'    => 'Enable this feature to automatically monitor unpaid orders at regular intervals until a response is received from the ePDQ.<br />It will continue to check the status of each order and update it accordingly—whether it\'s marked as cancelled, failed, or paid.<br />If the customer has not yet been redirected to the payment page, the order status will remain unchanged, and the feature will persistently poll ePDQ for updates.<br />Set the frequency of these checks bellow to ensure timely and accurate order status updates.<br /><strong>Learn more about this feature <a href="https://weareag.co.uk/docs/barclays-epdq-payment-gateway/status-check/scheduled-status-check-setup/" target="_blank">here</a>.</strong>',
				'default'  => 'no',
				'desc_tip' => FALSE
			),
			'order_status'    => array(
				'title'       => __( 'Order Status', 'ag_epdq_server' ),
				'type'        => 'multiselect',
				'class'       => 'chosen_select',
				'description' => __( 'Select the order statuses that should trigger the scheduled status checks. This setting allows you to tailor the monitoring process to specific order conditions.', 'ag_epdq_server' ),
				'default'     => 'wc-pending',
				'desc_tip'    => FALSE,
				'options'     => $filtered_order_statuses,
			),
			'check_interval'  => array(
				'title'       => __( 'Check Interval', 'ag_epdq_server' ),
				'type'        => 'select',
				'description' => __( 'Set the frequency at which the system checks the status of unpaid orders. Choose an interval that best suits your operational needs and ensures timely updates.', 'ag_epdq_server' ),
				'default'     => '86400',
				'desc_tip'    => FALSE,
				'options'     => array(
					'86400' => __( 'Once a day', 'ag_epdq_server' ),
					'43200' => __( 'Twice a day (every 12 hours)', 'ag_epdq_server' ),
					'28800' => __( 'Three times a day (every 8 hours)', 'ag_epdq_server' ),
					'21600' => __( 'Four times a day (every 6 hours)', 'ag_epdq_server' ),
					'14400' => __( 'Every four hours', 'ag_epdq_server' ),
					'10800' => __( 'Every three hours', 'ag_epdq_server' ),
					'7200'  => __( 'Every two hours', 'ag_epdq_server' ),
					'3600'  => __( 'Every hour', 'ag_epdq_server' ),
					'1800'  => __( 'Every 30 minutes (beta - may cause performance issues)', 'ag_epdq_server' ),
					'900'   => __( 'Every 15 minutes (beta - may cause performance issues)', 'ag_epdq_server' ),
				),
			),
			'actionscheduler' => array(
				'title'       => __( 'Monitor Scheduler Status', 'ag_epdq_server' ),
				'type'        => 'hidden',
				'description' => __( 'Click  <a href="' . site_url( '/wp-admin/admin.php?page=wc-status&status=pending&tab=action-scheduler&s=ag_check_unpaid_orders' ) . '" target="_blank">here</a> to view and manage the status of the Action Scheduler. This allows you to oversee scheduled tasks and ensure they are running as expected.', 'ag_epdq_server' ),
			),

			'cancelblock'     => array(
				'title'    => __( 'Block WooCommerce Auto Cancel', 'ag_epdq_server' ),
				'type'     => 'checkbox',
				'label'    => 'Enable this feature to prevent WooCommerce from automatically canceling orders due to timeout.<br />When activated, this setting ensures that the Scheduled Status Check feature retains control over order statuses, allowing it to complete its checks without interruption.<br />This is particularly useful for maintaining the integrity of order monitoring, especially when awaiting updates from ePDQ.',
				'default'  => 'no',
				'desc_tip' => FALSE
			),
			'cancel_interval' => array(
				'title'       => __( 'Cancel Interval (Beta)', 'ag_epdq_server' ),
				'type'        => 'select',
				'description' => __( 'Set the frequency at which the system auto cancels unpaid orders. Choose an interval that best suits your operational needs and ensures timely updates.', 'ag_epdq_server' ),
				'default'     => '1',
				'desc_tip'    => FALSE,
				'options'     => array(
					'1'  => __( '1 day', 'ag_epdq_server' ),
					'5'  => __( '5 days', 'ag_epdq_server' ),
					'7'  => __( '7 days', 'ag_epdq_server' ),
					'10' => __( '10 days', 'ag_epdq_server' ),
					'15' => __( '15 days', 'ag_epdq_server' ),
					'30' => __( '30 days', 'ag_epdq_server' )
				),
			),

		);
	}

}
