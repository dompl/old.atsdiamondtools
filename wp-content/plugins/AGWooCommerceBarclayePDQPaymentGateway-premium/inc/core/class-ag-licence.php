<?php

/*-----------------------------------------------------------------------------------*/
/*	AG Licence
/*-----------------------------------------------------------------------------------*/
defined( 'ABSPATH' ) or die( "No script kiddies please!" );
if ( class_exists( 'AG_licence' ) ) {
    return;
}
class AG_licence
{
    /**
     * Instance
     * @var string
     */
    public static  $instance = null ;
    public static  $args = array() ;
    public static  $freemius = null ;
    public static function run_instance( $args = array() )
    {
        
        if ( self::$instance === null ) {
            self::$args = $args;
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    private function __construct()
    {
        self::start_FS();
        self::start_hooks();
        add_filter(
            'woocommerce_available_payment_gateways',
            array( $this, 'ag_available_checkout' ),
            99,
            1
        );
    }
    
    public static function start_FS()
    {
        if ( !is_null( self::$freemius ) ) {
            return;
        }
        require_once self::$args['paths']['plugin'] . 'inc/freemius/start.php';
        $menu = array(
            'slug'    => self::get_fs_arg( 'menu/slug', null ),
            'contact' => self::get_fs_arg( 'menu/contact', false ),
            'support' => self::get_fs_arg( 'menu/support', false ),
            'account' => self::get_fs_arg( 'menu/account', false ),
            'pricing' => self::get_fs_arg( 'menu/pricing', false ),
        );
        self::$freemius = fs_dynamic_init( array(
            'id'               => self::get_fs_arg( 'id', null ),
            'slug'             => self::get_fs_arg( 'slug', null ),
            'type'             => self::get_fs_arg( 'type', 'plugin' ),
            'public_key'       => self::get_fs_arg( 'public_key', null ),
            'is_premium'       => true,
            'is_premium_only'  => self::get_fs_arg( 'is_premium_only', true ),
            'has_paid_plans'   => self::get_fs_arg( 'has_paid_plans', true ),
            'has_addons'       => self::get_fs_arg( 'has_addons', false ),
            'is_org_compliant' => self::get_fs_arg( 'is_org_compliant', false ),
            'trial'            => array(
            'days'               => self::get_fs_arg( 'trial/days', 7 ),
            'is_require_payment' => self::get_fs_arg( 'trial/is_require_payment', true ),
        ),
            'menu'             => $menu,
            'is_live'          => true,
        ) );
    }
    
    public static function get_fs_arg( $keys, $default )
    {
        $base = self::$args['freemius'];
        $keys = explode( '/', $keys );
        $depth = 0;
        $key_count = count( $keys );
        foreach ( $keys as $key ) {
            $depth++;
            if ( !isset( $base[$key] ) ) {
                break;
            }
            $base = $base[$key];
            if ( $depth == $key_count ) {
                return $base;
            }
        }
        return $default;
    }
    
    public static function start_hooks()
    {
        self::$freemius->add_filter( 'show_trial', '__return_false' );
        self::$freemius->add_filter(
            'templates/account.php',
            array( __CLASS__, 'welcome_link' ),
            10,
            1
        );
        self::$freemius->add_filter(
            'plugin_icon',
            array( __CLASS__, 'plugin_icon' ),
            10,
            1
        );
        self::$freemius->add_filter( 'hide_account_tabs', '__return_true' );
    }
    
    public static function plugin_icon( $icon )
    {
        return self::$args['paths']['plugin'] . '/img/plugin-icon.png';
    }
    
    public static function valid_licence()
    {
        if ( self::$freemius->can_use_premium_code() ) {
            return true;
        }
        return false;
    }
    
    public static function welcome_link( $html = '' )
    {
        return $html . sprintf( '<a href="%s" class="button button-secondary">&larr; %s</a>', self::$args['urls']['welcome'], __( 'Back to Welcome page' ) );
    }
    
    public function ag_available_checkout( $available_gateways )
    {
        if ( !self::$freemius->can_use_premium_code() ) {
            unset( $available_gateways['epdq_checkout'] );
        }
        return $available_gateways;
    }

}