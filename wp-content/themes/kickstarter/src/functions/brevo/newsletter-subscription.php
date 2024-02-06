<?php

add_action( 'new_newsletter_signup', function () {$selectedOffer = getRandomOffer( $offers );?>
<div id="ats-newsletter">
    <h4><?php echo $selectedOffer['title']; ?></h4>
    <p class="description"><?php echo $selectedOffer['description']; ?></p>
    <div class="nl-form">
        <form action="#" method="post">
            <input type="email" name="ats-nl-email" placeholder="Your email address" required>
            <input type="submit" value="Subscribe">
        </form>
    </div>
    <div class="note">By signing up, I agree to ATS Diamond Tools' <a href="/privacy-statement/" target="_blank" title="ATS Diamond Tools' Privacy Policy">Privacy Policy</a> and consent to my data being collected and stored.</div>
</div>
<?php } );

// Include Composer's autoload to load the Brevo Client and other dependencies

function ats_subscribe_to_newsletter() {

    check_ajax_referer( 'ats-newsletter-nonce', 'nonce' );

    if ( isset( $_POST['email'] ) && filter_var( $_POST['email'], FILTER_VALIDATE_EMAIL ) ) {

        $email = sanitize_email( $_POST['email'] );

        // Configure API key authorization
        $config = Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey( 'api-key', BREVO_API );

        $apiInstance          = new Brevo\Client\Api\ContactsApi( new GuzzleHttp\Client(), $config );
        $requestContactImport = new \Brevo\Client\Model\RequestContactImport();

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && !  empty( $_POST['email'] ) ) {
            $email = filter_input( INPUT_POST, 'email', FILTER_SANITIZE_EMAIL );

            // Prepare the contact import string
            $contactImportString = "EMAIL;FIRSTNAME;LASTNAME;SMS\n";
            $contactImportString .= sprintf( "%s;;;;\n", $email ); // Add the email to the string

            $requestContactImport['fileBody']                = $contactImportString;
            $requestContactImport['listIds']                 = [3]; // Adjust the list ID accordingly
            $requestContactImport['emailBlacklist']          = false;
            $requestContactImport['smsBlacklist']            = false;
            $requestContactImport['updateExistingContacts']  = true;
            $requestContactImport['emptyContactsAttributes'] = false;

            $result = $apiInstance->importContacts( $requestContactImport );
            // Redirect or notify user of successful subscription

        }

        if ( $result ) {
            $email   = sanitize_email( $email ); // Make sure to sanitize the email address
            $headers = array( 'Content-Type: text/html; charset=UTF-8' );
            wp_mail(
                'paul@atsdiamondtools.co.uk',
                'ATS Newsletter Signup',
                'Hi Paul<br> We have a new newsletter subscriber on our website. The email address is ' . $email . '<br>Nice!',
                $headers
            );
            wp_send_json_success( 'Thank you for your subscription!' );
        } else {
            wp_send_json_error( 'There was an error with your subscription.' );
        }
    } else {
        wp_send_json_error( 'Invalid email address.' );
    }
}
add_action( 'wp_ajax_subscribe_to_newsletter', 'ats_subscribe_to_newsletter' );
add_action( 'wp_ajax_nopriv_subscribe_to_newsletter', 'ats_subscribe_to_newsletter' );

add_filter( 'footer_localize_script', function ( $scripts ) {
    $scripts['AtsNewsletter']['ajax_url'] = admin_url( 'admin-ajax.php' );
    $scripts['AtsNewsletter']['nonce']    = wp_create_nonce( 'ats-newsletter-nonce' );
    return $scripts;
} );