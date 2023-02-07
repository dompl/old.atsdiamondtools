<?php
/*-----------------------------------------------------------------------------------*/
/*	AG Display 3D Secure score
/*-----------------------------------------------------------------------------------*/
defined('ABSPATH') || die("No script kiddies please!");


if (class_exists('ePDQ_Display_Score')) {
    return;
}

class ePDQ_Display_Score
{

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


    /**
	 * Construct the plugin
	 */
	public function __construct()
	{

		add_action('add_meta_boxes',function(){
            if(get_option( 'threeds' ) == 'yes'){
                add_meta_box('AG_show_3d_secure_score', self::$args['plugin_short_title'] .' 3DS Score', array( $this, 'AG_3D_score_order_callback'),'shop_order','side','default');
            }
        });
        
        add_action('admin_footer', array( $this, 'build_score_graph') );
        add_action('admin_enqueue_scripts', array($this, 'ag_admin_scripts'));

		if (!AG_licence::valid_licence()) {
			return;
        }
        
    }


    public function ag_admin_scripts()
	{
		wp_enqueue_style('circliful', AG_ePDQ_server_path . 'inc/assets/css/circliful.css', false, self::$args['plugin_version']);
		wp_enqueue_script( 'circliful-js', AG_ePDQ_server_path . 'inc/assets/js/circliful.js', array('jquery'), null, true );
	}
    

    function build_score_graph($percent, $score = null) {

        global $post_type;
        if($post_type == 'shop_order') {

            echo '<script>jQuery( document ).ready(function() { 
                jQuery("#the-score").circliful({
                    percent: "'. ePDQ_3D_score::AG_build_final_score_number() .'",
                    foregroundColor: "'. ePDQ_3D_score::AG_3D_score_colours(ePDQ_3D_score::AG_build_risk_level()) .'",
                    fontColor: "'. ePDQ_3D_score::AG_3D_score_colours(ePDQ_3D_score::AG_build_risk_level()) .'",
                })
                jQuery(".ag-warning").css("color", "'. ePDQ_3D_score::AG_3D_score_colours(ePDQ_3D_score::AG_build_risk_level()) .'");
            });
            </script>';

        }
    }

    public static function AG_sore_PSP_returned_data($data){


    }


    public static function AG_3D_score_order_callback(){

        $customer = ePDQ_3D_score::AG_get_order_data();
        $customer_data = $customer['data'];
        $order = $customer['object'];

        echo '<section class="AG-Score">';

        if($order->is_paid() && $order->get_payment_method() == self::$args['payment_method_id']){

            echo '<h3>'. ePDQ_3D_score::AG_build_risk_level() .'</h3>';
        
            echo '<div id="the-score"></div>';

            echo ePDQ_3D_score::AG_display_warnings($customer_data);

        } elseif(!$order->is_paid()) {

            echo '<p>No data to show.<br />The order must be paid for first.</p>';

        } elseif($order->get_payment_method() != self::$args['payment_method_id']) {

            echo '<p>No Data to show.<br />The order must be made with the gateway: '. self::$args['payment_method_id'].'</p>';

        }

        echo '</section>';

 
    }


	public static function activate_display_3d()
	{
		if (!get_option('AG_ePDQ_Display_3D')) {
			add_option('AG_ePDQ_Display_3D', true);
		}
	}
	
}


$ePDQ_display_3d = new ePDQ_Display_Score();
register_activation_hook(__FILE__, array($ePDQ_display_3d, 'activate_display_3d'));

