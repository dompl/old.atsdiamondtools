<?php

/*
 * Plugin Name: AG WooCommerce Barclay ePDQ Payment Gateway (Premium)
 * Plugin URI: https://weareag.co.uk/product/ag-barclays-epdq-payment-gateway-woocommerce/
 * Description: Add a Barclay ePDQ payment gateway to WooCommerce 3.4+.
 * Author: We are AG
 * Author URL: https://www.weareag.co.uk/
 * File: index.php
 * Project: AG-woocommerce-epdq-payment-gateway
 * -----
 * Last Modified: Tuesday, 16th October 2018 12:33:09 pm
 * Modified By: Aaron Bowie - We are AG
 * -----
 * Version: 2.11.0
 * WC requires at least: 3.0.0
 * WC tested up to: 3.4.4
 * License: GPL3
 */
/*
Plugin Name: AG WooCommerce Barclay ePDQ Payment Gateway (Premium)
Plugin URI: https://weareag.co.uk/product/ag-barclays-epdq-payment-gateway-woocommerce/
Description: Add a Barclay ePDQ payment gateway to WooCommerce 3.0+.
Version: 2.11.0
Author: Aaron @ We are AG
Author URI: https://www.weareag.co.uk
WC requires at least: 3.0.0
WC tested up to: 3.4.4
*/
defined( 'ABSPATH' ) or die( "No script kiddies please!" );
if ( !defined( 'PLUGIN_VER' ) ) {
    define( 'PLUGIN_VER', '2.11.0' );
}
// Create a helper function for easy SDK access.
function ag_epdq_fs()
{
    global  $ag_epdq_fs ;
    
    if ( !isset( $ag_epdq_fs ) ) {
        // Include Freemius SDK.
        require_once dirname( __FILE__ ) . '/lib/start.php';
        $ag_epdq_fs = fs_dynamic_init( array(
            'id'               => '2715',
            'slug'             => 'AGWooCommerceBarclayePDQPaymentGateway',
            'type'             => 'plugin',
            'public_key'       => 'pk_8024ebca6d61cc1b38ff7a933bc15',
            'is_premium'       => true,
            'is_premium_only'  => true,
            'has_addons'       => false,
            'has_paid_plans'   => true,
            'is_org_compliant' => false,
            'trial'            => array(
            'days'               => 7,
            'is_require_payment' => true,
        ),
            'menu'             => array(
            'slug'       => 'AG_plugins',
            'first-path' => 'admin.php?page=AG_plugins',
            'support'    => false,
            'parent'     => array(
            'slug' => 'AG_plugins',
        ),
        ),
            'is_live'          => true,
        ) );
    }
    
    return $ag_epdq_fs;
}

// Init Freemius.
ag_epdq_fs();
// Signal that SDK was initiated.
do_action( 'ag_epdq_fs_loaded' );
if ( !defined( 'epdq_checkout_dir' ) ) {
    define( 'epdq_checkout_dir', dirname( __FILE__ ) . '/' );
}
add_action( 'plugins_loaded', 'woocommerce_nom_epdq_init', 0 );
function woocommerce_nom_epdq_init()
{
    if ( !class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    }
    /* Localisation */
    load_plugin_textdomain( 'woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    /* Gateway class */
    require_once epdq_checkout_dir . 'class.epdq.php';
    //require_once epdq_checkout_dir . 'class.sub.php';
    /* Add the Gateway to WooCommerce */
    function woocommerce_add_nom_epdq_gateway( $methods )
    {
        //		if ( class_exists( 'WC_Subscriptions_Order' ) ) {
        //				$methods[] = 'epdq_sub';
        //		} else {
        $methods[] = 'epdq_checkout';
        //		}
        return $methods;
    }
    
    add_filter( 'woocommerce_payment_gateways', 'woocommerce_add_nom_epdq_gateway' );
}

// RSS
function ag_dashboard_widget_function()
{
    $rss = fetch_feed( "http://weareag.co.uk/feed/" );
    
    if ( is_wp_error( $rss ) ) {
        
        if ( is_admin() || current_user_can( 'manage_options' ) ) {
            echo  '<p>' ;
            printf( __( '<strong>RSS Error</strong>: %s' ), $rss->get_error_message() );
            echo  '</p>' ;
        }
        
        return;
    }
    
    
    if ( !$rss->get_item_quantity() ) {
        echo  '<p>Apparently, there are no updates to show!</p>' ;
        $rss->__destruct();
        unset( $rss );
        return;
    }
    
    echo  "<ul>\n" ;
    if ( !isset( $items ) ) {
        $items = 5;
    }
    foreach ( $rss->get_items( 0, $items ) as $item ) {
        $publisher = '';
        $site_link = '';
        $link = '';
        $content = '';
        $date = '';
        $link = esc_url( strip_tags( $item->get_link() ) );
        $title = esc_html( $item->get_title() );
        $content = $item->get_content();
        $content = wp_html_excerpt( $content, 250 ) . ' ...';
        echo  "<li><a class='rsswidget' href='{$link}'>{$title}</a>\n<div class='rssSummary'>{$content}</div>\n" ;
    }
    echo  "</ul>\n" ;
    $rss->__destruct();
    unset( $rss );
}

function add_dashboard_widget()
{
    wp_add_dashboard_widget( 'weareag_dashboard_widget', 'Recent Posts from We are AG', 'ag_dashboard_widget_function' );
}

add_action( 'wp_dashboard_setup', 'add_dashboard_widget' );
register_activation_hook( __FILE__, 'epdq_checkout_welcome_screen_activate' );
function epdq_checkout_welcome_screen_activate()
{
    set_transient( '_welcome_screen_activation_redirect', true, 30 );
}

add_action( 'admin_init', 'epdq_checkout_welcome_screen_do_activation_redirect' );
function epdq_checkout_welcome_screen_do_activation_redirect()
{
    // Bail if no activation redirect
    if ( !get_transient( '_welcome_screen_activation_redirect' ) ) {
        return;
    }
    // Delete the redirect transient
    delete_transient( '_welcome_screen_activation_redirect' );
    // Bail if activating from network, or bulk
    if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
        return;
    }
    // Redirect to about page
    wp_safe_redirect( add_query_arg( array(
        'page' => 'welcome-screen-about',
    ), admin_url( 'index.php' ) ) );
}

add_action( 'admin_menu', 'welcome_screen_pages' );
function welcome_screen_pages()
{
    add_dashboard_page(
        'Welcome To We are AG ePDQ',
        'Welcome To We are AG ePDQ',
        'read',
        'welcome-screen-about',
        'welcome_screen_content'
    );
}

function welcome_screen_content()
{
    ?>
<div class="wrap about-wrap">
	<h1>Welcome to AG ePDQ<br /><?php 
    echo  PLUGIN_VER ;
    ?></h1>
	<div class="about-text">Thank you for becoming part of the AG family!<br />Below is some tips on how to setup the plugin and how to get support.</div>
	<div class="ag-badge">Version <?php 
    echo  PLUGIN_VER ;
    ?></div>

	<h2 class="nav-tab-wrapper">
		<a class="nav-tab nav-tab-active" href="<?php 
    echo  site_url() ;
    ?>/wp-admin/index.php?page=welcome-screen-about">Tips & Support</a>
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
				<p>On the left hand side of this page you will see a new item called <a target="_blank" href="<?php 
    echo  site_url() ;
    ?>/wp-admin/admin.php?page=AG-woocommerce-epdq-payment-gateway%2Fclass.epdq.php">AG Licence</a>. This is where you would enter your licence key, if you have issues with activating your licence please email us at support@weareag.co.uk.<br /><br />Some hosting providers have locked down their server or have not updated the server's CA bundle (Fasthosts). If you are having this kind of issue, we highly recommend <a href="http://www.siteground.com/recommended?referrer_id=7042894" target="_blank">SiteGround</a> for WordPress hosting.</p>
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
				<?php 
    add_thickbox();
    ?>
				<ul>
					<li class="agimg">
						<a href="<?php 
    echo  plugin_dir_url( __FILE__ ) ;
    ?>img/ePDQ1.PNG" class="thickbox">
							<img src="<?php 
    echo  plugin_dir_url( __FILE__ ) ;
    ?>img/ePDQ1.PNG" />
							<br />
							<strong>Step one</strong>
						</a>
					</li>
					<li class="agimg">
						<a href="<?php 
    echo  plugin_dir_url( __FILE__ ) ;
    ?>img/epdq2.PNG" class="thickbox">
							<img src="<?php 
    echo  plugin_dir_url( __FILE__ ) ;
    ?>img/epdq2.PNG" />
							<br />
							<strong>Step two</strong>
						</a>
					</li>
					<li class="agimg">
						<a href="<?php 
    echo  plugin_dir_url( __FILE__ ) ;
    ?>img/ePDQ3.PNG" class="thickbox">
							<img src="<?php 
    echo  plugin_dir_url( __FILE__ ) ;
    ?>img/ePDQ3.PNG" />
							<br />
							<strong>Step three</strong>
						</a>
					</li>
				</ul>

				<p><br />On step two at the bottom is where you would enter the SHA-OUT pass phrase.</p>
				<div style="clear:both;"></div>
				<br />
				<br /><hr />
			</div>

			<div>
				<h4>ePDQ plugin settings</h4>
				<p>The <a target="_blank" href="<?php 
    echo  site_url() ;
    ?>/wp-admin/admin.php?page=wc-settings&tab=checkout&section=epdq_checkout">settings page</a> for the plugin will only be visible once you have activated the plugin. There are only three things on this page which must be correct for the plugin to work.</p>
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
				<p>Now you successfully set up the plugin why not <a href="https://www.weareag.co.uk/product/ag-barclays-epdq-payment-gateway-woocommerce/" target="_blank">leave a review</a> of how super easy it was to setup?<br />Looking at getting another one of our plugins? <a href="https://www.weareag.co.uk/product/ag-barclays-epdq-payment-gateway-woocommerce/" target="_blank">leave us a review</a>.</p>
				<p>Still having some issues have a read of our <a href="https://weareag.zendesk.com/hc/en-us/categories/360000682532-Barclays-ePDQ-payment-gateway">docs</a></a>
			</div>
		</div>
		<hr />
	</div>
	<div class="return-to-dashboard">
		<a href="<?php 
    echo  site_url() ;
    ?>/wp-admin/admin.php?page=AG-woocommerce-epdq-payment-gateway%2Fclass.epdq.php">Go to activate plugin now</a>
	</div>

</div>
<style>
	.ag-badge {
		background: rgba(0, 0, 0, 0) url("<?php 
    echo  plugin_dir_url( __FILE__ ) ;
    ?>epdq.jpg") no-repeat scroll 0 0;
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
function welcome_screen_remove_menus()
{
    remove_submenu_page( 'index.php', 'welcome-screen-about' );
}

/*-----------------------------------------------------------------------------------*/
/*	GDPR new bits
/*-----------------------------------------------------------------------------------*/
// Adding what we store in custom fields to Woo hook before removing of data.
function remove_epdq_personal_data( $order )
{
    $epdq_meta_to_remove = apply_filters( 'woocommerce_privacy_remove_order_personal_data_meta', array(
        'Acceptance'      => 'text',
        'BRAND'           => 'text',
        'IP'              => 'text',
        'NCERROR'         => 'text',
        'PAYID'           => 'text',
        'PaymentMethod'   => 'text',
        'Status'          => 'text',
        'OrderAmount'     => 'text',
        'TRXDATE'         => 'text',
        'OrderCurrency'   => 'text',
        'OrderID'         => 'text',
        '_transaction_id' => 'text',
    ) );
    if ( !empty($epdq_meta_to_remove) && is_array( $epdq_meta_to_remove ) ) {
        foreach ( $epdq_meta_to_remove as $meta_keys => $data_types ) {
            $value = $order->get_meta( $meta_keys );
            if ( empty($value) || empty($data_types) ) {
                continue;
            }
            $anon_value = ( function_exists( 'wp_privacy_anonymize_data' ) ? wp_privacy_anonymize_data( $data_types, $value ) : '' );
            $anon_value = apply_filters(
                'woocommerce_privacy_remove_order_personal_data_meta_value',
                $anon_value,
                $meta_keys,
                $value,
                $data_types,
                $order
            );
            
            if ( $anon_value ) {
                $order->update_meta_data( $meta_keys, $anon_value );
            } else {
                $order->delete_meta_data( $meta_keys );
            }
        
        }
    }
}

add_action( 'woocommerce_privacy_before_remove_order_personal_data', 'remove_epdq_personal_data' );
function plugin_get_default_privacy_content()
{
    return '<p>' . __( 'In the WooCommerce payments section you should list Barclays ePDQ as a payment processor.<br />
<br />
We accept payments through Barclays ePDQ. When processing payments, some of your data will be passed to Barclays, including information required to process or support the payment, such as the purchase total and billing information.<br /><br />
Once an order has been placed some of the data sent back from Barclays is stored to support the payment and order, this data will remain part of the order until it is deleted.
<br/><br />
Please see Barclays ePDQ Privacy Policy for more details.' ) . '</p>';
}

function plugin_add_suggested_privacy_content()
{
    $content = plugin_get_default_privacy_content();
    wp_add_privacy_policy_content( __( 'AG ePDQ Server Gateway' ), $content );
}

add_action( 'admin_init', 'plugin_add_suggested_privacy_content', 20 );
/*-----------------------------------------------------------------------------------*/
/*	Licence management section
/*-----------------------------------------------------------------------------------*/

if ( !function_exists( 'ag_plugin' ) ) {
    add_action( 'admin_menu', 'ag_plugin' );
    function ag_plugin()
    {
        add_menu_page(
            'AG License Activation Menu',
            'AG Plugins',
            'manage_options',
            'AG_plugins',
            'AG_plugins',
            'dashicons-admin-network'
        );
    }

}

if ( !function_exists( 'AG_plugins' ) ) {
    function AG_plugins()
    {
        ?>
		<div class="wrap about-wrap">
			<h1>AG Plugins</h1>
			<div class="about-text">Thank you for becoming part of the AG family!<br />Below is some tips on how to setup the plugin and how to get support.</div>

			<h2 class="nav-tab-wrapper">
				<a class="nav-tab nav-tab-active">Tips & Support</a>
			</h2>

			<div class="changelog">
				<h3>Activating the plugin</h3>

				<div class="feature-section col two-col">
					<div class="last-feature">
						<h4>Why activate?</h4>
						<p>You must activate the plugin to get the latest updates for the plugin, also we can only give support to the URL which has been activated.</p>
					</div>

				</div>
				<hr />
			</div>

			<div class="changelog">
				<h3>Setting up the plugin</h3>

					<div class="last-feature">
						<p>We aimed to keep this part of the plugin so simple it was silly, set up will only take around five minutes from start to finish. If you would like us to do the setup for you this can be done for £45.00. Simply email us at support@weareag.co.uk.</p>
					</div>
					<hr />

			</div>

			<div class="changelog">
				<h3>Getting support</h3>

					<div class="last-feature">
						<p>On the left hand side under AG Plugins you will see contact us, here you can get in touch with us if you need help or have questions.</p>
					</div>
					<hr />

			</div>


			<div class="changelog">
				<h3>What next?</h3>

				<div class="feature-section col two-col">
					<div class="last-feature">
						<p>Now you successfully set up the plugin why not leave us a review of how super easy it was to setup?<br /></p>
					</div>
				</div>
				<hr />
			</div>

		</div>
		<style>

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
		</style> <?php 
    }

}