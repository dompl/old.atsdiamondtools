<?php
defined( 'ABSPATH' ) || die( "No script kiddies please!" );
/*-----------------------------------------------------------------------------------*/
/*	AG up sell
/*-----------------------------------------------------------------------------------*/

class AG_up_sell {

    public static $single_instance = null;
    public static $args = array();
    
    
    /**
	 * run
	 */
	public static function run_instance( $args = array() ) {
		if ( self::$single_instance === null ) {
			self::$args            = $args;
			self::$single_instance = new self();
		}

		return self::$single_instance;
    }


 
    

    public static function setup_plugins() {

	    if (!class_exists('AG_ePDQ_Helpers')) {
		    return;
	    }

	    $transient_name = self::$args['plugin_slug'] .'_AG_post_upsell';
	    $site_posts = array();
	    $posts_got = get_transient( $transient_name );

	    if( empty($posts_got)) {

		    $response = wp_safe_remote_get( 'https://weareag.co.uk/wp-json/wp/v2/product?per_page=4&_embed');

		    if( !is_wp_error( $response ) && $response['response']['code'] === 200 ) {

			    $posts = json_decode( wp_remote_retrieve_body( $response ) );

			    foreach( $posts as $post ) {

				    $posts_got[] = array(
					    'title' =>  AG_ePDQ_Helpers::AG_escape( $post->title->rendered ),
					    'tip_url' => AG_ePDQ_Helpers::AG_escape( $post->link ),
					    'tip_img' => AG_ePDQ_Helpers::AG_escape( $post->_embedded->{'wp:featuredmedia'}[0]->source_url ),
					    'id' => AG_ePDQ_Helpers::AG_escape( $post->id ),
				    );

			    }
			    set_transient( $transient_name, $site_posts, 12 * HOUR_IN_SECONDS );

		    }

	    }

	    return $posts_got;

    }


    public static function output_up_sells() {
	    $upsells = self::setup_plugins();
	    foreach ( $upsells as $key => $upsell ) {

		    $response = wp_safe_remote_get( 'https://weareag.co.uk/wp-json/cmb2/v1/boxes/bhww_slides_metabox/fields/?object_type=post&object_id='. $upsell['id']);
		    $prices = wp_safe_remote_get( 'https://weareag.co.uk/wp-json/cmb2/v1/boxes/product/fields/?object_type=post&object_id='. $upsell['id']); //
		    $post_meta = array();

		    if ((!is_wp_error($response) && $response['response']['code'] === 200) || (!is_wp_error($prices) && $prices['response']['code'] === 200)) {

			    $posts = json_decode(wp_remote_retrieve_body($response));
			    $price = json_decode(wp_remote_retrieve_body($prices));

			    $post_meta[$key] = array(
				    'bold' => AG_ePDQ_Helpers::AG_escape($posts->_bhww_bold->value),
				    'short' => AG_ePDQ_Helpers::AG_escape($posts->_bhww_short->value),
				    'img' => AG_ePDQ_Helpers::AG_escape($posts->_bhww_schema_img->value),
				    'colour' => AG_ePDQ_Helpers::AG_escape($posts->_bhww_colour->value),
				    'colour2' => AG_ePDQ_Helpers::AG_escape($posts->_bhww_colour2->value),
				    'price' => AG_ePDQ_Helpers::AG_escape($price->_agd_single->value),
			    );

		    }

		    ?>



            <a class="product-card" href="<?php echo $upsell['tip_url']; ?>?utm_source=<?php echo self::$args['plugin_slug']; ?>&utm_medium=plugin_up_sell" style="width: 378.667px; float: left; margin-right: 32px">
                <div class="product-card-image" style="background: linear-gradient(135deg, <?php echo $post_meta[$key]['colour']; ?> 0%, <?php echo $post_meta[$key]['colour2']; ?> 100%);">
                    <div class="brand-logo"><img src="<?php echo $upsell['tip_img']; ?>"></div>
                    <div class="ag-logo"><img src="https://i2m5b5q7.rocketcdn.me/wp-content/themes/AGv6/assets/img/ag-logo.svg"></div>
                </div>
                <div class="product-card-content">
                    <div class="product-card-main">
                        <h3 class="heading-sm"><?php echo $upsell['title']; ?></h3>
                        <div class="body"><p><?php echo $post_meta[$key]['bold']; ?></p>
                        </div>
                    </div>
                    <div class="product-card-footer">
                        <div class="btn btn-sm btn-stroke">View</div>
                        <div class="product-card-price">$<?php echo $post_meta[$key]['price']; ?></div>
                    </div>
                </div>
            </a>



	    <?php }


    }

}



