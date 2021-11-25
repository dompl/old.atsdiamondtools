<?php
/*-----------------------------------------------------------------------------------*/
/*	AG Gateway tips
/*-----------------------------------------------------------------------------------*/
defined( 'ABSPATH' ) || die( "No script kiddies please!" );


if ( class_exists( 'AG_gateway_tips' ) ) {
	return;
}


class AG_gateway_tips {

    public static $instance = null;
    public static $args = array();
    
    /**
	 * run
	 */
	public static function run_instance( $args = array() ) {
		if ( self::$instance === null ) {
			self::$args            = $args;
			self::$instance = new self();
		}

		return self::$instance;
    }

    public static function pull_AG_posts() {

        if (!class_exists('AG_ePDQ_Helpers')) {
            return;
        }

        $transient_name = self::$args['plugin_slug'] .'_AG_post';
        $site_posts = array();
        $post_got = array();
        $posts_got = get_transient( $transient_name );

        if( empty($posts_got)) {

            $response = wp_safe_remote_get( 'https://weareag.co.uk/wp-json/wp/v2/posts?per_page=4&_embed');
             
            if( !is_wp_error( $response ) && $response['response']['code'] === 200 ) {
             
                $posts = json_decode( wp_remote_retrieve_body( $response ) ); 
                foreach( $posts as $post ) {

                    $site_posts[] = array(
                        'title' =>  AG_ePDQ_Helpers::AG_escape( $post->title->rendered ),
                        'tip_url' => AG_ePDQ_Helpers::AG_escape( $post->link ),
                        'dec' => AG_ePDQ_Helpers::AG_escape( $post->excerpt->rendered ),
                        //'tip_img' => AG_ePDQ_Helpers::AG_escape( $post->_embedded->{'wp:featuredmedia'}[0]->source_url )
                    );
             
                }
                set_transient( $transient_name, $site_posts, 12 * HOUR_IN_SECONDS );

            }

       }

       return $site_posts;

    }



    public static function output_tips() {
        
        if (!class_exists('AG_ePDQ_Helpers')) {
            return;
        }

        $data = self::pull_AG_posts();
        $transient_name = self::$args['plugin_slug'] .'_AG_post';
        $new_tips = get_transient( $transient_name );

        foreach ( $new_tips as $tip ) { ?>

        <div class="tip-card">
            <div class="card-contents">
                <div class="card-body">
                    <h3><?php echo AG_ePDQ_Helpers::AG_decode( $tip['title'] ); ?></h3>
                    <?php echo AG_ePDQ_Helpers::AG_decode( $tip['dec'] ); ?>
                    <a href="<?php echo AG_ePDQ_Helpers::AG_decode( $tip['tip_url'] ); ?>?utm_source=<?php echo self::$args['plugin_slug']; ?>&utm_medium=plugin_tips" target="_blank" class="ag-button">Find out more</a>
                </div>
            </div>
        </div>

    <?php }

    }
    

}

