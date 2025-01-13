<?php
/**
 * Logic related to displaying Order Rules page.
 *
 * @author   Actuality Extensions
 * @package  WooCommerce Order Rules
 * @since    0.1
 */


if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
add_action( 'admin_init', 'wc_order_rules_actions' );
add_filter('set-screen-option', 'wc_order_rules_set_options', 10, 3);
$GLOBALS['hook_suffix'] = '';
function wc_order_rules_actions() {
  global  $wc_order_rules,
          $wc_order_rules_form;

  if(isset($_GET['page']) && $_GET['page'] != $wc_order_rules->id) return;

  if(isset($_POST['action']) && $_POST['action'] == 'save-wc-order-rule'){
    $id = 0;
    if (isset($_POST['id']) && !empty($_POST['id'])) $id = $_POST['id'];
      $wc_order_rules_form->save($id);
  }

  else if( isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['id']) && !empty($_POST['id']) ) {
      $wc_order_rules_form->delete($_POST['id']);
  }else if( isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']) && !empty($_GET['id']) )  {
      $wc_order_rules_form->delete($_GET['id']);
  }else if ( isset($_POST['action2']) &&  $_POST['action2'] == 'delete' && isset($_POST['id']) && !empty($_POST['id']) )  {
      $wc_order_rules_form->delete($_POST['id']);
  }
}
function wc_order_rules_set_options($status, $option, $value) {
  if ( 'o_rules_per_page' == $option ) return $value;
  return $status;
}
function wc_order_rules_render() {
  global  $wc_order_rules,
          $wc_order_rules_form;

  if(isset($_GET['page']) && $_GET['page'] != $wc_order_rules->id) return;
  if(isset($_GET['action']) && $_GET['action'] == 'new')
    $wc_order_rules_form->display_edit_form();
  elseif(isset($_GET['action']) && isset($_GET['id']) && $_GET['action'] == 'edit' && $_GET['id'] != '')
    $wc_order_rules_form->display_edit_form($_GET['id']);
  else
    $wc_order_rules_form->display_table();
}
function wc_order_rules_add_options() {
  global $wc_order_rules_table;

  $option = 'per_page';
  $args = array(
    'label' => __( 'Rules', 'wc_order_rules' ),
    'default' => 10,
    'option' => 'wc_order_rules'
  );
  add_screen_option( $option, $args );

  $wc_order_rules_table = new WC_Order_Rules_Table;

}
