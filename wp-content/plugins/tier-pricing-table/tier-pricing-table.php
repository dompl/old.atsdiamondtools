<?php
	/**
	 * Plugin Name:       Tiered Price Table for WooCommerce
	 * Description:       Quantity-based discounts with nice-looking reflection on the frontend.
	 * Version:           8.4.0
	 * Plugin URI:        https://u2code.com/plugins/tiered-pricing-table-for-woocommerce/
	 * Author:            U2Code
	 * Author URI:        https://u2code.com
	 * License:           GNU General Public License v3.0
	 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
	 * Text Domain:       tier-pricing-table
	 * Domain Path:       /languages/
	 *
	 * WC requires at least: 7.0
	 * WC tested up to: 10.4
	 *
	 * Requires at least: 5.0
	 * Requires PHP: 7.2
	 * Tested up to: 6.8
 * Woo: 4688341:4df6277d69a5a71a9489359f4adca64a

	 */

	use TierPricingTable\TierPricingTablePlugin;

	// If this file is called directly, abort.
	if ( ! defined( 'WPINC' ) ) {
		die;
	}

	if ( version_compare( phpversion(), '7.2.0', '<' ) ) {

		add_action( 'admin_notices', function () {
			?>
			<div class='notice notice-error'>
				<p>
					Tiered Pricing Table plugin requires PHP version to be <b>7.2 or higher</b>. You run PHP
					version <?php echo esc_attr( phpversion() ); ?>
				</p>
			</div>
			<?php
		} );

		return;
	}

	call_user_func( function () {

		require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

		$plugin = new TierPricingTablePlugin( __FILE__ );

		if ( $plugin->checkRequirements() ) {

			register_activation_hook( __FILE__, array( $plugin, 'activate' ) );

			add_action( 'uninstall', array( TierPricingTablePlugin::class, 'uninstall' ) );

			$plugin->run();
		}
	} );

define('TIERED_PRICING_PRODUCTION', true);
