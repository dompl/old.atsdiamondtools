<?php
/**
 * Shipping Methods Display
 *
 * In 2.1 we show methods per package. This allows for multiple methods per order if so desired.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-shipping.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @author      WooThemes
 * @package     WooCommerce/Templates
 * @version     3.2.0
 */
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}
?>
<?php if (is_cart()): ?>
  <div class="shipping-totals shipping">
    <div class="shipping-totals-inner">
      <div id="shipping-method-header">
        <h3 class="basket-total-title basket-total-title-shipping"><?php echo esc_attr( $package_name ); ?></h3>
      </div>
      <?php else: ?>
        <tr class="shipping">
          <th><?php echo wp_kses_post( $package_name ); ?></th>
          <td data-title="<?php echo esc_attr( $package_name ); ?>">
          <?php endif ?>
          <?php if ( 1 < count( $available_methods ) ) : ?>
            <?php echo is_cart() ? '<div id="shipping-method-list">' : '' ?>
            <ul id="shipping_method">
              <?php foreach ( $available_methods as $method ) : ?>

                <li>
                  <?php
                  printf( '<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />
                    <label for="shipping_method_%1$d_%2$s">%5$s</label>',
                    $index, sanitize_title( $method->id ), esc_attr( $method->id ), checked( $method->id, $chosen_method, false ), '<span class="s-wrap">' . wc_cart_totals_shipping_method_label( $method ) . '</span>' );
                  do_action( 'woocommerce_after_shipping_rate', $method, $index );
                  ?>
                  <?php if (sanitize_title( $method->id ) == 'table_rate829'): ?>
                    <div class="shipping_method_description"><?php echo __('(If ordered Mon-Fri before 4pm)' , 'TEXT_DOAMIN') ?></div>
                  <?php endif ?>
                </li>
              <?php endforeach; ?>
            </ul>
            <?php echo is_cart() ? '</div>' : '' ?>
            <?php elseif ( 1 === count( $available_methods ) ) :  ?>
              <?php
              $method = current( $available_methods );
              printf( '%3$s <input type="hidden" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d" value="%2$s" class="shipping_method" />', $index, esc_attr( $method->id ), wc_cart_totals_shipping_method_label( $method ) );
              do_action( 'woocommerce_after_shipping_rate', $method, $index );
              ?>
              <?php echo is_cart() ? '<div class="shipping-info shipping-info-checkout">' : ''; ?>
              <?php // echo wpautop( __( 'Shipping costs will be calculated once you have provided your address.', 'woocommerce' ) ); ?>
              <?php echo is_cart() ? '</div>' : '' ?>
              <?php else : ?>
                <?php echo is_cart() ? '<div class="shipping-info shipping-info-na">' : ''; ?>
                <?php echo apply_filters( is_cart() ? 'woocommerce_cart_no_shipping_available_html' : 'woocommerce_no_shipping_available_html', wpautop( __( 'There are no shipping methods available. Please double check your address, or contact us if you need any help.', 'woocommerce' ) ) ); ?>
                <?php echo is_cart() ? '</div>' : '' ?>
              <?php endif; ?>

              <?php if ( $show_package_details ) : ?>
                <?php echo '<p class="woocommerce-shipping-contents"><small>' . esc_html( $package_details ) . '</small></p>'; ?>
              <?php endif; ?>
              <?php echo is_cart() ? '</div></div>' : '</td></tr>' ?>

              <?php if ( ! empty( $show_shipping_calculator ) ) : ?>
                <div class="shipping-calculator">
                  <div class="shipping-calculator-inner">
                    <?php woocommerce_shipping_calculator(); ?>
                  </div>
                </div>
                <?php endif; ?>