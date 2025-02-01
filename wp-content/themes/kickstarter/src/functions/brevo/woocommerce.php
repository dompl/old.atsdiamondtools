<?php

add_action( 'woocommerce_before_order_notes', 'ats_add_checkout_newsletter_opt_in', 9 );

function ats_add_checkout_newsletter_opt_in() {
    woocommerce_form_field( 'newsletter_subscribe', array(
        'type'  => 'checkbox',
        'class' => array( 'input-checkbox' ),
        'label' => __( 'I do not wish to sign up for the ATS Diamond Tools newsletter', 'woocommerce' )
    ) );
}

add_action( 'woocommerce_order_status_completed', 'ats_subscribe_customer_to_newsletter' );

function ats_subscribe_customer_to_newsletter( $order_id ) {
    $order      = wc_get_order( $order_id );
    $user_email = $order->get_billing_email();
    $first_name = $order->get_billing_first_name();
    $last_name  = $order->get_billing_last_name();
    $subscribe  = get_post_meta( $order_id, '_newsletter_subscribe', true );

    if (  !  $subscribe && !  empty( $user_email ) ) {
        // Now you have $user_email, $first_name, and $last_name
        // Prepare the contact import string with first name and last name
        $contactImportString = "EMAIL;FIRSTNAME;LASTNAME;SMS\n";
        $contactImportString .= sprintf( "%s;%s;%s;\n", $user_email, $first_name, $last_name );

        // Assuming you have a function to handle the Brevo subscription
        ats_handle_newsletter_subscription( $contactImportString );
    }
}

function ats_handle_newsletter_subscription( $contactImportString ) {

    // Adjust the path if your vendor directory is elsewhere
    require_once __DIR__ . '/vendor/autoload.php';

    // Configure API key authorisation using the fully qualified class name
    $config = \Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey( 'api-key', BREVO_API );

    // Create a new instance of the ContactsApi
    $apiInstance = new \Brevo\Client\Api\ContactsApi(
        new GuzzleHttp\Client(),
        $config
    );

    // Create and configure the request model
    $requestContactImport = new \Brevo\Client\Model\RequestContactImport();

    // Set the contact import details (adjust setters as per your library's version if needed)
    $requestContactImport['fileBody']                = $contactImportString;
    $requestContactImport['listIds']                 = [3]; // Adjust list ID as required
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