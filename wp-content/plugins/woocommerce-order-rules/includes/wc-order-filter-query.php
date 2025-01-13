<?php

/**
 *
 */
class WoocommerceOrderRulesFilters
{

    public static function init()
    {
        add_filter('pre_get_posts', array(__CLASS__, 'order_rules_pre_get_posts'), 1);
    }

    public static function order_rules_pre_get_posts($query)
    {
        global $typenow, $wp_query, $wpdb;

        $wpdb->show_errors();
        if ($typenow == 'shop_order' && !empty($_GET['shop_order_wc_order_rules'])) {

            $rulles = get_wc_order_rules($_GET['shop_order_wc_order_rules']);
            if (!$rulles) return $query;

            $rule = $rulles[0];

            $query_string = "SELECT posts.ID FROM {$wpdb->posts} posts ";
            $select = [];
            $filters = [];
            $joins = [];

            $match_rules = $rule['match_rules'] == 'all' ? ' AND ' : ' OR ';

            $i = 0;
            foreach ($rule['conditions'] as $condition) {
                $i++;
                $meta_fileds = array(
                    'customer_email' => 'billing_email',
                    'telephone_number' => 'billing_phone',
                );
                $order_var = isset($meta_fileds[$condition['order_var']]) ? $meta_fileds[$condition['order_var']] : $condition['order_var'];
                switch ($order_var) {
                    case 'order_status':
                        $filter = self::condition_rule_symbol($condition['rule'], "posts.post_status", $condition['value']);
                        if ($filter) {
                            $filters[] = $filter;
                        }
                        break;
                    case 'order_number':
                        $filter = self::condition_rule_symbol($condition['rule'], "posts.ID", $condition['value']);
                        if ($filter) {
                            $filters[] = $filter;
                        }
                        break;
                    case 'order_notes':
                        $filter = self::condition_rule_symbol($condition['rule'], "posts.post_excerpt", $condition['value']);
                        if ($filter) {
                            $filters[] = $filter;
                        }
                        break;
                    case 'order_total':
                    case 'billing_country':
                    case 'billing_state':
                    case 'billing_first_name':
                    case 'billing_last_name':
                    case 'billing_email':
                    case 'billing_phone':
                    case 'shipping_country':
                    case 'shipping_state':
                    case 'shipping_first_name':
                    case 'shipping_last_name':
                    case 'payment_method':
                        $field_name = "pm" . $i;
                        $meta_key = '_' . $order_var;
                        $filter = self::condition_rule_symbol($condition['rule'], "{$field_name}.meta_value", $condition['value']);
                        if ($filter) {
                            $joins[] = "LEFT JOIN {$wpdb->postmeta} {$field_name} ON (posts.ID = {$field_name}.post_id AND {$field_name}.meta_key = '{$meta_key}' )";
                            $filters[] = $filter;
                        }
                        break;
                    case 'purchased_items':
                        $fname = "items" . $i;
                        $finame = "itemmeta" . $i;
                        $new_query = "SELECT {$fname}.order_id as order_id, SUM({$finame}.meta_value) as purchased_items FROM {$wpdb->prefix}woocommerce_order_items {$fname}
              LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta {$finame} ON ({$fname}.order_item_id = {$finame}.order_item_id AND {$fname}.order_item_type = 'line_item' AND {$finame}.meta_key = '_qty')
              GROUP BY {$fname}.order_id";

                        $filter = self::condition_rule_symbol($condition['rule'], "{$fname}.purchased_items", $condition['value']);
                        if ($filter) {
                            $joins[] = "LEFT JOIN ({$new_query}) as {$fname} ON (posts.ID = {$fname}.order_id )";
                            $filters[] = $filter;
                        }
                        break;
                    case 'products':
                        $fname = "items" . $i;
                        $finame = "itemmeta" . $i;

                        $product_ids = $condition['value'];
                        if (!empty($product_ids)) {

                            $new_query_filter = "{$finame}.meta_value IN ({$product_ids})";
                            if ($condition['rule'] == 'is not') {
                                $new_query_filter = "{$finame}.meta_value NOT IN ({$product_ids})";
                            }

                            $new_query = "SELECT {$fname}.order_id as order_id, {$finame}.meta_value as product_id FROM {$wpdb->prefix}woocommerce_order_items {$fname}
                LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta {$finame} ON ({$fname}.order_item_id = {$finame}.order_item_id AND {$fname}.order_item_type = 'line_item' AND {$finame}.meta_key = '_product_id')
                WHERE {$new_query_filter}";

                            $filter = "{$fname}.product_id IN ({$product_ids})";
                            if ($condition['rule'] == 'is not') {
                                $filter = "{$fname}.product_id NOT IN ({$product_ids})";
                            }
                            if ($filter) {
                                $joins[] = "LEFT JOIN ({$new_query}) as {$fname} ON (posts.ID = {$fname}.order_id )";
                                $filters[] = $filter;
                            }

                        }
                        break;
                    case 'shipping':
                        $fname = "items" . $i;
                        $finame = "itemmeta" . $i;

                        $new_query_filter = self::condition_rule_symbol($condition['rule'], "{$finame}.meta_value", $condition['value']);

                        $new_query = "SELECT {$fname}.order_id as order_id, {$finame}.meta_value as shipping FROM {$wpdb->prefix}woocommerce_order_items {$fname}
              LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta {$finame} ON ({$fname}.order_item_id = {$finame}.order_item_id AND {$fname}.order_item_type = 'shipping' AND {$finame}.meta_key = 'method_id')
              WHERE {$new_query_filter}";

                        $filter = self::condition_rule_symbol($condition['rule'], "{$fname}.shipping", $condition['value']);
                        if ($filter) {
                            $joins[] = "LEFT JOIN ({$new_query}) as {$fname} ON (posts.ID = {$fname}.order_id )";
                            $filters[] = $filter;
                        }
                        break;
                    case 'order_date':
                        $date = date('Y-m-d', strtotime($condition['value']));

                        switch ($condition['rule']) {
                            case 'in the last':
                            case 'not in the last':
                                $dd = explode(':', $condition['value']);
                                $rule_date = strtotime('-' . $dd[0] . ' ' . $dd[1]);
                                $date = date('Y-m-d', $rule_date);
                                break;
                        }

                        $filter = self::condition_rule_symbol($condition['rule'], "DATE(posts.post_date)", "DATE('" . $date . "')");
                        if ($filter) {
                            $filters[] = $filter;
                        }
                        break;
                    default:
                        $filter = apply_filters('order_rules_pre_get_posts_filter', false, $order_var, $condition);
                        $join = apply_filters('order_rules_pre_get_posts_join', false, $order_var, $condition);
                        if ($filter) {
                            $filters[] = $filter;
                        }
                        if ($join) {
                            $joins[] = $join;
                        }
                        break;
                }

            }

            if (!empty($joins)) {
                $query_string .= implode(' ', $joins);
            }
            $query_string .= " WHERE " . implode($match_rules, $filters);


            $post_in = $wpdb->get_col($query_string);

            if (empty($post_in)) {
                $post_in = array(0);
            }
            $query->set('post__in', $post_in);
        }
        return $query;
    }


    private static function condition_rule_symbol($rule = '', $field = '', $value = '')
    {
        if (empty($rule)) return false;
        $s = "";
        switch ($rule) {
            case 'is':
                $value = is_string($value) ? "'" . $value . "'" : $value;
                $s = $field . " = " . $value;
                break;
            case 'is not':
                $value = is_string($value) ? "'" . $value . "'" : $value;
                $s = $field . " <> " . $value;
                break;
            case 'contains':
                $s = $field . " LIKE '%" . $value . "%'";
                break;
            case 'does not contain':
                $s = $field . " NOT LIKE '%" . $value . "%'";
                break;
            case 'begins with':
                $s = $field . " LIKE '" . $value . "%'";
                break;
            case 'ends with':
                $s = $field . " LIKE '%" . $value . "'";
                break;
            case 'is greater than':
            case 'is after':
            case 'in the last':
                $s = $field . " > " . $value;
                break;
            case 'is less than':
            case 'is before':
            case 'not in the last':
                $s = $field . " < " . $value;
                break;
            case 'is true':
                $s = $field . " <> ''";
                break;
            case 'is false':
                $s = $field . " == ''";
                break;
            default:
                return false;
                break;
        }
        return $s;
    }
}

WoocommerceOrderRulesFilters::init();