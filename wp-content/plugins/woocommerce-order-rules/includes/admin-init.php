<?php

/**
 * Admin init logic
 *
 * @author   Actuality Extensions
 * @package  WC-Order-Rules
 * @since    0.1
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

require_once( 'classes/class-wc-order-rules.php');
require_once( 'classes/class-wc-order-rules-table.php');

require_once( 'wc-order-rules.php' );

add_action('admin_menu', 'wc_order_rules_add_menu');
#add_action('admin_head', 'wc_order_rules_menu_highlight');


/**
 * Add the menu item
 */
function wc_order_rules_add_menu() {
    global  $wc_order_rules,
            $wc_order_rules_form,
            $wc_order_rules_table;

    $wc_order_rules_form    = new WC_Order_Rules();
    $wc_order_rules_table   = new WC_Order_Rules_Table();
   
    $hook = add_submenu_page('woocommerce',
            __('Order Rules', 'wc_order_rules'), // page title
            __('Order Rules', 'wc_order_rules'), // menu title
            'manage_woocommerce', // capability
            $wc_order_rules->id, // unique menu slug
            'wc_order_rules_render'
             );

    add_action("load-$hook", 'wc_order_rules_add_options');
}


function wc_order_rules_menu_highlight() {
    global $menu, $submenu, $parent_file, $submenu_file, $self, $post_type, $taxonomy, $wc_order_rules;

    if (isset($submenu[$wc_order_rules->id]) && isset($submenu[$wc_order_rules->id][1])) {
        $submenu[$wc_order_rules->id][0] = $submenu[$wc_order_rules->id][1];
        unset($submenu[$wc_order_rules->id][1]);
    }
}
