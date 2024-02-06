<?php
// If Uncomment the line below to add all subscribers
// add_action( 'init', 'brevo_import_subscribers' );

function brevo_import_subscribers() {

// Configure API key authorization
    $config = Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey( 'api-key', BREVO_API );
// $config = Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey('partner-key', 'YOUR_PARTNER_KEY');

    $apiInstance          = new Brevo\Client\Api\ContactsApi( new GuzzleHttp\Client(), $config );
    $requestContactImport = new \Brevo\Client\Model\RequestContactImport();

// Initialize contact import string
    $contactImportString = "EMAIL;FIRSTNAME;LASTNAME;SMS\n";

// Fetch WooCommerce customers
    $args      = array( 'role' => 'customer' );
    $customers = get_users( $args );

    foreach ( $customers as $customer ) {
        // Fetch first and last name from user meta
        $first_name = get_user_meta( $customer->ID, 'first_name', true );
        $last_name  = get_user_meta( $customer->ID, 'last_name', true );
        // Optionally, handle SMS if you have that information
        $sms = ''; // Assume SMS data is available or leave empty if not

        // Append customer data to the import string
        // Ensure to escape any semicolon in data to avoid format issues
        $contactImportString .= sprintf(
            "%s;%s;%s;%s\n",
            $customer->user_email,
            str_replace( ';', '\;', $first_name ),
            str_replace( ';', '\;', $last_name ),
            $sms // Leave as is if you don't have SMS info
        );
    }

    $requestContactImport['fileBody']                = $contactImportString;
    $requestContactImport['listIds']                 = [3]; // Specify the list ID(s) contacts should be added to
    $requestContactImport['emailBlacklist']          = false;
    $requestContactImport['smsBlacklist']            = false;
    $requestContactImport['updateExistingContacts']  = true;
    $requestContactImport['emptyContactsAttributes'] = false;

    try {
        $result = $apiInstance->importContacts( $requestContactImport );
        update_option( 'brev_subscribers_import', 1 );
    } catch ( Exception $e ) {
        echo 'Exception when calling ContactsApi->importContacts: ', $e->getMessage(), PHP_EOL;
    }

}