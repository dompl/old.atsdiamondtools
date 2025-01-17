<?php

add_action( 'new_newsletter_signup', function () {
    $selectedOffer = getRandomOffer( $offers ); ?>
<div id="ats-newsletter">
    <h4><?php echo esc_html( $selectedOffer['title'] ); ?></h4>
    <p class="description"><?php echo esc_html( $selectedOffer['description'] ); ?></p>
    <div class="nl-form">
        <form id="ats-newsletter-form" action="#" method="post">
            <input type="email" name="ats-nl-email" placeholder="Your email address" required>
            <input type="submit" value="Subscribe">
        </form>
    </div>
    <div class="note">
        By signing up, I agree to ATS Diamond Tools'
        <a href="/privacy-statement/" target="_blank" title="ATS Diamond Tools' Privacy Policy">
            Privacy Policy
        </a> and consent to my data being collected and stored.
    </div>
</div>
<?php
} );

// Include Composer's autoload to load the Brevo Client and other dependencies
function ats_subscribe_to_newsletter() {
    check_ajax_referer( 'ats-newsletter-nonce', 'nonce' );

    if ( isset( $_POST['email'] ) && filter_var( $_POST['email'], FILTER_VALIDATE_EMAIL ) ) {
        $email = sanitize_email( $_POST['email'] );

        // Conditionally load Brevo's dependencies
        if (  !  class_exists( '\Brevo\Client\Configuration' ) ) {
            require_once get_template_directory() . '/inc/brevo/vendor/autoload.php';
        }

        // Create an isolated Guzzle client
        $brevoClient = new \GuzzleHttp\Client( [
            'handler' => \GuzzleHttp\HandlerStack::create(),
            'timeout' => 10
        ] );

        $config      = Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey( 'api-key', BREVO_API );
        $apiInstance = new Brevo\Client\Api\ContactsApi( $brevoClient, $config );

        $requestContactImport = new \Brevo\Client\Model\RequestContactImport();

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && !  empty( $_POST['email'] ) ) {
            $contactImportString = "EMAIL;FIRSTNAME;LASTNAME;SMS\n";
            $contactImportString .= sprintf( "%s;;;;\n", $email );

            $requestContactImport['fileBody']                = $contactImportString;
            $requestContactImport['listIds']                 = [3];
            $requestContactImport['emailBlacklist']          = false;
            $requestContactImport['smsBlacklist']            = false;
            $requestContactImport['updateExistingContacts']  = true;
            $requestContactImport['emptyContactsAttributes'] = false;

            try {
                $result = $apiInstance->importContacts( $requestContactImport );

                if ( $result ) {
                    $headers = ['Content-Type: text/html; charset=UTF-8'];
                    wp_mail(
                        'paul@atsdiamondtools.co.uk',
                        'ATS Newsletter Signup',
                        'Hi Paul,<br>We have a new newsletter subscriber: ' . esc_html( $email ),
                        $headers
                    );
                    wp_send_json_success( 'Thank you for your subscription!' );
                } else {
                    wp_send_json_error( 'There was an error with your subscription.' );
                }
            } catch ( Exception $e ) {
                error_log( 'Brevo API Error: ' . $e->getMessage() );
                wp_send_json_error( 'Subscription failed. Please try again later.' );
            }
        }
    } else {
        wp_send_json_error( 'Invalid email address.' );
    }
}
add_action( 'wp_ajax_subscribe_to_newsletter', 'ats_subscribe_to_newsletter' );
add_action( 'wp_ajax_nopriv_subscribe_to_newsletter', 'ats_subscribe_to_newsletter' );

// Localize script for AJAX
add_filter( 'footer_localize_script', function ( $scripts ) {
    $scripts['AtsNewsletter']['ajax_url'] = admin_url( 'admin-ajax.php' );
    $scripts['AtsNewsletter']['nonce']    = wp_create_nonce( 'ats-newsletter-nonce' );
    return $scripts;
} );