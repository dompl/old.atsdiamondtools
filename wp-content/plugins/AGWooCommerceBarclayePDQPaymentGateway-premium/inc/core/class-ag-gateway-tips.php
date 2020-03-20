<?php
/*-----------------------------------------------------------------------------------*/
/*	AG Gateway tips
/*-----------------------------------------------------------------------------------*/
defined( 'ABSPATH' ) or die( "No script kiddies please!" );


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

        $transient_name = 'AG_post';
        $site_posts = array();
        $post_got = array();

        $posts_got = get_transient( $transient_name );
        //wp_die(var_dump($posts_got));


        if( isset($posts_got)) {


            $json = file_get_contents('https://weareag.co.uk/wp-json/wp/v2/posts?per_page=4&_embed');
            $posts = json_decode($json, true);
            foreach($posts as $post) {
            
                $site_posts[] = array(
                    'title' =>  $post['title']['rendered'],
                    'tip_url' => $post['link'],
                    'dec' => $post['excerpt']['rendered'],
                    'tip_img' => $post['_embedded']['wp:featuredmedia'][0]['source_url']
                );

            }
            set_transient( $transient_name, $site_posts, 12 * HOUR_IN_SECONDS );

            return $site_posts;

       }

    }


    public static function output_tips() {
        
        $data = self::pull_AG_posts();
        if ( empty( $data ) ) {
			return;
        }
        
        $transient_name = 'AG_post';
        $new_tips = get_transient( $transient_name );


        foreach ( $new_tips as $tip ) { ?>

        <div class="tip-card">
            <div class="card-contents">
                <div class="card-header">
                    <a href="<?php echo $tip['tip_url']; ?>?utm_source=<?php echo self::$args['plugin_slug']; ?>&utm_medium=plugin_tips" target="_blank">
                        <img class="plugin-logo" src="<?php echo $tip['tip_img']; ?>">
                        <div class="ag-watermark">
                            <img src="https://weareag.co.uk/wp/wp-content/themes/AGv5/img/ag-logo.svg">
                        </div>
                    </a>
                </div>
                <div class="card-body">
                    <h3><?php echo $tip['title']; ?></h3>
                    <?php echo $tip['dec']; ?>
                    <a href="<?php echo $tip['tip_url']; ?>?utm_source=<?php echo self::$args['plugin_slug']; ?>&utm_medium=plugin_tips" target="_blank" class="ag-button">Find out more</a>
                </div>
            </div>
        </div>

    <?php } 

	}
}