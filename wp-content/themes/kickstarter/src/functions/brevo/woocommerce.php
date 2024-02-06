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
    // Your existing Brevo API subscription code here
    // Configure API key authorization
    $config = Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey( 'api-key', BREVO_API );

    $apiInstance          = new Brevo\Client\Api\ContactsApi( new GuzzleHttp\Client(), $config );
    $requestContactImport = new \Brevo\Client\Model\RequestContactImport();

    // Set the contact import details
    $requestContactImport['fileBody']                = $contactImportString;
    $requestContactImport['listIds']                 = [3]; // Adjust the list ID accordingly
    $requestContactImport['emailBlacklist']          = false;
    $requestContactImport['smsBlacklist']            = false;
    $requestContactImport['updateExistingContacts']  = true;
    $requestContactImport['emptyContactsAttributes'] = false;

    try {
        $result = $apiInstance->importContacts( $requestContactImport );
        // Optionally, handle success, such as logging or sending a confirmation
    } catch ( Exception $e ) {
        // Handle errors, such as logging them or sending error notifications
    }
}