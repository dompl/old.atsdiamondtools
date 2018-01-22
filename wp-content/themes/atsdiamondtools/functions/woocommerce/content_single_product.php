<?php
/*  ********************************************************
 *   Single product
 *  ********************************************************
 */
/* Product Image */



function ats_single_product_open_container() {
  echo '<div class="container" id="single-product-container"><div class="susy-reset">';
}
function ats_single_product_close_container() {
  echo '</div></div>';
}

/*  ********************************************************
 *   Set up full product
 *  ********************************************************
 */



add_action('template_redirect', 'ats_setup_signle_product');
function ats_setup_signle_product()
{
  /* Set the product */

  /* Remove all acttions */
  add_action('woocommerce_before_single_product', 'ats_single_product_open_container',10);
  add_action('woocommerce_before_single_product_summary', 'ats_single_product_layout');
  add_action('woocommerce_after_single_product', 'ats_single_product_close_container',100);

  remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10);
  remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);

  remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title',  5 );
  remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
  remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
  remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
  remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
  remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
  remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );

  remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
  remove_action('woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15);
  remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
}
