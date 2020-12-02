<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


if ( ! class_exists( 'AWS_Admin' ) ) :

/**
 * Class for plugin admin panel
 */
class AWS_Admin {

    /*
     * Name of the plugin settings page
     */
    var $page_name = 'aws-options';

    /**
     * @var AWS_Admin The single instance of the class
     */
    protected static $_instance = null;


    /**
     * Main AWS_Admin Instance
     *
     * Ensures only one instance of AWS_Admin is loaded or can be loaded.
     *
     * @static
     * @return AWS_Admin - Main instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /*
    * Constructor
    */
    public function __construct() {

        add_action( 'admin_menu', array( &$this, 'add_admin_page' ) );
        add_action( 'admin_init', array( &$this, 'register_settings' ) );

        if ( ! AWS_Admin_Options::get_settings() ) {
            $default_settings = AWS_Admin_Options::get_default_settings();
            update_option( 'aws_settings', $default_settings );
        }

        add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );

        add_action( 'admin_notices', array( $this, 'display_welcome_header' ), 1 );

    }

    /**
     * Add options page
     */
    public function add_admin_page() {
        add_menu_page( esc_html__( 'Adv. Woo Search', 'advanced-woo-search' ), esc_html__( 'Adv. Woo Search', 'advanced-woo-search' ), 'manage_options', 'aws-options', array( &$this, 'display_admin_page' ), 'dashicons-search' );
    }

    /**
     * Generate and display options page
     */
    public function display_admin_page() {

        $nonce = wp_create_nonce( 'plugin-settings' );

        $tabs = array(
            'general' => esc_html__( 'General', 'advanced-woo-search' ),
            'form'    => esc_html__( 'Search Form', 'advanced-woo-search' ),
            'results' => esc_html__( 'Search Results', 'advanced-woo-search' )
        );

        $current_tab = empty( $_GET['tab'] ) ? 'general' : sanitize_text_field( $_GET['tab'] );

        $tabs_html = '';

        foreach ( $tabs as $name => $label ) {
            $tabs_html .= '<a href="' . admin_url( 'admin.php?page=aws-options&tab=' . $name ) . '" class="nav-tab ' . ( $current_tab == $name ? 'nav-tab-active' : '' ) . '">' . $label . '</a>';

        }

        $tabs_html .= '<a href="https://advanced-woo-search.com/?utm_source=plugin&utm_medium=settings-tab&utm_campaign=aws-pro-plugin" class="nav-tab premium-tab" target="_blank">' . esc_html__( 'Get Premium', 'advanced-woo-search' ) . '</a>';

        $tabs_html = '<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">'.$tabs_html.'</h2>';

        if ( isset( $_POST["Submit"] ) && current_user_can( 'manage_options' ) && isset( $_POST["_wpnonce"] ) && wp_verify_nonce( $_POST["_wpnonce"], 'plugin-settings' ) ) {
            AWS_Admin_Options::update_settings();
        }

        echo '<div class="wrap">';

        echo '<h1></h1>';

        echo '<h1 class="aws-instance-name">Advanced Woo Search</h1>';

        echo $tabs_html;

        echo '<form action="" name="aws_form" id="aws_form" method="post">';

        switch ($current_tab) {
            case('form'):
                new AWS_Admin_Fields( 'form' );
                break;
            case('results'):
                new AWS_Admin_Fields( 'results' );
                break;
            default:
                echo AWS_Admin_Meta_Boxes::get_general_tab_content();
                new AWS_Admin_Fields( 'general' );
        }

        echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';

        echo '</form>';

        echo '</div>';

    }

    /*
	 * Register plugin settings
	 */
    public function register_settings() {
        register_setting( 'aws_settings', 'aws_settings' );
    }

    /*
	 * Get plugin settings
	 */
    public function get_settings() {
        $plugin_options = get_option( 'aws_settings' );
        return $plugin_options;
    }

    /*
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts() {

        if ( isset( $_GET['page'] ) && $_GET['page'] == 'aws-options' ) {
            wp_enqueue_style( 'plugin-admin-style', AWS_URL . '/assets/css/admin.css', array(), AWS_VERSION );
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'plugin-admin-scripts', AWS_URL . '/assets/js/admin.js', array('jquery'), AWS_VERSION );
            wp_localize_script( 'plugin-admin-scripts', 'aws_vars', array(
                'ajaxurl' => admin_url( 'admin-ajax.php', 'relative' ),
                'ajax_nonce' => wp_create_nonce( 'aws_admin_ajax_nonce' ),
            ) );
        }

    }

    /*
     * Add welcome notice
     */
    public function display_welcome_header() {

        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'aws-options' ) {
            return;
        }

        $hide_notice = get_option( 'aws_hide_welcome_notice' );

        if ( ! $hide_notice || $hide_notice === 'true' ) {
            return;
        }

        echo AWS_Admin_Meta_Boxes::get_welcome_notice();

    }

}

endif;


add_action( 'init', 'AWS_Admin::instance' );