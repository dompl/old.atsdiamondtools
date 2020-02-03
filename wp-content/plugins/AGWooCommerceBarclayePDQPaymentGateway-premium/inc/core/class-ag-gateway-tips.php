<?php
/*-----------------------------------------------------------------------------------*/
/*	AG Gateway tips
/*-----------------------------------------------------------------------------------*/
defined( 'ABSPATH' ) or die( "No script kiddies please!" );


if ( class_exists( 'AG_gateway_tips' ) ) {
	return;
}


class AG_gateway_tips {


    public static $instance = null;
    public static $args = array();
    
    /**
	 * run
	 */
	public static function run_instance( $args = array() ) {
		if ( self::$instance === null ) {
			self::$args            = $args;
			self::$instance = new self();
		}

		return self::$instance;
    }


    public static function setup_tips() {

        return array(
            'PCI' =>  array(
                'title'          =>  'What is PCI compliance and does it affect me?',
                'tip_url'        =>  'https://weareag.co.uk/what-is-pci-compliance-and-does-it-affect-me/',
                'dec'            =>  'If you plan to run an e-commerce site, you need to be familiar with PCI compliance. PCI stands for Payment Card Industry, which is an independent body that was created by the major payment card brands (Visa, MasterCard, American Express, Discover and JCB). PCI establishes a set of specific rules and requirements you need to […]',
                'tip_img'        =>  'https://weareag.co.uk/wp/wp-content/uploads/2018/03/What-is-PCI-compliance-and-does-it-affect-me-1024x683.jpg',
            ),
            'payments_101' =>  array(
                'title'          =>  'Online Payment Processing Options 101',
                'tip_url'        =>  'https://weareag.co.uk/online-payment-processing-options-101/',
                'dec'            =>  'What is online payment processing? Online payment processing is the system that allows you to purchase goods or services on the internet without using cash. There are two main elements, the payment gateway and the payment processor. ',
                'tip_img'        =>  'https://weareag.co.uk/wp/wp-content/uploads/2018/08/online-payment-processing-options-101.jpg',
            ),
            'for_you' =>  array(
                'title'          =>  'What Payment Gateway Solution is Best for You?',
                'tip_url'        =>  'https://weareag.co.uk/what-payment-gateway-solution-is-best-for-you/',
                'dec'            =>  'Growing a business is exciting. Seeing sales come pouring in is a shot of energy that helps you keep going even when things get tough. Choose the payment gateway that will work best with your business. Take time to do the research and read the reviews. Ask other online business owners what gateway they’ve used. ',
                'tip_img'        =>  'https://weareag.co.uk/wp/wp-content/uploads/2018/11/What-Payment-Gateway-Solution-is-Best-for-You-1024x576.jpg',
            ),
            'luhn' =>  array(
                'title'          =>  'The Luhn Algorithm',
                'tip_url'        =>  'https://weareag.co.uk/the-luhn-algorithm/',
                'dec'            =>  'Have you ever wondered what happens if you enter the wrong credit card information to a payment gateway? Does the number get rejected, does it try to take payment so you still get your products, or does some random person suddenly lose a chunk of money?',
                'tip_img'        =>  'https://weareag.co.uk/wp/wp-content/uploads/2019/02/The-Luhn-Algorithm.jpg',
            ),
        );
    }

    public static function get_defined_tips( $slugs = array() ) {

		$tips          = self::setup_tips();
        $selected_tips = array();
        $slugs = self::$args['tips'];
        

		foreach ( $slugs as $slug ) {
			$selected_tips[ $slug ] = $tips[ $slug ];
		}

		if ( empty( $selected_tips ) ) {
			return false;
		}

		return $selected_tips;
    }


    public static function output_tips() {
        $tips = self::get_defined_tips();
        foreach ( $tips as $tip ) { ?>

        <div class="tip-card">
            <div class="card-contents">
                <div class="card-header">
                    <a href="<?php echo $tip['tip_url']; ?>?utm_source=<?php echo self::$args['plugin_slug']; ?>&utm_medium=plugin_up_sell" target="_blank">
                        <img class="plugin-logo" src="<?php echo $tip['tip_img']; ?>">
                        <div class="ag-watermark">
                            <img src="https://weareag.co.uk/wp/wp-content/themes/AGv5/img/ag-logo.svg">
                        </div>
                    </a>
                </div>
                <div class="card-body">
                    <h3><?php echo $tip['title']; ?></h3>
                    <p><?php echo $tip['dec']; ?></p>
                    <a href="<?php echo $tip['tip_url']; ?>?utm_source=<?php echo self::$args['plugin_slug']; ?>&utm_medium=plugin_up_sell" target="_blank" class="ag-button">Find out more</a>
                </div>
            </div>
        </div>

    <?php } 

	}
}