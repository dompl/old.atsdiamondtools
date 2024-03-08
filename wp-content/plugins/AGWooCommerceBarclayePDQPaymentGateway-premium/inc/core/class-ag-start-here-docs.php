<?php
/*-----------------------------------------------------------------------------------*/
/*	AG Get doc urls
/*-----------------------------------------------------------------------------------*/
defined( 'ABSPATH' ) || die( "No script kiddies please!" );


if ( class_exists( 'AG_start_here_docs' ) ) {
	return;
}


class AG_start_here_docs {

    
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
    
    /**
	 * Doc url
	 * @var string
	 */
    public static $AG_doc_url = 'https://weareag.co.uk/docs/';
    

    public static function get_doc_links() {

		if (!class_exists('AG_ePDQ_Helpers')) {
            return;
		}

	    $return = array();
	    $url = self::$AG_doc_url . self::$args['start_here'];
	    $transient_name = self::$args['plugin_slug'] . '_get_doc_links_new_doc';

	    if (!get_transient($transient_name) !== null) {

		    $dom = new DOMDocument();
		    libxml_use_internal_errors(true);

		    $html = wp_remote_retrieve_body(wp_safe_remote_get(esc_url($url)));
		    $dom->loadHTML($html);
		    $docs = $dom->getElementsByTagName('div');

		    foreach ($docs as $doc) {
			    $classes = $doc->getAttribute('class');

			    if (strpos($classes, 'docs-category-list') === false) {
				    continue;
			    }

			    $links = $doc->getElementsByTagName('a');


			    foreach ($links as $link) {
					$title = htmlspecialchars($link->nodeValue, ENT_QUOTES);
				    $return[] = array(
					    'href' => AG_ePDQ_Helpers::AG_escape($link->getAttribute('href')),
					    'title' => AG_ePDQ_Helpers::AG_escape($title),
				    );
			    }


		    }

	    set_transient($transient_name, $return, 30 * DAY_IN_SECONDS);

	    }

	    return $return;

	}


	public static function output_doc_links() {
		
		if (!class_exists('AG_ePDQ_Helpers')) {
            return;
        }

		$transient_name = self::$args['plugin_slug'] . '_get_doc_links_new_doc';
		$data = get_transient($transient_name);
		if (empty($data)) {
			$data = self::get_doc_links();
		} ?>

        <ol>
			<?php foreach ($data as $link) { ?>
				<li>
					<a href="<?php echo esc_attr( AG_ePDQ_Helpers::AG_decode( $link['href'] ) ); ?>?utm_source=<?php echo self::$args['plugin_slug']; ?>&utm_medium=insideplugin" target="_blank"><?php echo AG_ePDQ_Helpers::AG_decode( $link['title'] ); ?></a>
				</li>
			<?php } ?>
		</ol>

        <p><strong>Still having problems?</strong> Have a look at our <a href="<?php echo self::$AG_doc_url . self::$args['troubleshooting']; ?>" target="_blank">troubleshooting</a> documentation.<br />There is a permanent link to the plugin documentation below.</p>

		<p>Want to know more about other Payment options or PCI compliance? have a look at our tips and information section below.</p>

	<?php }





}