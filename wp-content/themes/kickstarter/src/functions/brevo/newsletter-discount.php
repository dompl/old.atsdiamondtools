<?php

add_action( 'init', 'auto_apply_coupon_based_on_query' );

function auto_apply_coupon_based_on_query() {

    if ( isset( $_GET['r8HpZdKoDrTl7_UPhuq'] ) ) {
        $coupon_code = 'MARCH2024';
        if ( class_exists( 'WooCommerce' ) && WC()->session ) {
            $coupon = new WC_Coupon( $coupon_code );
            if ( $coupon->get_id() ) {

                $expiry_date = $coupon->get_date_expires();
                if (  !  $expiry_date || $expiry_date->getTimestamp() > time() ) {
                    if (  !  WC()->cart->has_discount( $coupon_code ) ) {
                        WC()->cart->add_discount( $coupon_code );
                        if (  !  isset( $_COOKIE['ats_coupon_code'] ) ) {
                            setcookie( 'ats_coupon_code', 'yes', 0, COOKIEPATH, COOKIE_DOMAIN );
                            add_action( 'wp_footer', 'coupon_applied_popup_script' );
                        }
                    }
                }
            }
        }
    }
}

function coupon_applied_popup_script() {
    echo '<div class="remodal" data-remodal-id="modal">
    <button data-remodal-action="close" class="remodal-close"></button>
    <img src="https://atsdiamondtools.co.uk/wp-content/themes/kickstarter/build/img/theme/logo-new.png" width="300" />
    <h1>Congratulations! Your 10% discount is now successfully activated.</h1>
    <p>
    Your exclusive 10% discount will be automatically applied at the checkout, ensuring you enjoy immediate savings on your purchase. There\'s no need for you to do anything further - simply proceed to checkout to see the discount applied.<br><br><strong>Enjoy your shopping with us!</strong>
    </p>
    <br>
    <button data-remodal-action="confirm" class="remodal-confirm">OK</button>
  </div>';
}