<?php
if( ! defined('WP_UNINSTALL_PLUGIN') )
	exit;

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "wc_order_rules" );
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "wc_order_rules_conditions" );

$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'wc_order_rules%';");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_wc_order_rules%';");
