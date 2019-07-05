<?php
/**
 * Use this file for all your template filters and actions.
 * Requires WooCommerce PDF Invoices & Packing Slips 1.4.13 or higher
 */
if ( ! defined('ABSPATH')) {
  exit;
}
// Exit if accessed directly

add_filter('wpo_wcpdf_woocommerce_totals', 'wpo_wcpdf_woocommerce_totals_custom', 10, 3);
function wpo_wcpdf_woocommerce_totals_custom($totals, $order, $document_type) {

  if (get_field('enable_new_subtotal_calculation', 'option') != true) {
    return $totals;
  }

  $position = get_option('woocommerce_currency_pos');
  if ($position == 'left') {
    $currency_left  = get_woocommerce_currency_symbol();
    $currency_right = '';
  } elseif ($position == 'right') {
    $currency_left  = '';
    $currency_right = get_woocommerce_currency_symbol();
  } elseif ($position == 'left_space') {
    $currency_left  = get_woocommerce_currency_symbol() . ' ';
    $currency_right = '';
  } elseif ($position == 'right_space') {
    $currency_left  = '';
    $currency_right = ' ' . get_woocommerce_currency_symbol();
  }

  $totals['cart_subtotal'] = array(
    'label' => __('Subtotal', 'wpo_wcpdf'),
    'value' => $currency_left . ($order->get_subtotal() + $order->get_shipping_total()) . $currency_right,
  );
  /* Grab all values into variables */
  $subtotals      = $totals['cart_subtotal'];
  $shipping       = $totals['shipping'];
  $tax            = $totals['tax'];
  $payment_method = $totals['payment_method'];
  $order_total    = $totals['order_total'];

  /* Unset variables on the invoice to change order for subtotals */
  unset($totals['cart_subtotal']);
  unset($totals['shipping']);
  unset($totals['tax']);
  unset($totals['payment_method']);
  unset($totals['order_total']);

  /* Set new order for subtotals */
  $totals['shipping']       = $shipping;
  $totals['subtotals']      = $subtotals;
  $totals['tax']            = $tax;
  $totals['payment_method'] = $payment_method;
  $totals['order_total']    = $order_total;
  return $totals;
}