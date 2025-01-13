<?php
/**
 *
 * @author    Actuality Extensions
 * @package   WooCommerceOrderRules/Classes
 * @category    Class
 * @since     0.1
 */


if (!defined('ABSPATH')) exit; // Exit if accessed directly


class WC_Order_Rules
{
    public function display_table()
    {
        global $wc_order_rules,
               $wc_order_rules_table;
        ?>
        <div class="wrap">
            <h2>
                <?php echo get_admin_page_title(); ?>
                <a class="add-new-h2"
                   href="?page=wc_order_rules&action=new"><?php _e('Add Rule', 'wc_order_rules'); ?></a>
                <?php if (isset($_GET['s']) && !empty($_GET['s'])) { ?>
                    <span class="subtitle">Search results for “<?php echo $_GET['s']; ?>”</span>
                <?php } ?>
            </h2>
            <?php if (isset($_GET['message']) && !empty($_GET['message'])) {
                $message = self::get_message($_GET['message']);
                if (!empty($message)) {
                    ?>
                    <div class="<?php echo $message['class']; ?> below-h2" id="message">
                        <p><?php echo $message['text']; ?></p></div>
                <?php }
            } ?>
            <form method="get" action="" class="search-form">
                <p class="search-box">
                    <label for="rules-search-input"
                           class="screen-reader-text"><?php _e('Search Rules', 'wc_order_rules'); ?></label>
                    <input type="hidden" value="wc_order_rules" name="page">
                    <input type="search" value="" name="s" id="rules-search-input">
                    <input type="submit" value="<?php _e('Search Order Rules', 'wc_order_rules'); ?>" class="button"
                           id="search-submit" name="">
                </p>
                <br class="clear">
            </form>
            <form method="post" action="" id="wc_order_rules_form">
                <?php
                $wc_order_rules_table->prepare_items();
                $wc_order_rules_table->display();
                ?>
            </form>
        </div>
        <?php
    }

    public static function get_data($ids = '')
    {
        global $wpdb, $wc_order_rules;
        $filter = '';
        if (!empty($ids)) {
            if (is_array($ids)) {
                $ids = implode(',', array_map('intval', $ids));
                $filter .= "WHERE ID IN  == ($ids)";
            } else {
                $filter .= "WHERE ID = $ids";
            }
        }
        if (isset($_GET['s']) && !empty($_GET['s']) && $_GET['page'] == $wc_order_rules->id) {
            $s = $_GET['s'];
            $filter = "WHERE lower( concat(name) ) LIKE lower('%$s%')";
        }
        $table_name = $wpdb->prefix . "wc_order_rules";
        $db_data = $wpdb->get_results("SELECT * FROM $table_name $filter ORDER BY ordering ASC");
        $data = array();

        foreach ($db_data as $value) {
            $data[] = get_object_vars($value);
        }
        return $data;
    }

    public static function get_order_statuses()
    {
        $statuses = array();
        if (floatval(WC_VERSION) < 2.2) {
            global $wpdb;
            $query = "SELECT name, slug
  						FROM {$wpdb->terms}
  						LEFT JOIN {$wpdb->prefix}term_taxonomy as taxonomy
  									ON ({$wpdb->terms}.term_id = taxonomy.term_id)
  						WHERE taxonomy.taxonomy = 'shop_order_status'
  		";
            $results = $wpdb->get_results($query, 'ARRAY_A');
            foreach ($results as $value) {
                $statuses[$value['slug']] = $value['name'];
            }
        } else {
            $statuses = wc_get_order_statuses();
        }
        return $statuses;
    }

    function get_countries_list()
    {
        $countries = WC()->countries->countries;
        asort($countries);
        return $countries;
    }

    function get_filed_values($keys = array())
    {
        $values = array();
        if (!empty($keys)) {
            foreach ($keys as $key) {
                $values[$key] = __($key, 'wc_order_rules');
            }
        }
        return $values;
    }

    function get_products_list()
    {
        $values = array();
        $args = array('post_type' => 'product', 'posts_per_page' => -1);
        $loop = new WP_Query($args);
        if ($loop->have_posts()) {
            foreach ($loop->posts as $post) {
                $values[$post->ID] = $post->post_title;
            }
        }
        return $values;
    }

    function get_shipping_methods()
    {
        $methods = array();
        $ss = WC()->shipping->load_shipping_methods();
        if (!empty($ss)) {
            foreach ($ss as $code => $s) {
                $methods[$code] = $s->method_title;
            }
        }
        global $wpdb;
        $wpdb->hide_errors();
        $shipping_table_rates = $wpdb->get_results("SELECT  rate.rate_id, rate.rate_label, rate.shipping_method_id, methods.shipping_method_type, zone.zone_id, zone.zone_name
	    	FROM {$wpdb->prefix}woocommerce_shipping_table_rates as rate
	    	LEFT JOIN {$wpdb->prefix}woocommerce_shipping_zone_shipping_methods methods ON(rate.shipping_method_id = methods.shipping_method_id)
	    	LEFT JOIN {$wpdb->prefix}woocommerce_shipping_zones zone ON(zone.zone_id = methods.zone_id)
	    	GROUP BY rate.rate_label, rate.shipping_method_id
	    	");
        if ($shipping_table_rates) {
            foreach ($shipping_table_rates as $rate) {
                $code = $rate->shipping_method_type . '-' . $rate->shipping_method_id . ' : ' . $rate->rate_id;
                if ($rate->zone_id == 0)
                    $methods[$code] = $rate->rate_label;
                else
                    $methods[$code] = $rate->zone_name . ' - ' . $rate->rate_label;
            }
        }
        return $methods;
    }

    public function get_payment_methods()
    {
        $methods = array();
        $gateways = WC()->payment_gateways;
        foreach ($gateways->payment_gateways as $gateway) {
            $methods[$gateway->id] = $gateway->title;
        }
        if (is_plugin_active('woocommerce-point-of-sale/woocommerce-point-of-sale.php')) {
            $methods['pos_chip_pin'] = __('Chip & PIN', 'wc_point_of_sale');
        }
        return $methods;
    }

    public function get_conditions_fields($field = '')
    {
        $conditions_fields = array(
            'order_status' => array(
                'name' => __('Order Status', 'wc_order_rules'),
                'first_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_filed_values(array('contains', 'does not contain', 'begins with', 'ends with', 'is', 'is not'))
                    )
                ),
                'second_col' => array(
                    array(
                        'type' => 'text'
                    ),
                    array(
                        'type' => 'select',
                        'values' => $this->get_order_statuses()
                    )
                )
            ),
            'order_number' => array(
                'name' => __('Order Number', 'wc_order_rules'),
                'first_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_filed_values(array('is', 'is not', 'is greater than', 'is less than'))
                    )
                ),
                'second_col' => array(
                    array(
                        'type' => 'number'
                    )
                )
            ),
            'order_total' => array(
                'name' => __('Order Total', 'wc_order_rules'),
                'first_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_filed_values(array('is', 'is not', 'is greater than', 'is less than'))
                    )
                ),
                'second_col' => array(
                    array(
                        'type' => 'number'
                    )
                )
            ),
            'purchased_items' => array(
                'name' => __('Purchased Items', 'wc_order_rules'),
                'first_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_filed_values(array('is', 'is not', 'is greater than', 'is less than'))
                    )
                ),
                'second_col' => array(
                    array(
                        'type' => 'number'
                    )
                )
            ),
            'billing_country' => array(
                'name' => __('Billing Country', 'wc_order_rules'),
                'first_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_filed_values(array('is', 'is not'))
                    )
                ),
                'second_col' => array(
                    array(
                        'type' => 'multi_select',
                        'values' => $this->get_countries_list()
                    )
                )
            ),
            'shipping_country' => array(
                'name' => __('Shipping Country', 'wc_order_rules'),
                'first_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_filed_values(array('is', 'is not'))
                    )
                ),
                'second_col' => array(
                    array(
                        'type' => 'multi_select',
                        'values' => $this->get_countries_list()
                    )
                )
            ),
            'billing_state' => array(
                'name' => __('Billing State', 'wc_order_rules'),
                'first_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_filed_values(array('is', 'is not'))
                    )
                ),
                'second_col' => array(
                    array(
                        'type' => 'text'
                    )
                )
            ),
            'shipping_state' => array(
                'name' => __('Shipping State', 'wc_order_rules'),
                'first_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_filed_values(array('is', 'is not'))
                    )
                ),
                'second_col' => array(
                    array(
                        'type' => 'text'
                    )
                )
            ),
            'billing_first_name' => array(
                'name' => __('Billing First Name', 'wc_order_rules'),
                'first_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_filed_values(array('contains', 'does not contain', 'begins with', 'ends with', 'is', 'is not'))
                    )
                ),
                'second_col' => array(
                    array(
                        'type' => 'text'
                    )
                )
            ),
            'billing_last_name' => array(
                'name' => __('Billing Last Name', 'wc_order_rules'),
                'first_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_filed_values(array('contains', 'does not contain', 'begins with', 'ends with', 'is', 'is not'))
                    )
                ),
                'second_col' => array(
                    array(
                        'type' => 'text'
                    )
                )
            ),
            'shipping_first_name' => array(
                'name' => __('Shipping First Name ', 'wc_order_rules'),
                'first_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_filed_values(array('contains', 'does not contain', 'begins with', 'ends with', 'is', 'is not'))
                    )
                ),
                'second_col' => array(
                    array(
                        'type' => 'text'
                    )
                )
            ),
            'shipping_last_name' => array(
                'name' => __('Shipping Last Name', 'wc_order_rules'),
                'first_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_filed_values(array('contains', 'does not contain', 'begins with', 'ends with', 'is', 'is not'))
                    )
                ),
                'second_col' => array(
                    array(
                        'type' => 'text'
                    )
                )
            ),
            'customer_email' => array(
                'name' => __('Customer Email', 'wc_order_rules'),
                'first_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_filed_values(array('contains', 'does not contain', 'begins with', 'ends with', 'is', 'is not'))
                    )
                ),
                'second_col' => array(
                    array(
                        'type' => 'text'
                    )
                )
            ),
            'telephone_number' => array(
                'name' => __('Telephone Number', 'wc_order_rules'),
                'first_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_filed_values(array('contains', 'does not contain', 'begins with', 'ends with', 'is', 'is not'))
                    )
                ),
                'second_col' => array(
                    array(
                        'type' => 'text'
                    )
                )
            ),
            'shipping' => array(
                'name' => __('Shipping Method', 'wc_order_rules'),
                'first_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_filed_values(array('is', 'is not'))
                    )
                ),
                'second_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_shipping_methods()
                    )
                )
            ),
            'order_notes' => array(
                'name' => __('Order Notes', 'wc_order_rules'),
                'first_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_filed_values(array('is true', 'is false'))
                    )
                )
            ),
            'payment_method' => array(
                'name' => __('Payment Method', 'wc_order_rules'),
                'first_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_filed_values(array('is', 'is not'))
                    )
                ),
                'second_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_payment_methods()
                    )
                )
            ),
            'order_date' => array(
                'name' => __('Order Date', 'wc_order_rules'),
                'first_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_filed_values(array('is', 'is not', 'is after', 'is before', 'in the last', 'not in the last'))
                    )
                ),
                'second_col' => array(
                    array(
                        'type' => 'date'
                    ),
                    array(
                        'type' => 'number'
                    ),
                    array(
                        'type' => 'select',
                        'values' => $this->get_filed_values(array('days', 'weeks', 'months'))
                    )
                )
            ),
            'products' => array(
                'name' => __('Product', 'wc_order_rules'),
                'first_col' => array(
                    array(
                        'type' => 'select',
                        'values' => $this->get_filed_values(array('is', 'is not'))
                    )
                ),
                'second_col' => array(
                    array(
                        'type' => 'multi_select',
                        'values' => $this->get_products_list()
                    )
                )
            )
        );
        $conditions_fields = apply_filters('wc_order_rules_conditions_fields', $conditions_fields, $this);

        if (!empty($field)) {
            return $conditions_fields[$field];
        }
        return $conditions_fields;
    }

    public static function get_conditions_data($id = 0)
    {
        if (!$id) return false;
        global $wpdb;
        $table_name = $wpdb->prefix . "wc_order_rules_conditions";
        $db_data = $wpdb->get_results("SELECT * FROM $table_name WHERE rule_id = $id");
        $data = array();

        foreach ($db_data as $value) {
            $data[] = get_object_vars($value);
        }
        return $data;
    }

    function display_edit_form($id = 0)
    {
        $data = array(
            'name' => '',
            'row_colour' => '',
            'enable_row_colours' => 'off',
            'match_rules' => 'any',
        );

        if ($id) {
            $order_rul_data = self::get_data($id);
            foreach ($order_rul_data as $key => $value) {
                $data = array(
                    'ID' => $value['ID'],
                    'name' => $value['name'],
                    'row_colour' => $value['row_colour'],
                    'enable_row_colours' => $value['enable_row_colours'],
                    'match_rules' => $value['match_rules'],
                );
            }
        }
        $conditions_data = array();
        $conditions_fields = $this->get_conditions_fields();
        if (isset($data['ID']) && !empty($data['ID'])) {
            $conditions_data = self::get_conditions_data($data['ID']);
        }
        if (empty($conditions_data)) $conditions_data = array(array('ID' => 'new_0', 'order_var' => 'order_status', 'rule' => '', 'value' => ''));

        ?>
        <div class="wrap">
            <?php if ($id > 0) { ?>
                <h2><?php _e('Edit Rule', 'wc_order_rules'); ?></h2>
            <?php } else { ?>
                <h2><?php _e('Add New Rule', 'wc_order_rules'); ?></h2>
            <?php } ?>
            <div id="ajax-response"></div>
            <form id="wc_order_rules_form" class="validate" action="" method="post" autocomplete="off">
                <input type="hidden" value="save-wc-order-rule" name="action">
                <input type="hidden" value="<?php echo $id; ?>" name="id">
                <?php wp_nonce_field('nonce-save-wc-order-rule'); ?>
                <input type="hidden" name="removed_rows" value="" id="removed_rows">
                <table class="form-table">
                    <tr class="form-field form-required">
                        <th>
                            <label for="rule_name"><?php _e('Name', 'wc_order_rules'); ?></label>

                        </th>
                        <td>
                            <input type="text" aria-required="true" size="40" id="rule_name" name="rule_name"
                                   value="<?php echo stripslashes($data['name']); ?>">
                            <p class="description">The name of the Order Rule as it would appear to the users using this
                                plugin.</p>
                        </td>
                    </tr>
                    <tr class="form-field form-required">
                        <th>
                            <label><?php _e('Conditions', 'wc_order_rules'); ?></label>
                        </th>
                        <td>
                            <div id="match_rules_wrap">
                                Match <select name="match_rules" id="match_rules">
                                    <?php echo '<option value="all" ' . ($data['match_rules'] == 'all' ? 'selected="selected"' : '') . '>All</option>'; ?>
                                    <?php echo '<option value="any" ' . ($data['match_rules'] == 'any' ? 'selected="selected"' : '') . '>Any</option>'; ?>
                                </select>
                                of the following rules:
                            </div>
                            <table class="conditions_table widefat conditions_table_edit">
                                <thead>
                                <tr>
                                    <th class="manage-column column-cb check-column"><input type="checkbox"></th>
                                    <th class="variable-column"><?php _e('Variable', 'wc_order_rules'); ?></th>
                                    <th class="rule-column"><?php _e('Rule', 'wc_order_rules'); ?></th>
                                    <th class="value-column"><?php _e('Value', 'wc_order_rules'); ?></th>
                                </tr>
                                </thead>
                                <tbody id="conditions">
                                <?php if (!empty($conditions_data)) { ?>
                                    <?php foreach ($conditions_data as $condition) { ?>
                                        <tr data-index="<?php echo $condition['ID']; ?>">
                                            <th class="check-column"><input type="checkbox"
                                                                            value="<?php echo $condition['ID']; ?>">
                                            </th>
                                            <td class="order_var">
                                                <select name="rules[<?php echo $condition['ID']; ?>][order_var]">
                                                    <?php foreach ($conditions_fields as $key => $value) { ?>
                                                        <option value="<?php echo $key; ?>" <?php echo($key == $condition['order_var'] ? 'selected="selected"' : ''); ?> ><?php echo $value['name']; ?></option>
                                                    <?php } ?>
                                                </select>
                                            </td>
                                            <td class="order_rule">
                                                <?php
                                                if (isset($conditions_fields[$condition['order_var']]['first_col'])) {
                                                    $i = 0;
                                                    foreach ($conditions_fields[$condition['order_var']]['first_col'] as $field_id => $field) {
                                                        switch ($field['type']) {
                                                            case 'text':
                                                                echo '<input type="text" class="' . $key . '_inp"  name="rules[' . $condition['ID'] . '][rule][' . $i . ']" value="' . $condition['rule'] . '">';
                                                                break;
                                                            case 'number':
                                                                echo '<input type="number" step="any" min="0" class="' . $key . '_inp" name="rules[' . $condition['ID'] . '][rule][' . $i . ']" value="' . $condition['rule'] . '">';
                                                                break;
                                                            case 'date':
                                                                echo '<input type="text" class="' . $key . '_inp ui_date" name="rules[' . $condition['ID'] . '][rule][' . $i . ']" value="' . $condition['rule'] . '">';
                                                                break;
                                                            case 'select':
                                                                echo '<select class="' . $key . '_inp __chosen_select" name="rules[' . $condition['ID'] . '][rule][' . $i . ']">';
                                                                foreach ($field['values'] as $val => $name) {
                                                                    echo '<option value="' . $val . '" ' . ($val == $condition['rule'] ? 'selected="selected"' : '') . ' >' . $name . '</option>';
                                                                }
                                                                echo '</select>';
                                                                break;
                                                            case 'multi_select':
                                                                echo '<select multiple="multiple" class="' . $key . '_inp chosen_multi_select" name="rules[' . $condition['ID'] . '][rule][' . $i . '][]">';
                                                                foreach ($field['values'] as $val => $name) {
                                                                    echo '<option value="' . $val . '" ' . ($val == $condition['rule'] ? 'selected="selected"' : '') . '>' . $name . '</option>';
                                                                }
                                                                echo '</select>';
                                                                break;
                                                        }
                                                        $i++;
                                                    }
                                                }
                                                ?>
                                            </td>
                                            <td class="order_value">
                                                <?php
                                                if (isset($conditions_fields[$condition['order_var']]['second_col'])) {
                                                    $i = 0;
                                                    foreach ($conditions_fields[$condition['order_var']]['second_col'] as $field_id => $field) {
                                                        switch ($field['type']) {
                                                            case 'text':
                                                                echo '<input type="text" class="' . $key . '_inp"  name="rules[' . $condition['ID'] . '][value][' . $i . ']" value="' . $condition['value'] . '">';
                                                                break;
                                                            case 'number':
                                                                if ($condition['order_var'] == 'order_date') {
                                                                    if ($condition['rule'] != 'is' && $condition['rule'] != 'is not' && $condition['rule'] != 'is after' && $condition['rule'] != 'is before') {
                                                                        $d = explode(':', $condition['value']);
                                                                        echo '<input type="number" step="any" min="0" class="' . $key . '_inp" name="rules[' . $condition['ID'] . '][value][' . $i . ']" value="' . $d[0] . '">';
                                                                    }
                                                                } else {
                                                                    echo '<input type="number" step="any" min="0" class="' . $key . '_inp" name="rules[' . $condition['ID'] . '][value][' . $i . ']" value="' . $condition['value'] . '">';
                                                                }
                                                                break;
                                                            case 'date':
                                                                if ($condition['order_var'] == 'order_date') {
                                                                    if ($condition['rule'] == 'is' || $condition['rule'] == 'is not' || $condition['rule'] == 'is after' || $condition['rule'] == 'is before') {
                                                                        echo '<input type="text" class="' . $key . '_inp ui_date"  name="rules[' . $condition['ID'] . '][value][' . $i . ']" value="' . $condition['value'] . '">';
                                                                    }
                                                                } else {
                                                                    echo '<input type="text" class="' . $key . '_inp ui_date" name="rules[' . $condition['ID'] . '][value][' . $i . ']" value="' . $condition['value'] . '">';
                                                                }
                                                                break;
                                                            case 'select':
                                                                if ($condition['order_var'] == 'order_date') {
                                                                    if ($condition['rule'] != 'is' && $condition['rule'] != 'is not' && $condition['rule'] != 'is after' && $condition['rule'] != 'is before') {
                                                                        $d = explode(':', $condition['value']);
                                                                        echo '<select class="' . $key . '_inp __chosen_select" name="rules[' . $condition['ID'] . '][value][' . $i . ']">';
                                                                        foreach ($field['values'] as $val => $name) {
                                                                            echo '<option value="' . $val . '" ' . ($val == $d[1] ? 'selected="selected"' : '') . '>' . $name . '</option>';
                                                                        }
                                                                        echo '</select>';
                                                                    }
                                                                } else {
                                                                    echo '<select class="' . $key . '_inp __chosen_select" name="rules[' . $condition['ID'] . '][value][' . $i . ']">';
                                                                    foreach ($field['values'] as $val => $name) {
                                                                        echo '<option value="' . $val . '" ' . ($val == $condition['value'] ? 'selected="selected"' : '') . '>' . $name . '</option>';
                                                                    }
                                                                    echo '</select>';
                                                                }
                                                                break;
                                                            case 'multi_select':

                                                                $c = explode(',', $condition['value']);
                                                                echo '<select multiple="multiple" class="' . $key . '_inp chosen_multi_select" name="rules[' . $condition['ID'] . '][value][' . $i . '][]">';
                                                                foreach ($field['values'] as $val => $name) {
                                                                    echo '<option value="' . $val . '" ' . (in_array($val, $c) ? 'selected="selected"' : '') . '>' . $name . '</option>';
                                                                }
                                                                echo '</select>';

                                                                break;
                                                        }
                                                        $i++;
                                                    }
                                                }
                                                ?>
                                            </td>

                                        </tr>
                                    <?php }
                                } ?>

                                </tbody>
                                <tfoot>
                                <tr>
                                    <th colspan="4" style="padding: 20px;">
                                        <a class="button plus insert" id="insert_condition_row"
                                           href="#"><?php _e('Insert Row', 'wc_order_rules'); ?></a>
                                        <a class="button minus remove_conditions"
                                           href="#"><?php _e('Remove Selected Row(s)', 'wc_order_rules'); ?></a>
                                    </th>
                                </tr>
                                </tfoot>
                            </table>
                            <p class="description"><?php _e('Add and configure conditions which need to be met in order for this order rule to be activated.', 'wc_order_rules'); ?></p>
                        </td>
                    </tr>
                    <tr class="form-field form-required">
                        <th>
                            <label for="enable_row_colours"><?php _e('Enable Coloured Rows', 'wc_order_rules'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="enable_row_colours"
                                   id="enable_row_colours" <?php checked($data['enable_row_colours'], 'on', true);
                            echo ($data['enable_row_colours'] == 'on') ? 'checked="checked"' : ''; ?>/>
                            <span class="description"><?php _e('Check this box to enable coloured rows for orders that match the conditions set above.', 'wc_order_rules'); ?></span>
                        </td>

                    </tr>
                    <tr id="row_colours"
                        class="form-field form-required" <?php echo $data['enable_row_colours'] != 'on' ? 'style="display:none;"' : ''; ?>>
                        <th><label for="row_colour"><?php _e('Row Colour', 'wc_order_rules'); ?></label></th>
                        <td><input type="text" name="row_colour" id="row_colour"
                                   value="#<?php echo $data['row_colour']; ?>"/>
                            <p class="description"><?php _e('Define the colour of the row of the orders that match this rule.', 'wc_order_rules'); ?></p>
                        </td>

                    </tr>
                </table>
                <p class="submit"><input type="submit" value="Save Order Rule" class="button button-primary"
                                         id="submit"></p>
            </form>
            <div id="default_f">
                <?php
                foreach ($conditions_fields as $key => $value) {
                    if (!isset($value['first_col'])) continue;
                    $i = 0;
                    foreach ($value['first_col'] as $field_id => $field) {
                        switch ($field['type']) {
                            case 'text':
                                echo '<input type="text" class="' . $key . '_inp_first"  name="rules[__replace__][rule][' . $i . ']">';
                                break;
                            case 'number':
                                echo '<input type="number" step="any"  min="0" class="' . $key . '_inp_first" name="rules[__replace__][rule][' . $i . ']">';
                                break;
                            case 'date':
                                echo '<input type="text" class="' . $key . '_inp_first ui_date" name="rules[__replace__][rule][' . $i . ']">';
                                break;
                            case 'select':
                                echo '<select class="' . $key . '_inp_first __chosen_select" name="rules[__replace__][rule][' . $i . ']">';
                                foreach ($field['values'] as $val => $name) {
                                    echo '<option value="' . $val . '">' . $name . '</option>';
                                }
                                echo '</select>';
                                break;
                            case 'multi_select':
                                echo '<select multiple="multiple" class="' . $key . '_inp_first chosen_multi_select" name="rules[__replace__][rule][' . $i . '][]">';
                                foreach ($field['values'] as $val => $name) {
                                    echo '<option value="' . $val . '">' . $name . '</option>';
                                }
                                echo '</select>';
                                break;
                        }
                        $i++;
                    }
                }
                ?>
                <?php
                foreach ($conditions_fields as $key => $value) {
                    if (!isset($value['second_col'])) continue;
                    $i = 0;
                    foreach ($value['second_col'] as $field_id => $field) {
                        switch ($field['type']) {
                            case 'text':
                                echo '<input type="text" class="' . $key . '_inp_second"  name="rules[__replace__][value][' . $i . ']">';
                                break;
                            case 'number':
                                echo '<input type="number" step="any"  min="0" class="' . $key . '_inp_second" name="rules[__replace__][value][' . $i . ']">';
                                break;
                            case 'date':
                                echo '<input type="text" class="' . $key . '_inp_second ui_date" name="rules[__replace__][value][' . $i . ']">';
                                break;
                            case 'select':
                                echo '<select class="' . $key . '_inp_second __chosen_select" name="rules[__replace__][value][' . $i . ']">';
                                foreach ($field['values'] as $val => $name) {
                                    echo '<option value="' . $val . '">' . $name . '</option>';
                                }
                                echo '</select>';
                                break;
                            case 'multi_select':
                                echo '<select multiple="multiple" class="' . $key . '_inp_second chosen_multi_select" name="rules[__replace__][value][' . $i . '][]">';
                                foreach ($field['values'] as $val => $name) {
                                    echo '<option value="' . $val . '">' . $name . '</option>';
                                }
                                echo '</select>';
                                break;
                        }
                        $i++;
                    }
                }
                ?>
            </div>
        </div>
        <?php
    }

    function save($id = 0)
    {
        global $wpdb,
               $wc_order_rules;
        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'nonce-save-wc-order-rule'))
            die(__('Action failed. Please refresh the page and retry.', 'woocommerce'));
        extract($_POST);
        $data['name'] = $rule_name;
        $data['match_rules'] = $match_rules;
        $data['enable_row_colours'] = isset($enable_row_colours) && $enable_row_colours == 'on' ? 'on' : 'off';
        $data['row_colour'] = str_replace('#', '', $row_colour);

        $conditions = array();
        if (isset($rules) && !empty($rules)) {
            foreach ($rules as $key => $value) {
                $conditions[$key]['order_var'] = $value['order_var'];
                $conditions[$key]['rule'] = '';
                $conditions[$key]['value'] = '';
                if (isset($value['rule']) && !empty($value['rule'])) {
                    foreach ($value['rule'] as $k => $r_val) {
                        if (!empty($conditions[$key]['rule'])) $conditions[$key]['rule'] .= ':';
                        $conditions[$key]['rule'] .= $r_val;
                    }
                }

                if (isset($value['value']) && !empty($value['value'])) {
                    foreach ($value['value'] as $v_val) {
                        if (empty($v_val)) continue;
                        if (!empty($conditions[$key]['value'])) $conditions[$key]['value'] .= ':';
                        if (is_array($v_val)) $v_val = implode(',', $v_val);
                        $conditions[$key]['value'] .= $v_val;
                    }
                }
            }
        }
        $table_rules = $wpdb->prefix . "wc_order_rules";
        $table_conditions = $wpdb->prefix . "wc_order_rules_conditions";
        $message = 0;
        if ($id) {
            $rows_affected = $wpdb->update($table_rules, $data, array('ID' => $id));
            $message = 2;
        } else {
            $max_order_result = $wpdb->get_results("SELECT MAX(ordering) as max FROM $table_rules");

            if ($max_order_result) {
                $max_order = $max_order_result[0]->max + 1;
                $data['ordering'] = $max_order;
            } else {
                $data['ordering'] = 1;
            }
            $rows_affected = $wpdb->insert($table_rules, $data);
            $id = $wpdb->insert_id;
            $message = 1;
        }
        if ($id && !empty($conditions)) {
            foreach ($conditions as $condition_id => $value) {
                if (is_int($condition_id)) {
                    $result = $wpdb->update($table_conditions, $value, array('ID' => $condition_id));
                } else {
                    $value['rule_id'] = $id;
                    $result = $wpdb->insert($table_conditions, $value);
                }

            }

        }
        if ($removed_rows && !empty($removed_rows)) {
            $wpdb->query("DELETE FROM $table_conditions WHERE ID IN ($removed_rows)");
        }
        return wp_redirect(add_query_arg(array("page" => $wc_order_rules->id, "message" => $message), 'admin.php'));

    }

    function delete($ids = 0)
    {
        global $wpdb, $wc_order_rules;
        if ($ids) {
            if (is_array($ids)) {
                $ids = implode(',', array_map('intval', $ids));
                $filter .= "WHERE ID IN ($ids)";
                $filter2 .= "WHERE rule_id IN ($ids)";
            } else {
                $filter .= "WHERE ID = $ids";
                $filter2 .= "WHERE rule_id = $ids";
            }
            $table_name = $wpdb->prefix . "wc_order_rules";
            $con_table = $wpdb->prefix . "wc_order_rules_conditions";
            $query = "DELETE FROM $table_name $filter";
            $query2 = "DELETE FROM $con_table $filter2";
            if ($wpdb->query($query) && $wpdb->query($query2)) {
                return wp_redirect(add_query_arg(array("page" => $wc_order_rules->id, "message" => 3), 'admin.php'));
            }
        }
        return wp_redirect(add_query_arg(array("page" => $wc_order_rules->id), 'admin.php'));
    }

    function get_message($id = 0)
    {
        $message = array();
        switch ($id) {
            case 1:
                $message['class'] = 'updated';
                $message['text'] = __('New order rule added.', 'wc_order_rules');
                break;
            case 2:
                $message['class'] = 'updated';
                $message['text'] = __('Order rule updated.', 'wc_order_rules');
                break;
            case 3:
                $message['class'] = 'updated';
                $message['text'] = __('Order rule(s) deleted.', 'wc_order_rules');
                break;
        }
        return $message;
    }
}