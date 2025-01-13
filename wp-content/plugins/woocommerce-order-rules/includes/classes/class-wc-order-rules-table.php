<?php
/**
 * WooCommerce Order Rules Table Class
 *
 * @author    Actuality Extensions
 * @package   WooCommerceOrderRules/Classes
 * @category	Class
 * @since     0.1
 */


if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class WC_Order_Rules_Table extends WP_List_Table {
  protected static $data;
  protected $found_data;

  function __construct(){
  global $status, $page;

      parent::__construct( array(
          'singular'  => __( 'Order Rule', 'wc_order_rules' ),     //singular name of the listed records
          'plural'    => __( 'Order Rules', 'wc_order_rules' ),   //plural name of the listed records
          'ajax'      => false        //does this table support ajax?
      ) );

  }


  function no_items() {
    _e( 'Rules not found. Try to adjust the filter.', 'wc_order_rules' );
  }
  function column_default( $item, $column_name ) {
    switch ( $column_name ) {
      case 'name':
      case '_conditions':
      case 'rule_row_colour':
        return $item[substr($column_name, 1)];
      default:
        return print_r( $item, true ); //Show the whole array for troubleshooting purposes
    }
  }
  function get_sortable_columns() {
    $sortable_columns = array(
      'name' => array('name', false)
    );
    return $sortable_columns;
  }
  function get_columns() {
    $columns = array(
      'cb' => '<input type="checkbox" />',
      'name' => __( 'Name', 'wc_order_rules' ),
      '_conditions' => __( 'Conditions', 'wc_order_rules' ),
      'rule_row_colour' => __( 'Row Colour', 'wc_order_rules' ),
    );
    return $columns;
  }
  function usort_reorder( $a, $b ) {
    // If no sort, default to last purchase
    $orderby = ( !empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'ordering';
    // If no order, default to desc
    $order = ( !empty( $_GET['order'] ) ) ? $_GET['order'] : 'asc';
    // Determine sort order
    if ( $orderby == 'order_value' ) {
      $result = $a[$orderby] - $b[$orderby];
    } else {
      $result = strcmp( $a[$orderby], $b[$orderby] );
    }
    // Send final sort direction to usort
    return ( $order === 'asc' ) ? $result : -$result;
  }

  function get_bulk_actions() {
    $actions = apply_filters( 'wc_pos_outlet_bulk_actions', array(
      'delete' => __( 'Delete', 'wc_order_rules' ),
    ) );
    return $actions;
  }

  function column_cb( $item ) {
    return sprintf(
      '<input type="checkbox" name="id[]" value="%s" />', $item['ID']
    );
  }

  function column_name( $item ) {
    $name = sprintf('<strong><a href="?page=%s&action=%s&id=%s">%s</a></strong>','wc_order_rules','edit', $item['ID'], stripslashes($item['name']));
    $actions = array(
      'edit'      => sprintf('<a href="?page=%s&action=%s&id=%s">Edit</a>','wc_order_rules','edit', $item['ID']),
      'delete'      => sprintf('<a href="?page=%s&action=%s&id=%s">Delete</a>','wc_order_rules','delete', $item['ID']),
    );

    return sprintf('%1$s %2$s', $name, $this->row_actions($actions) );
  }
  function column_rule_row_colour( $item ) {
    if( $item['enable_row_colours'] == 'on' ){
      return '<span class="row_colour_assigned" style="background-color: #'.$item['row_colour'].';">#'.$item['row_colour'].'</span>';
    }else{
      return '<span class="no_row_colour_assigned" data-tip="'.__('No Row Colour Assigned', 'wc_order_rules').'"></span>';
    }
  }
  function column__conditions( $item ) {
    global $wc_order_rules_form;
    $str = '';
    $conditions = WC_Order_Rules::get_conditions_data($item['ID']);
    $payment_methods  = $wc_order_rules_form->get_payment_methods();
    $shipping_methods = $wc_order_rules_form->get_shipping_methods();
    foreach ($conditions as $key => $value) {
      if(!empty($value['order_var'])){
        $order_var = ucfirst(str_replace('_', ' ', $value['order_var']) );
        $str .= '<b>'.$order_var.'</b> ';
      }
      if(!empty($value['rule'])){
        $order_rule = $value['rule'];
        $str .= $order_rule;
      }
      if(!empty($value['value'])){
        $order_value = $value['value'];
        switch ($value['order_var']) {
          case 'products':
            $ids   = explode(',', $order_value);
            $names = array();
            foreach ($ids as $pr_id) {
              $pr      = wc_get_product($pr_id);
              $names[] = '<a href="'.get_edit_post_link($pr_id).'">'.$pr->get_title().'</a>';
            }
            $order_value = implode(', ', $names);
            break;
          case 'order_status':
            if($value['rule'] == 'is' || $value['rule'] == 'is not'){
              $statuses = wc_get_order_statuses();
              $order_value = '"' . $statuses[$order_value] . '"';
            }
            else{
              $order_value = '"' . $order_value . '"';
            }
            break;
          case 'billing_first_name':
          case 'billing_last_name':
          case 'shipping_first_name':
          case 'shipping_last_name':
          case 'customer_email':
            $order_value = '"' . $order_value . '"';
            break;
          case 'order_date':
            $order_value = str_replace(':', ' ', $order_value);
            break;
          case 'payment_method':
            $payment_methods  = $wc_order_rules_form->get_payment_methods();
            $order_value = $payment_methods[$order_value];
            break;
          case 'shipping':
            $shipping_methods = $wc_order_rules_form->get_shipping_methods();
            $order_value = $shipping_methods[$order_value];
            break;
          case 'billing_country':
          case 'shipping_country':
            $countries = WC()->countries->countries;
            #print_r($countries);
            $order_countrie = explode(',', $order_value);
            if(!empty($order_countrie)){
              $order_value = '';
              foreach ($order_countrie as $cod) {
                if(!empty($order_value)) $order_value .=  ', ';
                $order_value .= $countries[$cod];
              }
            }
            break;
        }
        $str .= ' '.$order_value;
      }
      if(!empty($str))
        $str .= '<br>';
    }
    return $str;
  }
  function column__selected_by( $item ) {
    $selected_by = ucfirst(str_replace('_', ' ', $item['selected_by']) );

    return $selected_by;
  }

  function prepare_items() {
    $columns  = $this->get_columns();
    $hidden   = array();
    self::$data = WC_Order_Rules::get_data();
    $sortable = $this->get_sortable_columns();
    $this->_column_headers = array( $columns, $hidden, $sortable );
    usort( self::$data, array( &$this, 'usort_reorder' ) );

    $user = get_current_user_id();
    $screen = get_current_screen();
    $option = $screen->get_option('per_page', 'option');
    $per_page = get_user_meta($user, $option, true);
    if ( empty ( $per_page) || $per_page < 1 ) {
        $per_page = $screen->get_option( 'per_page', 'default' );
    }

    $current_page = $this->get_pagenum();

    $total_items = count( self::$data );
    if( $_GET['page'] == 'wc_order_rules' ){
      // only ncessary because we have sample data
      $this->found_data = array_slice( self::$data,( ( $current_page-1 )* $per_page ), $per_page );

      $this->set_pagination_args( array(
        'total_items'   => $total_items,                  //WE have to calculate the total number of items
        'per_page' => $per_page                     //WE have to determine how many items to show on a page
      ) );
      $this->items = $this->found_data;
    }else{
      $this->items = self::$data;
    }
  }
  

} //class