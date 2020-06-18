<?php
defined( 'ABSPATH' ) || die( "No script kiddies please!" );
/*-----------------------------------------------------------------------------------*/
/*	AG up sell
/*-----------------------------------------------------------------------------------*/

class AG_up_sell {

    public static $single_instance = null;
    public static $args = array();
    
    
    /**
	 * run
	 */
	public static function run_instance( $args = array() ) {
		if ( self::$single_instance === null ) {
			self::$args            = $args;
			self::$single_instance = new self();
		}

		return self::$single_instance;
    }


 
    

    public static function setup_plugins() {

        return array(
           'sagepay_direct' =>  array(
               'title'          =>  'SagePay Direct For WooCommerce',
               'plugin_url'     =>  'https://weareag.co.uk/product/sagepay-direct-woocommerce/',
               'dec'            =>  '63% of consumers feel more reassured about their purchase when a SagePay logo is shown on a website.',
               'plugin_img'     =>  'https://weareag.co.uk/wp/wp-content/uploads/2018/10/ag-sagepay.png',
               'plugin_color'   =>  '#323232',  
           ), 
           'sagepay_server' =>  array(
                'title'          =>  'SagePay Server For WooCommerce',
                'plugin_url'     =>  'https://weareag.co.uk/product/sage-pay-server-woocommerce/',
                'dec'            =>  '63% of consumers feel more reassured about their purchase when a SagePay logo is shown on a website.',
                'plugin_img'     =>  'https://weareag.co.uk/wp/wp-content/uploads/2018/10/ag-sagepay.png',
                'plugin_color'   =>  '#323232',  
           ),
           'visa_checkout'  =>  array(
                'title'          =>  'Visa Checkout For WooCommerce',
                'plugin_url'     =>  'https://weareag.co.uk/product/ag-visa-checkout-for-woocommerce/',
                'dec'            =>  'Visa Checkout customers make 30% more transactions per person compared to other customers.',
                'plugin_img'     =>  'https://weareag.co.uk/wp/wp-content/uploads/2018/10/ag-visa.png',
                'plugin_color'   =>  '#1a1e5a',  
           ),
           'epdq_direct'    =>  array(
                'title'          =>  'Barclaycard ePDQ Direct For WooCommerce',
                'plugin_url'     =>  'https://weareag.co.uk/product/barclaycard-epdq-direct-link-payment-gateway-woocommerce/',
                'dec'            =>  'Barclaycard is one of the most established & trusted merchant payment companies in the UK.',
                'plugin_img'     =>  'https://weareag.co.uk/wp/wp-content/uploads/2018/10/ag-barclays.png',
                'plugin_color'   =>  '#543a60', 
           ),
           'epdq_server'    =>  array(
                'title'          =>  'Barclaycard ePDQ Server For WooCommerce',
                'plugin_url'     =>  'https://weareag.co.uk/product/ag-barclays-epdq-payment-gateway-woocommerce/',
                'dec'            =>  'Industry researches have proved that credit card payments on-line increase sales up to 23% because products and services become more easily available to customers.',
                'plugin_img'     =>  'https://weareag.co.uk/wp/wp-content/uploads/2018/10/ag-barclays.png',
                'plugin_color'   =>  '#543a60', 
           ),
           'adyen'          =>  array(
                'title'          =>  'Adyen HPP For WooCommerce',
                'plugin_url'     =>  'https://weareag.co.uk/product/ag-adyen-hpp-woocommerce-gateway/',
                'dec'            =>  'Adyen serves more than 4,500 businesses, including 8 of the 10 largest U.S. Internet companies. Customers include Facebook, Uber, Netflix, Spotify, L’Oreal and Burberry.',
                'plugin_img'     =>  'https://weareag.co.uk/wp/wp-content/uploads/2018/10/ag-adyen.png',
                'plugin_color'   =>  '#071a40', 
           ),
           'pay360'         =>  array(
                'title'          =>  'Pay360 For WooCommerce',
                'plugin_url'     =>  'https://weareag.co.uk/product/ag-woocommerce-pay360-payment-gateway/',
                'dec'            =>  'Pay360 is a leading online payment provider, our Pay360 plugin focuses on the hosted cashier API.',
                'plugin_img'     =>  'https://weareag.co.uk/wp/wp-content/uploads/2018/10/ag-pay360.png',
                'plugin_color'   =>  '#287470', 
           ),
           'safecharge'         =>  array(
                'title'          =>  'SafeCharge For WooCommerce',
                'plugin_url'     =>  'https://weareag.co.uk/product/safecharge-payment-gateway-for-woocommerce/',
                'dec'            =>  'SafeCharge Checkout page is a ready to use, customisable payment page designed to give your customers a smooth payment experience, online and on mobile.',
                'plugin_img'     =>  'https://weareag.co.uk/wp/wp-content/uploads/2019/01/AG-safecharge-WooCommerce-e1548292046998.png',
                'plugin_color'   =>  '#016080', 
            ),
            'lloyds'         =>  array(
                'title'          =>  'Lloyds Cardnet Connect For WooCommerce',
                'plugin_url'     =>  'https://weareag.co.uk/product/lloyds-cardnet-connect-for-woocommerce/',
                'dec'            =>  'Lloyds Bank is one of the leading names in European banking, so it comes as no surprise it provides merchant account services to thousands of firms across the continent and beyond.',
                'plugin_img'     =>  'https://weareag.co.uk/wp/wp-content/uploads/2019/01/ag-cardnet-1.png',
                'plugin_color'   =>  '#006a4e', 
            ),
            'auth'         =>  array(
                'title'          =>  'Authorize.net Hosted For WooCommerce',
                'plugin_url'     =>  'https://weareag.co.uk/product/authorize-net-hosted-for-woocommerce/',
                'dec'            =>  'Authorize.Net has been working with merchants and small businesses since 1996 and is trusted by more than 430,000 merchants.',
                'plugin_img'     =>  'https://weareag.co.uk/wp/wp-content/uploads/2019/03/ag-Authorize.net-Hosted-1.png',
                'plugin_color'   =>  '#1c3141', 
            ),
        );

    }


    public static function get_defined_plugins( $slugs = array() ) {
	
		$plugins          = self::setup_plugins();
        $selected_plugins = array();
        $slugs = self::$args['plugins'];
        

		foreach ( $slugs as $slug ) {
			$selected_plugins[ $slug ] = $plugins[ $slug ];
		}

		if ( empty( $selected_plugins ) ) {
			return false;
		}

		return $selected_plugins;
    }
    

    public static function output_up_sells() {
        $upsells = self::get_defined_plugins();
        foreach ( $upsells as $upsell ) { ?>

        <div class="product-card">
            <div class="card-contents">
                <div class="card-header">
                    <a href="<?php echo $upsell['plugin_url']; ?>?utm_source=<?php echo self::$args['plugin_slug']; ?>&utm_medium=plugin_up_sell" target="_blank">
                        <img class="plugin-logo" src="<?php echo $upsell['plugin_img']; ?>">
                        <div class="ag-watermark">
                            <img src="https://weareag.co.uk/wp/wp-content/themes/AGv5/img/ag-logo.svg">
                        </div>
                        <div class="plugin-tint" style="background-color:<?php echo $upsell['plugin_color']; ?>; opacity:0.95;"></div>
                        <img class="plugin-background" src="https://weareag.co.uk/wp/wp-content/themes/AGv5/img/plugin-background.jpg">
                    </a>
                </div>
                <div class="card-body">
                    <h3><?php echo $upsell['title']; ?></h3>
                    <p><?php echo $upsell['dec']; ?></p>
                    <a href="<?php echo $upsell['plugin_url']; ?>?utm_source=<?php echo self::$args['plugin_slug']; ?>&utm_medium=plugin_up_sell" target="_blank" class="ag-button">Find out more</a>
                </div>
            </div>
        </div>


		
        <?php } 

	}

}



