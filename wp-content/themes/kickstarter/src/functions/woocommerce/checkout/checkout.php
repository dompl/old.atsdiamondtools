<?php
/*  ********************************************************
 *   Checkout functions
 *  ********************************************************
 */

function sv_require_second_street_address_field($fields) {
    $fields['address_1']['label'] = esc_attr__('House/flat name or number, streen name', 'woocommerce');
    unset($fields['address_2']);
    return $fields;
}

add_filter('woocommerce_default_address_fields', 'sv_require_second_street_address_field');

/**
 * Add additional tesx under newseltter.
 * Note,this is injected with plugin core. With any plugin update please add hook  do_action('after_newseltter_signup'); in areas of line 442
 * wp-content/plugins/woocommerce-subscribe-to-newsletter/woocommerce-subscribe-to-newsletter.php
 * ---
 */
add_action('after_newseltter_signup', 'additional_text_newsletter_signup');

function additional_text_newsletter_signup() {

    $privacy_policy_page_id = 62;

    echo '<p id="newsletter-consent">Join our mailing list to receive emails from us with news, product updates and special offers. You can unsubscribe at any point in the future using the unsubscribe link on any of our newsletter emails. By checking this box you are agreeing to our <a href="' . esc_url(get_permalink($privacy_policy_page_id)) . '" title="Privacy Statement & Cookie Notice" target="_blank">Privacy Statement & Cookie Notice.</a></p>';
};

add_action('woocommerce_checkout_after_terms_and_conditions', 'gdpr_terms_additional_note');

function gdpr_terms_additional_note() {

    $privacy_policy_page_id = 62;

    echo '<p id="gdpr-term-conditions">This form collects your name, email, billing address, shipping address, telephone number and VAT number, so we can process your order. Check out our <a href="' . esc_url(get_permalink($privacy_policy_page_id)) . '" title="Privacy Statement & Cookie Notice" target="_blank">privacy statement</a> to see how we protect and manage your data. We will not store or collect your payment card details. That information is provided directly to our third-party payment processors whose use of your personal information is governed by their Privacy Policy. By checking this box you are agreeing to our <a href="' . esc_url(get_permalink($privacy_policy_page_id)) . '" title="Privacy Statement & Cookie Notice" target="_blank">Privacy Statement & Cookie Notice.</a> and <a href="'.esc_url(wc_get_page_permalink('terms')).'" target="_blank" class="woocommerce-terms-and-conditions-link">terms &amp; conditions</a></p>';
}
