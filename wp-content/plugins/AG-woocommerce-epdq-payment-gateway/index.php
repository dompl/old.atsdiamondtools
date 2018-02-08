<?php
# @Author: Aaron Bowie
# @Date:   Monday, May 29th 2017, 6:08:11 pm
# @Email:  support@weareag.co.uk
# @Filename: index.php
# @Last modified by:   Aaron Bowie
# @Last modified time: Wednesday, February 7th 2018, 9:39:26 pm

/*
Plugin Name: AG WooCommerce Barclay ePDQ Payment Gateway
Plugin URI: https://weareag.co.uk/product/ag-barclays-epdq-payment-gateway-woocommerce/
Description: Add a Barclay ePDQ payment gateway to WooCommerce 3.0+.
Version: 2.9.6
Author: Aaron @ We are AG
Author URI: https://www.weareag.co.uk
WC requires at least: 3.0.0
WC tested up to: 3.3.1
*/

defined('ABSPATH') or die("No script kiddies please!");
// Plugin repo bits
$version = '2.9.6';
$plugin_id = 'FEEffFecGNWZq5fx';
$repo_url = 'http://weareag.co.uk/update/';
//$pluginurl = plugin_dir_url( $file );
if( !defined('epdq_checkout_dir') )
	define('epdq_checkout_dir', dirname(__FILE__) . '/' );
add_action('plugins_loaded', 'woocommerce_nom_epdq_init', 0);

function woocommerce_nom_epdq_init() {
	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

/* Localisation */
	load_plugin_textdomain('woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');

/* Gateway class */
require_once epdq_checkout_dir . 'class.epdq.php';
//require_once epdq_checkout_dir . 'class.sub.php';


/* Add the Gateway to WooCommerce */
	function woocommerce_add_nom_epdq_gateway($methods) {

//		if ( class_exists( 'WC_Subscriptions_Order' ) ) {
//				$methods[] = 'epdq_sub';
//		} else {
				$methods[] = 'epdq_checkout';
//		}
		return $methods;

	}
	add_filter('woocommerce_payment_gateways', 'woocommerce_add_nom_epdq_gateway' );
}


function plugin_activated() {
	// Once the plugin is activated and the user has allowed us to track minimal data send the data.

	// Notify of plugin activated on URL for plugin support only as per our support T&C's
	$url = site_url();
	$admin = get_option( 'admin_email' );
	$support = __( " Website url: $url\n\n admin email: $admin");
	wp_mail('hi@weareag.co.uk', 'ePDQ plugin activated' , $support);
}
register_activation_hook( __FILE__, "plugin_activated");


// RSS
function ag_dashboard_widget_function() {
	$rss = fetch_feed( "http://weareag.co.uk/feed/" );

	if ( is_wp_error($rss) ) {
		if ( is_admin() || current_user_can('manage_options') ) {
			echo '<p>';
			printf(__('<strong>RSS Error</strong>: %s'), $rss->get_error_message());
			echo '</p>';
		}
		return;
	}

	if ( !$rss->get_item_quantity() ) {
		echo '<p>Apparently, there are no updates to show!</p>';
		$rss->__destruct();
		unset($rss);
		return;
	}

	echo "<ul>\n";

	if ( !isset($items) )
		$items = 5;

	foreach ( $rss->get_items(0, $items) as $item ) {
		$publisher = '';
		$site_link = '';
		$link = '';
		$content = '';
		$date = '';
		$link = esc_url( strip_tags( $item->get_link() ) );
		$title = esc_html( $item->get_title() );
		$content = $item->get_content();
		$content = wp_html_excerpt($content, 250) . ' ...';

		echo "<li><a class='rsswidget' href='$link'>$title</a>\n<div class='rssSummary'>$content</div>\n";
	}

	echo "</ul>\n";
	$rss->__destruct();
	unset($rss);
}

function add_dashboard_widget() {
	wp_add_dashboard_widget('weareag_dashboard_widget', 'Recent Posts from We are AG', 'ag_dashboard_widget_function');
}

add_action('wp_dashboard_setup', 'add_dashboard_widget');

// defined
if ( ! defined( 'AG_Repo' ) ) {
	define( 'AG_Repo', $repo_url );
}
if ( ! defined( 'plugin_id' ) ) {
	define( 'plugin_id', $plugin_id );
}
if ( ! defined( 'PLUGIN_VER' ) ) {
	define( 'PLUGIN_VER', $version );
}
if ( ! defined( 'AGD_KEY' ) ) {
	define( 'AGD_KEY', '56cdf3f59bdb87.92903929' );
}
if ( ! defined( 'AGD' ) ) {
	define( 'AGD', 'https://www.weareag.co.uk' );
}
if ( ! defined( 'ITEM' ) ) {
	define( 'ITEM', '1283' );
}

// check for updates
add_action( 'plugins_loaded', 'rkv_load_updater' );

function rkv_load_updater() {

	if ( ! class_exists( 'RKV_Remote_Updater' ) ) {
		include( 'lib/RKV_Remote_Updater.php' );
	}
}

$license_key = get_option('AGD_ePDQ_Key');
$api_params = array(
	'slm_action' => 'slm_check',
	'secret_key' => AGD_KEY,
	'license_key' => $license_key,
);

$query = esc_url_raw(add_query_arg($api_params, AGD));
$response = wp_remote_get($query, array('timeout' => 20, 'sslverify' => false));
$license_data = json_decode(wp_remote_retrieve_body($response));
//var_dump($license_data);
if($license_data->result == 'success'){
// Run the updates
add_action( 'admin_init', 'rkv_remote_update' );
}
function rkv_remote_update() {

	// ensure the class exists before running
	if ( ! class_exists( 'RKV_Remote_Updater' ) ) {
		return;
	}

	$updater = new RKV_Remote_Updater( AG_Repo, __FILE__, array(
		'unique'    => plugin_id,
		'version'   => PLUGIN_VER,
	)
	);
}


register_activation_hook( __FILE__, 'epdq_checkout_welcome_screen_activate' );
function epdq_checkout_welcome_screen_activate() {
	set_transient( '_welcome_screen_activation_redirect', true, 30 );
}

add_action( 'admin_init', 'epdq_checkout_welcome_screen_do_activation_redirect' );
function epdq_checkout_welcome_screen_do_activation_redirect() {
	// Bail if no activation redirect
	if ( ! get_transient( '_welcome_screen_activation_redirect' ) ) {
		return;
	}

	// Delete the redirect transient
	delete_transient( '_welcome_screen_activation_redirect' );

	// Bail if activating from network, or bulk
	if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
		return;
	}

	// Redirect to bbPress about page
	wp_safe_redirect( add_query_arg( array( 'page' => 'welcome-screen-about' ), admin_url( 'index.php' ) ) );

}

add_action('admin_menu', 'welcome_screen_pages');

function welcome_screen_pages() {
	add_dashboard_page(
		'Welcome To We are AG ePDQ',
		'Welcome To We are AG ePDQ',
		'read',
		'welcome-screen-about',
		'welcome_screen_content'
	);
}

function welcome_screen_content() {
?>
<div class="wrap about-wrap">
	<h1>Welcome to AG ePDQ<br /><?php echo PLUGIN_VER; ?></h1>
	<div class="about-text">Thank you for becoming part of the AG family!<br />Below is some tips on how to setup the plugin and how to get support.</div>
	<div class="ag-badge">Version <?php echo PLUGIN_VER; ?></div>

	<h2 class="nav-tab-wrapper">
		<a class="nav-tab nav-tab-active" href="<?php echo site_url(); ?>/wp-admin/index.php?page=welcome-screen-about">Tips & Support</a>
	</h2>

	<div class="changelog">
		<h3>Activating the plugin</h3>

		<div class="feature-section col two-col">
			<div class="last-feature">
				<h4>Why activate?</h4>
				<p>You must activate the plugin to get the latest updates for the plugin, also we can only give support to the URL which has been activated.</p>
			</div>

			<div>
				<h4>How to activate</h4>
				<p>On the left hand side of this page you will see a new item called <a target="_blank" href="<?php echo site_url(); ?>/wp-admin/admin.php?page=AG-woocommerce-epdq-payment-gateway%2Fclass.epdq.php">AG Licence</a>. This is where you would enter your licence key, if you have issues with activating your licence please email us at support@weareag.co.uk.<br /><br />Some hosting providers have locked down their server or have not updated the server's CA bundle (Fasthosts). If you are having this kind of issue, we highly recommend <a href="http://www.siteground.com/recommended?referrer_id=7042894" target="_blank">SiteGround</a> for WordPress hosting.</p>
			</div>
		</div>
		<hr />
	</div>

	<div class="changelog">
		<h3>Setting up the plugin</h3>

		<div class="feature-section col one-col">
			<div class="last-feature">
				<p>We aimed to keep this part of the plugin so simple it was silly, set up will only take around five minutes from start to finish. Below is our guide on the setup, if you would like us to do the setup for you this can be done for £45.00. Simply email us at support@weareag.co.uk.</p>
			</div>
		</div>

		<div class="feature-section col three-col">
			<div>
				<h4>Barclays ePDQ back office</h4>
				<p>In the back office we only need to make four changes to get the plugin to work. To login in to the testing back office <a href="https://mdepayments.epdq.co.uk/Ncol/Test/BackOffice/login/index?branding=EPDQ&CSRFSP=%2fncol%2ftest%2fbackoffice%2fhome%2findex&CSRFKEY=90FD337207FCB0253991B0701A69D23FD5FD7132&CSRFTS=20160411165450" target="_blank">click here</a>, for the live back office <a href="https://payments.epdq.co.uk/Ncol/Prod/BackOffice/login/index?branding=EPDQ&CSRFSP=%2fncol%2fprod%2fbackoffice%2fhome%2findex&CSRFKEY=85BA29B66E89DDE7D43AAD241336280BB375793E&CSRFTS=20160411165602" target="_blank">click here</a>. Here are some screenshots of the four steps to get it to work. (Click on the images)</p>
				<?php add_thickbox(); ?>
				<ul>
					<li class="agimg">
						<a href="<?php echo plugin_dir_url( __FILE__ ); ?>img/ePDQ1.PNG" class="thickbox">
							<img src="<?php echo plugin_dir_url( __FILE__ ); ?>img/ePDQ1.PNG" />
							<br />
							<strong>Step one</strong>
						</a>
					</li>
					<li class="agimg">
						<a href="<?php echo plugin_dir_url( __FILE__ ); ?>img/epdq2.PNG" class="thickbox">
							<img src="<?php echo plugin_dir_url( __FILE__ ); ?>img/epdq2.PNG" />
							<br />
							<strong>Step two</strong>
						</a>
					</li>
					<li class="agimg">
						<a href="<?php echo plugin_dir_url( __FILE__ ); ?>img/ePDQ3.PNG" class="thickbox">
							<img src="<?php echo plugin_dir_url( __FILE__ ); ?>img/ePDQ3.PNG" />
							<br />
							<strong>Step three</strong>
						</a>
					</li>
					<!--<li class="agimg">
						<a href="<?php echo plugin_dir_url( __FILE__ ); ?>img/epdq4.PNG" class="thickbox">
							<img src="<?php echo plugin_dir_url( __FILE__ ); ?>img/epdq4.PNG" />
							<br />
							<strong>Step four</strong>
						</a>
					</li> -->
				</ul>

				<p><br />On step two at the bottom is where you would enter the SHA-OUT pass phrase.</p>
				<div style="clear:both;"></div>
				<br />
				<br /><hr />
			</div>

			<div>
				<h4>ePDQ plugin settings</h4>
				<p>The <a target="_blank" href="<?php echo site_url(); ?>/wp-admin/admin.php?page=wc-settings&tab=checkout&section=epdq_checkout">settings page</a> for the plugin will only be visible once you have activated the plugin. There are only three things on this page which must be correct for the plugin to work.</p>
				<br />
				<ul>
					<li><strong>PSPID</strong> - The PSPID for your Barclays account. This is the id which you use to login the back office of the Barclays bank.</li>
					<li><strong>SHA-IN & SHA-OUT Passphrase</strong> - These are used for better security between the shop and Barclays, we recommend keeping it to letters and numbers.</li>
					<li><strong>SHA encryption method</strong> - This must match what you set in the back office of Barclays.</li>
				</ul>
				<br /><hr />
			</div>

			<div class="last-feature">
				<h4>Test payment</h4>
				<p>With your ePDQ back office set as testing and the plugin store status set as test, simply add some items to your basket, go through the process of adding your address information and click to pay via the ePDQ plugin. Once you have been prompted to enter some bank card details simple enter the following: <br /><br />
					Test card number: <strong>4444333322221111</strong>
					<br />
					Expiry date (mm/yyyy): <strong>Any in date</strong>
					<br />
					Card verification code: <strong>123</strong><br /><br />Once the payment has been confirmed you will be taken back to your website and the test payment has been made. When you have finished testing make sure that you turn both the ePDQ back office and the plugin store status to <strong>LIVE</strong>. You are now good to go! Well done.  </p>
			</div>
		</div>
	</div>
	<div class="changelog">
		<h3>What next?</h3>

		<div class="feature-section col two-col">
			<div class="last-feature">
				<p>Now you successfully set up the plugin why not <a href="https://www.weareag.co.uk/product/ag-barclays-epdq-payment-gateway-woocommerce/#tab-reviews" target="_blank">leave a review</a> of how super easy it was to setup?<br />Looking at getting another one of our plugins? <a href="https://www.weareag.co.uk/product/ag-barclays-epdq-payment-gateway-woocommerce/#tab-reviews" target="_blank">leave us a review</a> and we will give you a <strong>discount code</strong> off your next order with us.</p>
				<p><strong>What to show off your website using our plugin? make sure you tick the option in the plugin <a target="_blank" href="<?php echo site_url(); ?>/wp-admin/admin.php?page=wc-settings&tab=checkout&section=epdq_checkout">settings page</a>, if you are picked we will email you telling you with a discount code off your next order with us.</strong></p>
			</div>
		</div>
		<hr />
	</div>
	<div class="return-to-dashboard">
		<a href="<?php echo site_url(); ?>/wp-admin/admin.php?page=AG-woocommerce-epdq-payment-gateway%2Fclass.epdq.php">Go to activate plugin now</a>
	</div>

</div>
<style>
	.ag-badge {
		background: rgba(0, 0, 0, 0) url("<?php echo plugin_dir_url( __FILE__ ); ?>epdq.jpg") no-repeat scroll 0 0;
		color: #fff;
		font-size: 14px;
		font-weight: bold;
		height: 50px;
		margin: 0 -5px;
		padding-top: 92px;
		text-align: center;
		width: 173px;
	}

	.about-wrap .ag-badge {
		position: absolute;
		right: 0;
		top: 0;
	}
	.about-wrap {
		font-size: 15px;
		margin: 25px 40px 0 20px;
		max-width: 1050px;
		position: relative;
	}
	.about-wrap img {
		width:100%;
		height: 210px;
	}
	.thickbox strong {
		text-align: center;
		display: block;
		width: 100%
	}
	.agimg {
		width: 48%;
		float:left;
		padding:8px;
	}
</style>
<?php
}
add_action( 'admin_head', 'welcome_screen_remove_menus' );
function welcome_screen_remove_menus() {
	remove_submenu_page( 'index.php', 'welcome-screen-about' );
}



// new plugin notice

add_action('admin_notices', 'new_plugin_notice');

function new_plugin_notice() {
    global $current_user ;
    $user_id = $current_user->ID;
    /* Check that the user hasn't already clicked to ignore the message */
    if ( ! get_user_meta($user_id, 'new_plugin_notice') ) { ?>
<div class="notice error licence-notice" style="position: relative;">
    <p>Thank you for using our WooCommerce ePDQ payment gateway, We have a new plugin called: <strong>Barclaycard ePDQ Direct Link Payment Gateway for WooCommerce</strong>.<br />
		This plugin enables you to take payments direct on your website, Super simple to setup.</p>
    <br />
    <a class="button-primary" target="_blank" href="https://www.weareag.co.uk/product/barclaycard-epdq-direct-link-payment-gateway-woocommerce/" style="margin-bottom: 10px;">View the plugin</a>
    <?php printf(__('<a href="%1$s"><button type="button" class="notice-dismiss"><span class="screen-reader-text">Hide Notice</span></button></a>'), '?ignore=0'); ?>
</div>
<?php
                                                      }
}

add_action('admin_init', 'ignore');

function ignore() {
    global $current_user;
    $user_id = $current_user->ID;
    /* If user clicks to ignore the notice, add that to their user meta */
    if ( isset($_GET['ignore']) && '0' == $_GET['ignore'] ) {
        add_user_meta($user_id, 'new_plugin_notice', 'true', true);
    }
}
