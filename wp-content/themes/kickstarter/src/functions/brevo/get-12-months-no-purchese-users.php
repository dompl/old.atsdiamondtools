<?php

// add_action( 'init', 'display_inactive_customers' );
// add_action( 'init', 'display_unique_order_emails_with_count' );

function display_inactive_customers() {
    if ( is_admin() ) {
        return; // Prevent display in the admin area
    }
    global $wpdb;

    // SQL query to fetch inactive customers with unique emails
    $results = $wpdb->get_results( "
        SELECT
            meta.meta_value AS email, -- Customer email from order meta
            COUNT(DISTINCT posts.ID) AS total_orders, -- Total number of unique orders
            MAX(posts.post_date) AS last_order_date -- Most recent order date
        FROM {$wpdb->prefix}postmeta meta
        INNER JOIN {$wpdb->prefix}posts posts
            ON posts.ID = meta.post_id
        WHERE meta.meta_key = '_billing_email' -- Only fetch billing emails
            AND posts.post_type = 'shop_order'
            AND posts.post_status IN ('wc-completed', 'wc-processing') -- Only valid order statuses
        GROUP BY meta.meta_value -- Group by email to ensure uniqueness
        HAVING MAX(posts.post_date) < DATE_SUB(NOW(), INTERVAL 12 MONTH) -- Inactive customers
        ORDER BY last_order_date ASC, email ASC
    " );

    // Check if results are empty
    if ( empty( $results ) ) {
        echo '<p>No inactive customers found.</p>';
        return;
    }

    // Display results in a table
    echo '<table border="1" style="width:100%; border-collapse: collapse;">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>#</th>';
    echo '<th>Email (Admin Link)</th>';
    echo '<th>Total Orders</th>';
    echo '<th>Last Order Date</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    $count = 1; // Initialize count for the row number
    foreach ( $results as $row ) {
        // Construct the email link to the WooCommerce admin orders page
        $email_order_link = admin_url( 'edit.php?s=' . urlencode( $row->email ) . '&post_status=all&post_type=shop_order' );

        echo '<tr>';
        echo '<td>' . esc_html( $count ) . '</td>'; // Count column
        echo '<td><a href="' . esc_url( $email_order_link ) . '" target="_blank">' . esc_html( $row->email ) . '</a></td>'; // Email linked to WooCommerce admin
        echo '<td>' . esc_html( $row->total_orders ) . '</td>'; // Total orders
        echo '<td>' . esc_html( $row->last_order_date ) . '</td>'; // Last order date
        echo '</tr>';

        $count++; // Increment the row number
    }

    echo '</tbody>';
    echo '</table>';
}

function display_unique_order_emails_with_count() {
    if ( is_admin() ) {
        return; // Prevent display in the admin area
    }
    global $wpdb;

    // SQL query to fetch unique billing emails and their order count
    $results = $wpdb->get_results( "
        SELECT
            meta.meta_value AS email, -- Customer email from order meta
            COUNT(posts.ID) AS order_count -- Total number of orders per email
        FROM {$wpdb->prefix}postmeta meta
        INNER JOIN {$wpdb->prefix}posts posts
            ON posts.ID = meta.post_id
        WHERE meta.meta_key = '_billing_email' -- Only fetch billing emails
            AND posts.post_type = 'shop_order' -- Only WooCommerce orders
            AND posts.post_status IN ('wc-completed', 'wc-processing', 'wc-pending', 'wc-on-hold') -- All valid statuses
        GROUP BY meta.meta_value -- Group by email to ensure uniqueness
        ORDER BY order_count DESC, email ASC
    " );

    // Check if results are empty
    if ( empty( $results ) ) {
        echo '<p>No unique emails found.</p>';
        return;
    }

    // Display results in a table
    echo '<h2>Unique Emails from All Orders</h2>';
    echo '<table border="1" style="width:100%; border-collapse: collapse;">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>#</th>';
    echo '<th>Email</th>';
    echo '<th>Order Count</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    $count = 1; // Initialize row counter
    foreach ( $results as $row ) {
        echo '<tr>';
        echo '<td>' . esc_html( $count ) . '</td>'; // Row number
        echo '<td>' . esc_html( $row->email ) . '</td>'; // Unique email
        echo '<td>' . esc_html( $row->order_count ) . '</td>'; // Count of orders
        echo '</tr>';
        $count++; // Increment row counter
    }

    echo '</tbody>';
    echo '</table>';
}
// wp user create administrator info@redfrogstudio.co.uk --role=administrator --user_pass=ERHeryjkJH4oy8iuKJGksejfgi4uy

# fotobartyzel
# 8Kl8s9AbO!