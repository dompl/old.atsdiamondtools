<?php
/* Remove direct access */
if (!defined('ABSPATH'))
{
  exit; // Exit if accessed directly
}

if ( 'VOIDX_CUSTOMISE_WP_LOGIN_SCREEN' ) {

  add_action( 'login_enqueue_scripts', function() {
    wp_dequeue_style( 'login' );
  });

  function my_login_stylesheet() {
    wp_enqueue_style( 'custom-login', get_stylesheet_directory_uri() . '/login.css' );
    wp_enqueue_style( 'custom-login-font', 'https://fonts.googleapis.com/css?family=Source+Sans+Pro:200,300,400,600,700' );
    wp_enqueue_script('jquery');
    wp_enqueue_script( 'custom-login-js', get_stylesheet_directory_uri() . '/js/x-login.js', false );
  }
  add_action( 'login_enqueue_scripts', 'my_login_stylesheet' );


  // Change the Login Logo URL
  function my_login_logo_url() {
    return home_url();
  }
  add_filter( 'login_headerurl', 'my_login_logo_url' );

  function my_login_logo_url_title() {
    return get_bloginfo('name');
  }
  add_filter( 'login_header_title', 'my_login_logo_url_title' );
}
