<?php
function get_wc_order_rules($id = 0){
    global $wpdb;
    
    $table_name = $wpdb->prefix . "wc_order_rules";
    $filter = "";
    if ($id != 0){
        if(is_array($id)){
            $ids = implode(',', $id);
            $filter = "WHERE ID id($ids)";
        }else if($id > 0){
            $filter = "WHERE ID = $id";
        }
    }

    $db_data = $wpdb->get_results("SELECT * FROM $table_name $filter ORDER BY ordering DESC");
    $order_rules = array();
    
    foreach ($db_data as $value) {
      $order_rules[] = get_object_vars($value);
    }

    foreach ($order_rules as $rule_key => $data) {
        if(isset($data['ID']) && !empty($data['ID'])){
            $order_rules[$rule_key]['conditions']  = get_wc_order_rules_conditions_data($data['ID']);
        }
    }
        
    return $order_rules;
}

function get_wc_order_rules_conditions_data($id = 0)
{
    if(!$id) return false;
    global $wpdb;
    $table_name = $wpdb->prefix . "wc_order_rules_conditions";
    $db_data = $wpdb->get_results("SELECT * FROM $table_name WHERE rule_id = $id");
    $data = array();

    foreach ($db_data as $value) {
      $data[] = get_object_vars($value);
    }
    return $data;
}