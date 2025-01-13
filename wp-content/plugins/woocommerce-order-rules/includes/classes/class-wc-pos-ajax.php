<?php

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * WooCommerce WC_AJAX
 *
 * AJAX Event Handler
 *
 * @class     wc_order_rules_AJAX
 * @version   0.0.1
 * @package   WoocommerceOrderRules/Classes
 * @category  Class
 * @author    Actuality Extensions
 */

class WC_Order_Rules_AJAX {

    /**
     * Hook into ajax events
     */
    public function __construct() {

        // woocommerce_EVENT => nopriv
        $ajax_events = array(
            'ordering' => false
        ); 

        foreach ($ajax_events as $ajax_event => $nopriv) {
            add_action('wp_ajax_wc_order_rules_' . $ajax_event, array($this, $ajax_event));

            if ($nopriv)
                add_action('wp_ajax_nopriv_wc_order_rules_' . $ajax_event, array($this, $ajax_event));
        }
    }

    public function ordering(){
        global $wpdb, $wc_order_rules;

        $id       = (int) $_POST['id'];
        $table_rules = $wpdb->prefix . "wc_order_rules";
        $next_id  = isset( $_POST['nextid'] ) && (int) $_POST['nextid'] ? (int) $_POST['nextid'] : null;
        $rules    = $wpdb->get_results("SELECT * FROM $table_rules ORDER BY ordering ASC");

        if ( ! $id ) {
            die(0);
        }
        $index = 0;
        $rule_in_level = false; // flag: is our rule to order in this level of rules
        foreach ($rules as $rule) {
            
            if( $rule->ID == $id ) { // our rule to order, we skip
                $rule_in_level = true;
                continue; // our rule to order, we skip
            }
            if(null !== $next_id && $rule->ID == $next_id) {
                $index++;
                $this->set_rule_order($id, $index);
            }
            // set order
            $index++;
            $this->set_rule_order($rule->ID, $index);
        }
        // no nextid meaning our rule is in last position
        if( $rule_in_level && null === $next_id )
            $this->set_rule_order($id, $index+1);
        die;
    }
    private function set_rule_order($id, $i){
        global $wpdb;

        $table_rules = $wpdb->prefix . "wc_order_rules";
        $data['ordering'] = $i;
        
        $result = $wpdb->update( $table_rules, $data, array( 'ID' => $id ) );
    }
}

new WC_Order_Rules_AJAX();
