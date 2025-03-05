<?php
/**
 * Functions related to Brevo subscription on WooCommerce order completion
 */

use Brevo\Client\Api\ContactsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\RequestContactImport;
use GuzzleHttp\Client;

// Add the opt-out checkbox field to the checkout page
add_action( 'woocommerce_before_order_notes', 'ats_add_checkout_newsletter_opt_in', 9 );
function ats_add_checkout_newsletter_opt_in() {
    woocommerce_form_field( 'newsletter_subscribe', array(
        'type'  => 'checkbox',
        'class' => array( 'input-checkbox' ),
        'label' => __( 'I do not wish to sign up for the ATS Diamond Tools newsletter', 'woocommerce' )
    ) );
}

// Save the checkbox value to order meta when checkout is processed
add_action( 'woocommerce_checkout_update_order_meta', 'ats_save_newsletter_opt_in' );
function ats_save_newsletter_opt_in( $order_id ) {
    // Checkbox is set if the customer opts out, so we save 1 if ticked, 0 if not.
    $subscribe = isset( $_POST['newsletter_subscribe'] ) ? 1 : 0;
    update_post_meta( $order_id, '_newsletter_subscribe', $subscribe );
}

// Process the newsletter subscription when the order is completed
// add_action( 'woocommerce_order_status_completed', 'ats_subscribe_customer_to_newsletter' );
add_action( 'woocommerce_checkout_order_processed', 'ats_subscribe_customer_to_newsletter' );
function ats_subscribe_customer_to_newsletter( $order_id ) {

    $order      = wc_get_order( $order_id );
    $user_email = $order->get_billing_email();
    $first_name = $order->get_billing_first_name();
    $last_name  = $order->get_billing_last_name();
    $subscribe  = get_post_meta( $order_id, '_newsletter_subscribe', true );

    // If the opt-out checkbox is NOT ticked (i.e. $subscribe equals 0) and email exists, subscribe the customer.
    if (  !  $subscribe && !  empty( $user_email ) ) {
        // Build the contact import string
        $contactImportString = "EMAIL;FIRSTNAME;LASTNAME;SMS\n";
        $contactImportString .= sprintf( "%s;%s;%s;\n", $user_email, $first_name, $last_name );

        ats_handle_newsletter_subscription( $contactImportString );
    }
}

function ats_handle_newsletter_subscription( $contactImportString ) {
    // Adjust the path to vendor/autoload.php if needed
    // require_once __DIR__ . '/vendor/autoload.php';

    if (  !  class_exists( '\Brevo\Client\Configuration' ) ) {
        require_once get_template_directory() . '/inc/brevo/vendor/autoload.php';
    }

    // Configure API key (ensure BREVO_API is defined, e.g. in wp-config.php)
    $config = Configuration::getDefaultConfiguration()->setApiKey( 'api-key', BREVO_API );

    // Create a new instance of the ContactsApi
    $apiInstance = new ContactsApi( new Client(), $config );

    // Create and configure the request model
    $requestContactImport                            = new RequestContactImport();
    $requestContactImport['fileBody']                = $contactImportString;
    $requestContactImport['listIds']                 = [3]; // Replace 3 with the actual Brevo list ID
    $requestContactImport['emailBlacklist']          = false;
    $requestContactImport['smsBlacklist']            = false;
    $requestContactImport['updateExistingContacts']  = true;
    $requestContactImport['emptyContactsAttributes'] = false;

    try {
        $result = $apiInstance->importContacts( $requestContactImport );
        error_log( 'Brevo import successful: ' . json_encode( $result ) );
    } catch ( Exception $e ) {
        error_log( 'Brevo API Error: ' . $e->getMessage() );
    }
}

/**
 * Redirect WordPress fatal error recovery emails to a different address.
 */
add_filter( 'recovery_mode_email', 'custom_recovery_mode_email', 10, 2 );
function custom_recovery_mode_email( $email, $url ) {
    // Change the recipient
    $email['to'] = 'info@redfrogstudio.co.uk';

    // Optionally customise the subject
    $email['subject'] = 'Site Fatal Error Notification';

    // Optionally customise the message body
    $email['message'] .= "\n\nPlease note this email was redirected to info@redfrogstudio.co.uk.";

    return $email;
}