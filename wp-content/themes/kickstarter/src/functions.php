<?php // ==== FUNCTIONS ==== //
/* Remove direct access */

if (  !  defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/*  ********************************************************
 *   Load functions files
 *  ********************************************************
 */
/**
 * @param $folder
 * @return null
 */
function load_files( $folder ) {
    if (  !  $folder ) {
        return;
    }
    $directory = get_stylesheet_directory() . '/' . $folder . '/*.php';
    foreach ( glob( $directory ) as $element ) {
        require_once $element;
    }
}

/* Load Functions */
load_files( 'functions' );

/* Load all files from visual composer */
if ( defined( 'WPB_VC_VERSION' ) ) {
    load_files( 'functions/visual_composer' );
}

/* Load all other assets */
load_files( 'inc' );

/* Load action hooks */
load_files( 'functions/hooks' );

/* Shortcodes */
load_files( 'functions/shortcodes' );

/* AACF */
load_files( 'functions/acf' );

/* Check if Visual Composer is installed */
if ( defined( 'WPB_VC_VERSION' ) ) {
    load_files( 'functions/visual_composer' );
}

/* Insall all woocmmerce files */
if ( class_exists( 'WooCommerce' ) ) {
    load_files( 'functions/woocommerce' );
    load_files( 'functions/woocommerce/single' );
    load_files( 'functions/woocommerce/checkout' );
}

// Only the bare minimum to get the theme up and running
function voidx_setup() {

    // HTML5 support; mainly here to get rid of some nasty default styling that WordPress used to inject
    add_theme_support( 'html5', array( 'search-form', 'gallery' ) );

    // Automatic feed links
    // add_theme_support( 'automatic-feed-links' );

    // $content_width limits the size of the largest image size available via the media uploader
    // It should be set once and left alone apart from that; don't do anything fancy with it; it is part of WordPress core
    global $content_width;
    $content_width = 1140;

}

add_action( 'after_setup_theme', 'voidx_setup', 11 );

// Sidebar declaration
function voidx_widgets_init() {
    register_sidebar(
        array(
            'name'          => esc_html__( 'Footer', 'TEXT_DOMAIN' ),
            'id'            => 'sidebar-footer',
            'description'   => esc_html__( 'Appear at the bottom of each page', 'TEXT_DOMAIN' ),
            'before_widget' => '<div class="footer-item">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3>',
            'after_title'   => '</h3>'
        ) );
}

add_action( 'widgets_init', 'voidx_widgets_init' );

/* Unregister Standard Widgets */
function micnav_unregister_default_widgets() {

    $unregister_widgets = array(
        'WP_Widget_Pages', // PagesWidget;
        'WP_Widget_Calendar', // CalendarWidget;
        'WP_Widget_Archives', // ArchivesWidget;
        'WP_Widget_Links', // LinksWidget;
        'WP_Widget_Media_Audio', // AudioPlayerMediaWidget;
        'WP_Widget_Media_Image', // ImageMediaWidget;
        'WP_Widget_Media_Video', // VideoMediaWidget;
        'WP_Widget_Meta', // MetaWidget;
        'WP_Widget_Search', // SearchWidget;
        // 'WP_Widget_Text',            // TextWidget;
        'WP_Widget_Categories', // CategoriesWidget;
        'WP_Widget_Recent_Posts', // RecentPostsWidget;
        'WP_Widget_Recent_Comments', // RecentCommentsWidget;
        'WP_Widget_RSS', // RSSWidget;
        'WP_Widget_Tag_Cloud', // TagCloudWidget;
        'WP_Widget_Custom_HTML', // CustomHTMLWidget,
        'WP_Widget_Media_Gallery', // CustomHTMLWidget,
        # 'WP_Nav_Menu_Widget',        // MenusWidget;
        /* Woocomerce */
        'WC_Widget_Recent_Products',
        'WC_Widget_Featured_Products',
        'WC_Widget_Product_Categories',
        'WC_Widget_Product_Tag_Cloud',
        'WC_Widget_Cart',
        'WC_Widget_Layered_Nav',
        'WC_Widget_Layered_Nav_Filters',
        'WC_Widget_Price_Filter',
        'WC_Widget_Product_Search',
        'WC_Widget_Top_Rated_Products',
        'WC_Widget_Recent_Reviews',
        'WC_Widget_Recently_Viewed',
        'WC_Widget_Best_Sellers',
        'WC_Widget_Onsale',
        'WC_Widget_Random_Products'
    );

    foreach ( $unregister_widgets as $unregister_widget ) {
        unregister_widget( $unregister_widget );
    }
}

add_action( 'widgets_init', 'micnav_unregister_default_widgets', 11 );
/*  ********************************************************
 *   Get attachement data
 *  ********************************************************
 */

/**
 * @param $attachment_id
 * @param null $attachment_size
 * @return null
 */
function wp_get_attachment( $attachment_id = null, $attachment_size = null ) {

    if (  !  $attachment_id ) {
        return;
    }

    $attachment = get_post( $attachment_id );
    $src        = $attachment_size ? wp_get_attachment_image_src( $attachment_id, $attachment_size )[0] : $attachment->guid;
    return array(
        'alt'         => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
        'caption'     => $attachment->post_excerpt,
        'description' => $attachment->post_content,
        'href'        => get_permalink( $attachment->ID ),
        'src'         => $src,
        'title'       => $attachment->post_title,
        'guid'        => $attachment->guid
    );
}

add_filter( 'woocommerce_redirect_single_search_result', '__return_false' );
/**
 * @param $permalink
 * @return mixed
 */
function my_maybe_woocommerce_variation_permalink( $permalink ) {

    // check to see if the search was for a product variation SKU
    $sku  = get_search_query();
    $args = array(
        'post_type'      => 'product_variation',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'   => '_sku',
                'value' => $sku
            )
        )
    );
    $variation = get_posts( $args );
    // make sure the permalink we're filtering is for the parent product
    if ( get_permalink( wp_get_post_parent_id( $variation[0] ) ) !== $permalink ) {
        return $permalink;
    }
    if (  !  empty( $variation ) && function_exists( 'wc_get_attribute_taxonomy_names' ) ) {
        // this is a variation SKU, we need to prepopulate the filters
        $variation_id  = absint( $variation[0] );
        $variation_obj = new WC_Product_Variation( $variation_id );
        $attributes    = $variation_obj->get_variation_attributes();
        if ( empty( $attributes ) ) {
            return $permalink;
        }
        $permalink = add_query_arg( $attributes, $permalink );
    }
    return $permalink;
}

add_filter( 'the_permalink', 'my_maybe_woocommerce_variation_permalink' );

/**
 * Hide shipping rates when free shipping is available.
 * Updated to support WooCommerce 2.6 Shipping Zones.
 *
 * @param  array   $rates Array of rates found for the package.
 * @return array
 */
// Hide table rate shipping option when free shipping is available
add_filter( 'woocommerce_available_shipping_methods', 'hide_table_rate_shipping_when_free_is_available' );

/**
 *  Hide Table Rate shipping option when free shipping is available
 *
 * @param array $available_methods
 */
add_filter( 'woocommerce_available_shipping_methods', 'bbloomer_unset_shipping_when_free_is_available_in_zone', 10, 2 );

function bbloomer_unset_shipping_when_free_is_available_in_zone( $rates, $package ) {

    // Only unset rates if free_shipping is available

    unset( $rates['shipping_method_0_table_rate313'] );

    return $rates;

}

/* Replace the IP address with an empty string */
add_filter( 'gform_ip_address', '__return_empty_string' );
/*  ********************************************************
 *   Comment
 *  ********************************************************
 */
/**
 * @param $taxes
 * @param $price
 * @param $rates
 * @return mixed
 */
function yourname_fix_shipping_tax( $taxes, $price, $rates ) {

    if ( wc_prices_include_tax() ) {
        return WC_Tax::calc_inclusive_tax( $price, $rates );
    }

    return $taxes;
}

/* Add delivery cost to subtotal */
/**
 * @param $cart
 * @return null
 */
function add_shipping_cost_to_subtotal( $cart ) {
    if ( get_field( 'enable_new_subtotal_calculation', 'option' ) != true ) {
        return;
    }
    $cart->subtotal = $cart->cart_contents_total + $cart->shipping_total;
}

add_filter( 'woocommerce_calculate_totals', 'add_shipping_cost_to_subtotal' );

/* Vies numbers */
add_action( 'admin_head', 'admin_styles' );
function admin_styles() {
    echo '<style>
    #yith-system-alert {
      display:none!important;
    }
    .notice-error.yith-license-notice {
      display:none!important;
    }
    a.toplevel_page_yith_plugin_panel {
      // display:none!important;
    }
  </style>';
}

/**
 * @return null
 */
function remove_menu_items() {
    if ( get_current_user_id() == 1 ) {
        return;
    }
    remove_menu_page( 'yith_plugin_panel' );
}

add_action( 'admin_menu', 'remove_menu_items', 999 );

/* Remove wordpress PHP nug */
function kcr_remove_dashboard_widgets() {
    remove_meta_box( 'dashboard_php_nag', 'dashboard', 'normal' );
}

add_action( 'wp_dashboard_setup', 'kcr_remove_dashboard_widgets' );

/* Allow editing woocommerce refund custom amount  */

/**
 * @return null
 */
function remove_restriction_from_refund() {
    $currentPostType = get_post_type();
    if ( $currentPostType != 'shop_order' ) {
        return;
    }

    ?>
<script>
(function($) {
    $(document).ready(function() {
        $('#refund_amount').removeAttr('readonly');
    });
})(jQuery);
</script>
<?php }
add_action( 'admin_footer', 'remove_restriction_from_refund' );
// add_action( 'init', 'ats_remove_variations_decimals' );
/**
 * @return null
 */
function ats_remove_variations_decimals() {

    if (  !  current_user_can( 'administrator' ) ) {
        return;
    }

    $product_update_version = 1;
    $act_product_update     = get_option( 'act_product_update' );

    if ( $act_product_update == $product_update_version ) {
        return;
    }

    $args = array(
        'posts_per_page' => -1,
        'post_type'      => 'product'
    );

    $query = new WP_Query( $args );

    if ( $query->have_posts() ):
        while ( $query->have_posts() ):

            $query->the_post();
            $product = wc_get_product( get_the_ID() );

            if ( $product->is_type( 'variable' ) ) {

                foreach ( $product->get_available_variations() as $variation_values ) {
                    $variation_id = $variation_values['variation_id']; // variation id

                    $num = get_post_meta( $variation_id, '_price', true );
                    update_post_meta( $variation_id, '_regular_price', number_format( $num, 2, '.', '' ) );
                    update_post_meta( $variation_id, '_price', number_format( $num, 2, '.', '' ) );
                    wc_delete_product_transients( $variation_id ); // Clear/refresh the variation cache
                }
                wc_delete_product_transients( get_the_ID() );
            }

        endwhile;
        update_option( 'act_product_update', $product_update_version );
    endif;
    wp_reset_postdata();
}

require_once get_template_directory() . '/functions/brevo/_init.php';