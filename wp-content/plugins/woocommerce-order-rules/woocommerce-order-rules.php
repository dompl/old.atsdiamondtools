<?php
/**
 * Plugin Name: WooCommerce Order Rules & Filters
 * Plugin URI: https://codecanyon.net/item/woocommerce-order-rules-filters/9299494&ref=actualityextensions
 * Description: WooCommerce Order Rules is an extension which allows you to automatically filter out the orders based on a filter which you define by colouring the row of the order to a HEX colour you assign.
 * Version: 1.5.3
 * Author: Actuality Extensions
 * Author URI: http://actualityextensions.com/
 * Tested up to: 4.9.4
 *
 * Copyright: (c) 2018 Actuality Extensions
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package     WC-Order-Rules
 * @author      Actuality Extensions
 * @category    Plugin
 * @copyright   Copyright (c) 2018, Actuality Extensions
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly\

if (function_exists('is_multisite') && is_multisite()) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    if (!is_plugin_active('woocommerce/woocommerce.php'))
        return;
} else {
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))))
        return; // Check if WooCommerce is active    
}

require 'updater/updater.php';
global $aebaseapi;
$aebaseapi->add_product(__FILE__);

if (!class_exists('WoocommerceOrderRules')) {


    class WoocommerceOrderRules
    {

        public $shipping_zones = false;

        public function __construct()
        {
            global $wc_order_rules_db_version;
            $wc_order_rules_db_version = "0.9";

            //$this->rulles = get_wc_order_rules();

            // installation after woocommerce is available and initialized
            if (is_admin() && !defined('DOING_AJAX'))
                add_action('init', array($this, 'wc_order_rules_install'));
            add_action('woocommerce_init', array($this, 'includes'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_dependencies_admin'));
            add_action('admin_print_scripts', array($this, 'shop_order_columns_display_color'));
            add_action('restrict_manage_posts', array($this, 'restrict_manage_orders'), 5);
            #add_filter( 'pre_get_posts', array( $this, 'orders_by_order_rules' ));

            add_action('init', array($this, '_get_rulles'));


        }

        /**
         * The plugin's ids
         * @var string
         */
        var $id = 'wc_order_rules';
        var $rulles;

        public function _get_rulles()
        {
            $this->rulles = get_wc_order_rules();
        }

        /**
         * Enqueue admin CSS and JS dependencies
         */
        public function enqueue_dependencies_admin()
        {

            global $wc_order_rules_db_version;

            if (isset($_GET['page']) && $_GET['page'] != $this->id) return;
            $scripts = array('jquery', 'editor', 'thickbox', 'jquery-ui-datepicker', 'wc-enhanced-select', 'woocommerce_admin');

            wp_enqueue_style('thickbox');
            wp_enqueue_style('jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');

            wp_enqueue_script('custom-background');
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_style('woocommerce_frontend_styles', plugins_url() . '/woocommerce/assets/css/admin.css');
            wp_enqueue_style('woocommerce-order-rules-style', plugins_url('assets/css/admin.css', __FILE__), array(), $wc_order_rules_db_version);


            wp_enqueue_script('woocommerce-order-rules-script-admin', plugins_url('assets/js/admin.js', __FILE__), $scripts, $wc_order_rules_db_version);

            wp_localize_script('woocommerce-order-rules-script-admin', 'wc_order_rules_params',
                apply_filters('wc_order_rules_params', array(
                        'ajax_url' => WC()->ajax_url(),
                        'ajax_loader_url' => apply_filters('woocommerce_ajax_loader_url', WC()->plugin_url() . '/assets/images/ajax-loader@2x.gif'),
                        'post_id' => isset($post->ID) ? $post->ID : '',
                    )
                )
            );
        }

        public function wc_order_rules_install()
        {
            global $wpdb, $wc_order_rules_db_version;
            $wpdb->hide_errors();
            $installed_ver = get_option("wc_order_rules_db_version");
            if ($installed_ver != $wc_order_rules_db_version) {

                $collate = '';
                if ($wpdb->has_cap('collation')) {
                    if (!empty($wpdb->charset))
                        $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
                    if (!empty($wpdb->collate))
                        $collate .= " COLLATE $wpdb->collate";
                }

                // initial install
                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                $table_name = $wpdb->prefix . "wc_order_rules";
                $sql = "CREATE TABLE $table_name (
                        ID         bigint(20) NOT NULL AUTO_INCREMENT,
                        name       text NOT NULL,
                        row_colour varchar(6),
                        enable_row_colours varchar(3) NOT NULL DEFAULT 'off',
                        match_rules varchar(15) NOT NULL DEFAULT 'any',
                        ordering   int(20) NOT NULL,
                        PRIMARY KEY  (ID)
                )" . $collate;
                dbDelta($sql);

                $table_name = $wpdb->prefix . "wc_order_rules_conditions";
                $sql = "CREATE TABLE $table_name (
                        ID         bigint(20) NOT NULL AUTO_INCREMENT,
                        rule_id    int(20) NOT NULL,
                        order_var  varchar(255) NOT NULL,
                        rule       text NOT NULL,
                        value      text NOT NULL,
                        PRIMARY KEY  (ID)
                )" . $collate;
                dbDelta($sql);
                if (version_compare($installed_ver, '0.7', '=')) {
                    $table_name = $wpdb->prefix . "wc_order_rules";
                    #$wpdb->show_errors();
                    $sql = "ALTER TABLE {$table_name} DROP COLUMN limit_to;";
                    $wpdb->query($sql);
                    $sql = "ALTER TABLE {$table_name} DROP COLUMN selected_by;";
                    $wpdb->query($sql);
                    $sql = "ALTER TABLE {$table_name} DROP COLUMN live_updating;";
                    $wpdb->query($sql);

                    $rules = $wpdb->get_results("SELECT * FROM $table_name");
                    if ($rules && !empty($rules)) {
                        foreach ($rules as $rule) {
                            if (!empty($rule->row_colour)) {
                                $wpdb->update($table_name, array('enable_row_colours' => 'on'), array('ID' => $rule->ID));
                            } else {
                                $wpdb->update($table_name, array('enable_row_colours' => 'off'), array('ID' => $rule->ID));
                            }
                        }
                    }
                }

                if (get_option("wc_order_rules_db_version")) {
                    update_option("wc_order_rules_db_version", $wc_order_rules_db_version);
                } else {
                    add_option("wc_order_rules_db_version", $wc_order_rules_db_version);
                }
            }
        }

        /**
         * Include required files
         */
        public function includes()
        {
            require_once('includes/functions-core.php');
            if (is_admin()) {
                require_once('includes/admin-init.php'); // Admin section
                require_once('includes/wc-order-filter-query.php');

                if (defined('DOING_AJAX')) {
                    $this->ajax_includes();
                }
            }
        }

        /**
         * Include required ajax files.
         */
        public function ajax_includes()
        {
            include_once('includes/classes/class-wc-pos-ajax.php');         // Ajax functions for admin and the front-end
        }

        function shop_order_columns_display_color()
        {
            global $posts, $typenow;
            if ('shop_order' != $typenow) {
                return;
            }
            if ($posts) {
                echo '<style>';
                foreach ($posts as $post) {
                    if ($color = $this->check_order($post->ID, true))
                        echo 'tr#post-' . $post->ID . '{background-color: #' . $color . '}';
                }
                echo '</style>';
            }
        }

        function check_order($post_id = 0, $skipnocolor = false)
        {
            if ($post_id == 0) return false;
            $color = 0;

            $prev_color = 0;
            $order_rules = $this->rulles;

            $order = new WC_Order($post_id);
            foreach ($order_rules as $rule) {
                if ($rule['enable_row_colours'] != 'on' && $skipnocolor === true) continue;
                $conditions = $rule['conditions'];
                $prev_color = $color;
                $i = 0;
                //return $conditions;
                foreach ($conditions as $condition) {
                    switch ($condition['order_var']) {
                        case 'order_status':
                            $condition_eval = $this->condition_rule_symbol($condition['rule'], get_post_status($post_id), $condition['value']);
                            if ($condition_eval) {
                                $color = $rule['row_colour'];
                                $i++;
                            }
                            break;
                        case 'order_number':
                            $condition_eval = $this->condition_rule_symbol($condition['rule'], $post_id, $condition['value']);
                            if ($condition_eval) {
                                $color = $rule['row_colour'];
                                $i++;
                            }
                            break;
                        case 'order_total':

                            $condition_eval = $this->condition_rule_symbol($condition['rule'], $order->get_total(), $condition['value']);
                            if ($condition_eval) {
                                $color = $rule['row_colour'];
                                $i++;
                            }
                            break;
                        case 'purchased_items':
                            $condition_eval = $this->condition_rule_symbol($condition['rule'], $order->get_item_count(), $condition['value']);
                            if ($condition_eval) {
                                $color = $rule['row_colour'];
                                $i++;
                            }
                            break;
                        case 'billing_country':
                            $billing_country = $order->billing_country;
                            $condition_eval = $this->condition_rule_symbol($condition['rule'], $billing_country, $condition['value']);
                            if ($condition_eval) {
                                $color = $rule['row_colour'];
                                $i++;
                            }
                            break;
                        case 'shipping_country':
                            $shipping_country = $order->shipping_country;
                            $condition_eval = $this->condition_rule_symbol($condition['rule'], $shipping_country, $condition['value']);
                            if ($condition_eval) {
                                $color = $rule['row_colour'];
                                $i++;
                            }
                            break;
                        case 'billing_first_name':
                            $billing_first_name = $order->billing_first_name;
                            $condition_eval = $this->condition_rule_symbol($condition['rule'], $billing_first_name, $condition['value']);
                            if ($condition_eval) {
                                $color = $rule['row_colour'];
                                $i++;
                            }
                            break;
                        case 'billing_last_name':
                            $billing_last_name = $order->billing_last_name;
                            $condition_eval = $this->condition_rule_symbol($condition['rule'], $billing_last_name, $condition['value']);
                            if ($condition_eval) {
                                $color = $rule['row_colour'];
                                $i++;
                            }
                            break;
                        case 'shipping_first_name':
                            $shipping_first_name = $order->shipping_first_name;
                            $condition_eval = $this->condition_rule_symbol($condition['rule'], $shipping_first_name, $condition['value']);
                            if ($condition_eval) {
                                $color = $rule['row_colour'];
                                $i++;
                            }
                            break;
                        case 'shipping_last_name':
                            $shipping_last_name = $order->shipping_last_name;
                            $condition_eval = $this->condition_rule_symbol($condition['rule'], $shipping_last_name, $condition['value']);
                            if ($condition_eval) {
                                $color = $rule['row_colour'];
                                $i++;
                            }
                            break;
                        case 'customer_email':
                            $customer_email = $order->billing_email;
                            $condition_eval = $this->condition_rule_symbol($condition['rule'], $customer_email, $condition['value']);
                            if ($condition_eval) {
                                $color = $rule['row_colour'];
                                $i++;
                            }
                            break;
                        case 'telephone_number':
                            $telephone_number = $order->billing_phone;
                            $condition_eval = $this->condition_rule_symbol($condition['rule'], $telephone_number, $condition['value']);
                            if ($condition_eval) {
                                $color = $rule['row_colour'];
                                $i++;
                            }
                            break;
                        case 'shipping':
                            $shipping = $order->get_shipping_methods();
                            $condition_eval = false;
                            if (class_exists('WC_Shipping_Zones')) {
                                $zones = $this->get_shipping_zones($condition['value']);
                                $condition['value'] = array_merge($zones, array($condition['value']));
                            } else {
                                $condition['value'] = array($condition['value']);
                            }
                            if ($condition['rule'] == 'is') {
                                foreach ($shipping as $si) {
                                    if (strpos($si['method_id'], 'legacy_') !== false) {
                                        if (in_array($si['method_id'], $condition['value'])) $condition_eval = true;
                                    } else {
                                        if (strpos($si['method_id'], $condition['value'][0]) !== false) $condition_eval = true;
                                    }
                                }
                            } else if ($condition['rule'] == 'is not') {
                                foreach ($shipping as $si) {
                                    if (strpos($si['method_id'], 'legacy_') !== false) {
                                        if (!in_array($si['method_id'], $condition['value'])) $condition_eval = true;
                                    } else {
                                        if (strpos($si['method_id'], $condition['value'][0]) === false) $condition_eval = true;
                                    }
                                }
                            }
                            if ($condition_eval) {
                                $color = $rule['row_colour'];
                                $i++;
                            }
                            break;
                        case 'payment_method':
                            $payment_method = $order->payment_method;
                            $condition_eval = $this->condition_rule_symbol($condition['rule'], $payment_method, $condition['value']);
                            if ($condition_eval) {
                                $color = $rule['row_colour'];
                                $i++;
                            }
                            break;
                        case 'order_notes':
                            $order_notes = $order->customer_note;
                            $condition_eval = $this->condition_rule_symbol($condition['rule'], $order_notes, '');
                            if ($condition_eval) {
                                $color = $rule['row_colour'];
                                $i++;
                            }
                            break;

                        case 'products':
                            $items = $order->get_items();
                            $items_ids = array();
                            foreach ($items as $item) {
                                $items_ids[] = $item['product_id'];
                            }
                            $v = explode(',', $condition['value']);
                            $condition_eval = false;
                            if ($condition['rule'] == 'is') {
                                if (sizeof(array_intersect($items_ids, $v)) > 0)
                                    $condition_eval = true;
                            } else if ($condition['rule'] == 'is not') {
                                if (sizeof(array_intersect($items_ids, $v)) == 0)
                                    $condition_eval = true;
                            }
                            if ($condition_eval) {
                                $color = $rule['row_colour'];
                                $i++;
                            }
                            break;

                        case 'order_date':
                            if ($condition['rule'] == 'is after') {
                                $order_date = strtotime($order->get_date_created());
                                $date = strtotime(date('m/d/Y', $order_date));
                                $rule_date = strtotime($condition['value']);
                                $condition_eval = $this->condition_rule_symbol('is greater than', $date, $rule_date);
                            } else if ($condition['rule'] == 'is before') {
                                $order_date = strtotime($order->get_date_created());
                                $date = strtotime(date('m/d/Y', $order_date));
                                $rule_date = strtotime($condition['value']);
                                $condition_eval = $this->condition_rule_symbol('is less than', $date, $rule_date);
                            } else if ($condition['rule'] == 'in the last') {
                                $dd = explode(':', $condition['value']);
                                $rule_date = strtotime('-' . $dd[0] . ' ' . $dd[1]);

                                $order_date = strtotime($order->get_date_created());
                                $date = strtotime(date('m/d/Y', $order_date));

                                $condition_eval = $this->condition_rule_symbol('is greater than', $date, $rule_date);
                            } else if ($condition['rule'] == 'not in the last') {
                                $dd = explode(':', $condition['value']);
                                $rule_date = strtotime('-' . $dd[0] . ' ' . $dd[1]);

                                $order_date = strtotime($order->get_date_created());
                                $date = strtotime(date('m/d/Y', $order_date));

                                $condition_eval = $this->condition_rule_symbol('is less than', $date, $rule_date);
                            } else {
                                $order_date = strtotime($order->get_date_created());
                                $date = date('m/d/Y', $order_date);
                                $condition_eval = $this->condition_rule_symbol($condition['rule'], $date, $condition['value']);
                            }
                            if ($condition_eval) {
                                $color = $rule['row_colour'];
                                $i++;
                            }
                            break;
                        default:
                            $condition_eval = apply_filters('wc_order_rules_check_order', false, $condition['order_var'], $condition['rule'], $condition['value'], $post_id, $this);
                            if ($condition_eval) {
                                $color = $rule['row_colour'];
                                $i++;
                            }

                            break;
                    }
                }
                if ($rule['match_rules'] == 'all' && count($conditions) != $i) {
                    $color = $prev_color;
                }

            }
            return $color;
        }

        function condition_rule_symbol($rule = '', $f, $s)
        {
            if (empty($rule)) return false;
            switch ($rule) {
                case 'is':
                    if ($f == $s) return true;
                    else return false;
                    break;
                case 'is not':
                    if ($f != $s) return true;
                    else return false;
                    break;
                case 'contains':
                    if (strpos($f, $s) !== false) return true;
                    else return false;
                    break;
                case 'does not contain':
                    if (strpos($f, $s) === false) return true;
                    else return false;
                    break;
                case 'begins with':
                    $l = strlen($s);
                    if (substr($f, 0, $l) == $s) return true;
                    else return false;
                    break;
                case 'ends with':
                    $l = strlen($s);
                    if (substr($f, -$l) == $s) return true;
                    else return false;
                    break;
                case 'is greater than':
                    if ($f > $s) return true;
                    else return false;
                    break;
                case 'is less than':
                    if ($f < $s) return true;
                    else return false;
                    break;
                case 'is true':
                    if ($f) return true;
                    else return false;
                    break;
                case 'is false':
                    if (!$f) return true;
                    else return false;
                    break;
                default:
                    return false;
                    break;
            }
        }

        function restrict_manage_orders($value = '')
        {
            global $woocommerce, $typenow;
            if ('shop_order' != $typenow) {
                return;
            }
            $rules = WC_Order_Rules::get_data();
            if ($rules && !empty($rules) && is_array($rules)) {
                $selected = '';
                if (isset($_GET['shop_order_wc_order_rules']) && $_GET['shop_order_wc_order_rules'] != '') {
                    $selected = $_GET['shop_order_wc_order_rules'];
                }
                ?>
                <select name='shop_order_wc_order_rules' id='dropdown_shop_order_wc_order_rules'>
                    <option value=""><?php _e('All rules', 'wc_point_of_sale'); ?></option>
                    <?php
                    foreach ($rules as $rule) {
                        echo '<option value="' . $rule['ID'] . '" ' . selected($selected, $rule['ID'], false) . ' >' . stripslashes($rule['name']) . '</option>';
                    }
                    ?>
                </select>
                <?php
            }

        }

        public function orders_by_order_rules($query)
        {
            global $typenow, $wp_query, $wpdb;
            // set base query arguments
            if ($typenow == 'shop_order' && isset($_GET['shop_order_wc_order_rules']) && $_GET['shop_order_wc_order_rules'] != '' && !isset($_GET['no_filter_rules'])) {

                remove_filter('pre_get_posts', array($this, 'orders_by_order_rules'));

                $this->rulles = get_wc_order_rules($_GET['shop_order_wc_order_rules']);

                $post_in = array();
                $_GET['no_filter_rules'] = '1';
                $statuses = wc_get_order_statuses();
                $order_ids = get_posts(array(
                    'numberposts' => -1, // get all posts.
                    'post_type' => 'shop_order',
                    'post_status' => array_keys($statuses),
                    'fields' => 'ids', // Only get post IDs
                ));
                unset($_GET['no_filter_rules']);

                foreach ($order_ids as $id) {
                    if ($this->check_order($id))
                        $post_in[] = $id;
                }
                if (empty($post_in))
                    $post_in[] = 0;

                $query->set('post__in', $post_in);

                $this->rulles = get_wc_order_rules();

                add_filter('pre_get_posts', array($this, 'orders_by_order_rules'));
            }

            return $query;
        }

        public function get_shipping_zones($zone = false)
        {

            $zones = array();
            if ($zone && !empty($zone) && is_string($zone)) {
                if ($this->shipping_zones && isset($this->shipping_zones[$zone]))
                    return $this->shipping_zones[$zone];

                $vals = explode(' : ', $zone);
                if (!isset($vals[1]))
                    return $zones;
                $args = explode('-', $vals[0]);

                $method = $vals[0];
                $rate_id = $vals[1];
                $method_id = $args[1];

                global $wpdb;
                $rate_label = $wpdb->get_var("SELECT rate_label FROM {$wpdb->prefix}woocommerce_shipping_table_rates WHERE rate_id = {$rate_id} LIMIT 1");
                $rates = $wpdb->get_results("SELECT rate_id FROM {$wpdb->prefix}woocommerce_shipping_table_rates
                 WHERE rate_label = '{$rate_label}' AND shipping_method_id = {$method_id} ");
                if ($rates) {
                    foreach ($rates as $rate) {
                        $zones[] = $method . ' : ' . $rate->rate_id;
                    }
                }
                $this->shipping_zones[$zone] = $zones;
            }
            return $zones;
        }

    }

    $wc_order_rules = new WoocommerceOrderRules();
}