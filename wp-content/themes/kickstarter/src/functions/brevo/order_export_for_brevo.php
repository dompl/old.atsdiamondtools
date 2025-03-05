<?php
/**
 * Generate CSV export for Brevo contacts.
 *
 * This function queries all WooCommerce orders since the specified start date and builds a CSV string
 * containing the following columns: EMAIL;FIRSTNAME;LASTNAME;SMS.
 * It only includes orders where the customer did not opt out of the newsletter.
 *
 * @return string CSV data ready for import to Brevo.
 */
function ats_generate_brevo_export_csv() {
    // Define the start date for export in Y-m-d H:i:s format.
    $start_date_str = '2025-02-21 00:00:00';

    // Create a WC_DateTime object from the start date.
    $start_date_obj = new WC_DateTime( $start_date_str );
    $start_date_obj->setTimezone( wp_timezone() );

    // Convert the WC_DateTime object back to a formatted string.
    $formatted_date = $start_date_obj->date( 'Y-m-d H:i:s' );
    error_log( 'Export start date: ' . $formatted_date );

    // Query all orders created from the specified date.
    $orders = wc_get_orders( array(
        'limit'        => -1,
        'date_created' => '>=' . $formatted_date
    ) );
    error_log( 'Number of orders found: ' . count( $orders ) );

    // Prepare an array to hold unique contacts, keyed by email.
    $contacts = array();

    // Loop through each order and collect the contact details.
    foreach ( $orders as $order ) {
        $user_email = $order->get_billing_email();
        if ( empty( $user_email ) ) {
            // Optionally provide a fallback email.
            $user_email = 'info@redfrogstudio.co.uk';
        }

        // Check if the customer opted out.
        // A meta value of 1 indicates the customer ticked the opt-out box.
        $subscribe = get_post_meta( $order->get_id(), '_newsletter_subscribe', true );
        if ( $subscribe ) {
            continue;
        }

        // Add the contact details if not already added.
        if (  !  isset( $contacts[$user_email] ) ) {
            $contacts[$user_email] = array(
                'first_name' => $order->get_billing_first_name(),
                'last_name'  => $order->get_billing_last_name()
            );
        }
    }

    // Build the CSV string with a header row.
    $csv_data = "EMAIL;FIRSTNAME;LASTNAME;SMS\n";
    foreach ( $contacts as $email => $details ) {
        $csv_data .= sprintf( "%s;%s;%s;\n", $email, $details['first_name'], $details['last_name'] );
    }

    return $csv_data;
}

// Hook the export trigger to admin_init so it's executed after post types are registered.
// add_action( 'admin_init', 'handle_brevo_csv_export' );
// function handle_brevo_csv_export() {
//     if ( isset( $_GET['export_brevo_csv'] ) ) {
//         header( 'Content-Type: text/csv' );
//         header( 'Content-Disposition: attachment; filename="brevo-contacts.csv"' );
//         echo ats_generate_brevo_export_csv();
//         exit;
//     }
// }