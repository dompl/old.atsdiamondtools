<?php
/*
Plugin Name: Red Frog Login Screen
Plugin URI: https://www.redfrogstudio.co.uk
description: Custom login screen from Red Frog Studio. This plugin has no options.
Version: 1.0.2
Author: Dom Kapelewski
Author URI: https://twitter.com/dompl
License: GPL2
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}
// This is a test
// Plaugin properties sdgsdg
define('RFS_LOGIN_SCREEN_VERSION', '1.0.2');
define('RFS_LOGIN_SCREEN_NAME', 'rfs-login-screen');

// Add composer autoload

if (!class_exists('RfsLogin')) {

  class RfsLogin
  {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version)
    {
      /* Plugin information */
      $this->plugin_name = $plugin_name;
      $this->version     = $version;

      $plugin_url = $this->rfs_admin_login_dir();

      /* Load compser ( plugin update styles) */
      include 'vendor/autoload.php';

      $myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
        'https://rfs-plugins.s3.amazonaws.com/rfs-login-screen/rfs-login-screen.json',
        $this->rfs_admin_login_dir(),
        $this->plugin_name
      );

      /* Plugin sctipts and styles */
      add_action('login_enqueue_scripts', array($this, 'rfs_deregister_legacy_login_scripts'), 999);
      add_action('login_enqueue_scripts', array($this, 'rfs_screen_enqueue_scripts'), 999);

      /* Login Header Title */
      add_filter('login_headertitle', array($this, 'rfs_login_screen_header_title'), 999);

      /* Login Header URL */
      add_filter('login_headerurl', array($this, 'rfs_login_screen_header_url'));

      /* Screen footer */
      add_action('login_footer', array($this, 'rfs_admin_login_footer'), 999);

      /* Add body class */
      add_filter('login_body_class', array($this, 'rfs_admin_login_body_class'), 999);

      /* Wrap login screen into container - open */
      add_action('login_header', array($this, 'rfs_admin_login_wrapper_open'), 999);

      /* Wrap login screen into container - close */
      add_action('login_footer', array($this, 'rfs_admin_login_wrapper_close'), 999);

    }

    /* Require stylesheets */

    public function rfs_screen_enqueue_scripts()
    {

      wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/admin-style.css', array(), $this->version, 'all');
      wp_enqueue_style($this->plugin_name . '-font', 'https://fonts.googleapis.com/css?family=Lato:300,400,700');
      wp_enqueue_script($this->plugin_name . '-js', plugin_dir_url(__FILE__) . 'js/login.js', array('jquery-core'), $this->version, true);

    }

    /* Deregister custom and WordPress CSS and JS */

    public function rfs_deregister_legacy_login_scripts()
    {

      wp_deregister_style('custom-login');
      wp_deregister_style('custom-login-font');
      wp_deregister_style('login');
      wp_deregister_script('custom-login-js');

    }

    /* Rename Login header title */

    public function rfs_login_screen_header_title()
    {

      return esc_html(get_bloginfo('name'));

    }

    /* Change Blog title URL */

    public function rfs_login_screen_header_url()
    {
      return esc_url(home_url());
    }

    public function ks_admin_login_body_class($classes)
    {

      $classes[] = 'rfs-admin-login';

      return $classes;

    }

    /* Login footer */

    public function rfs_admin_login_footer()
    {

      if ($GLOBALS['pagenow'] != 'wp-login.php') {
        return;
      }

      $lost_password = isset($_GET['action']) && ($_GET['action'] == 'lostpassword' || $_GET['action'] == 'rp') ? true : false;

      $rfs_admin_login_footer = sprintf('
          <div class="rfs-admin-login-footer">
          <div class="left"><a href="%1$s">%2$s</a></div>
          <div class="right"><a href="%3$s">%4$s %5$s</a></div>
          </div>',

        $lost_password ? esc_url(wp_login_url()) : esc_url(wp_lostpassword_url()),
        $lost_password ? __('log in', 'redfrog') : __('lost your password', 'redfrog'),
        esc_url(home_url('/')),
        __('back to', 'redfrog'),
        get_bloginfo('title')
      );

      echo $rfs_admin_login_footer;

    }

    /* Add body class */

    public function rfs_admin_login_body_class()
    {

      $classes[] = $this->plugin_name;

      return $classes;

    }

    /* Wrap login screen into container - open */

    public function rfs_admin_login_wrapper_open()
    {

      printf('<div id="%1$s" class="%1$s-%2$s">', $this->plugin_name, str_replace('.', '', $this->version));

    }

    public function rfs_admin_login_dir() {
      return  __FILE__;
    }

    /* Wrap login screen into container - close */

    public function rfs_admin_login_wrapper_close()
    {

      printf('</div>');

    }

  }

}

$class = new RfsLogin(RFS_LOGIN_SCREEN_NAME, RFS_LOGIN_SCREEN_VERSION);
