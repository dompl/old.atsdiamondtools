<?php // ==== CONFIGURATION (DEFAULT) ==== //
/* Remove direct access */
if (!defined('ABSPATH'))
{
  exit; // Exit if accessed directly
}
// Specify default configuration values here; these may be overwritten by previously defined values in `functions-config.php`

/*  ********************************************************
 *   Remove stuff from hader
 *  ********************************************************
 */

// Remove Header JSON
defined( 'VOIDX_REMOVE_HEADER_JSON' ) || define( 'VOIDX_REMOVE_HEADER_JSON', true );

// Remove Wordpress Emojis
defined( 'VOIDX_REMOVE_EMOJIS' ) || define( 'VOIDX_REMOVE_EMOJIS', true );

// Remove Weblog Client Link
defined( 'VOIDX_REMOVE_WEBLOG_CLIENT_LINK' ) || define( 'VOIDX_REMOVE_WEBLOG_CLIENT_LINK', true );

// Remove  Windows Live Writer Manifest Link
defined( 'VOIDX_REMOVE_WIN_MANIFEST' ) || define( 'VOIDX_REMOVE_WIN_MANIFEST', true );

// Remove WordPress Page/Post Shortlinks
defined( 'VOIDX_REMOVE_POST_SHORTLINK' ) || define( 'VOIDX_REMOVE_POST_SHORTLINK', true );

// Remove WordPress generator
defined( 'VOIDX_REMOVE_WP_GENERATOR' ) || define( 'VOIDX_REMOVE_WP_GENERATOR', true );

// Disable RSS Feed from header
defined( 'VOIDX_REMOVE_RSS_FEED' ) || define( 'VOIDX_REMOVE_RSS_FEED', true );

// Move jQuery to Footer
defined( 'VOIDX_MOVE_JQUERY_TO_FOOTER' ) || define( 'VOIDX_MOVE_JQUERY_TO_FOOTER', false );

// Remove WP-Embed from footer
defined( 'VOIDX_REMOVE_WP_EMBED' ) || define( 'VOIDX_REMOVE_WP_EMBED', true );

// Text domain
defined( 'TEXT_DOMAIN' ) || define( 'TEXT_DOMAIN', 'atsdiamondtools' );


/*  ********************************************************
 *   Customise WordPress
 *  ********************************************************
 */

// Activate custom login screen
defined( 'VOIDX_CUSTOMISE_WP_LOGIN_SCREEN' ) || define( 'VOIDX_CUSTOMISE_WP_LOGIN_SCREEN', true );

// Add re-captach to login form
defined( 'VOIDX_ADD_RECAPTCHA_ON_LOGIN' ) || define( 'VOIDX_ADD_RECAPTCHA_ON_LOGIN', false );
