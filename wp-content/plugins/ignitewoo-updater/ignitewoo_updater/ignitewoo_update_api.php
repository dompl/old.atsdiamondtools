<?php
/*
Version 1.0.2 - September 8, 2020
	Updated for better styling of plugin messages 
	Updated for better WP Multisite support

Version 1.0.1 - January 5, 2013
 	Changed priority of plugins_api hook

Version 1.0.0 - December, 2013
	Minor reworking of variable values
	Added custom function for plugin row notices
*/

if ( ! function_exists( 'ignitewoo_queue_update' ) ) {
	function ignitewoo_queue_update( $file, $file_id, $product_id ) {
		global $ignitewoo_queued_updates;

		if ( ! isset( $ignitewoo_queued_updates ) ) {
			$ignitewoo_queued_updates = array();
		}

		$plugin             = new stdClass();
		$plugin->file       = $file;
		$plugin->file_id    = $file_id;
		$plugin->product_id = $product_id;
	
		$ignitewoo_queued_updates[] = $plugin;
	}
}
if ( !function_exists( 'ignite_plugin_add_table_css' ) ) { 
	function ignite_plugin_add_table_css( $file ) { 

		?>
		<style>
		
		.plugins tr[data-plugin='<?php echo $file ?>'] th, 
		.plugins tr[data-plugin='<?php echo $file ?>'] td {
			border-bottom: 0;
			box-shadow: none;
		}
		.plugin-update-tr[data-plugin='<?php echo $file ?>'] td {
			border-left: 4px solid #00a0d2;
		}
		</style>
		<?php 
	}
}
if (!function_exists( 'ignite_plugin_update_row')) {
	function ignite_plugin_update_row( $file, $plugin_data ) {

		$this_plugin_base = plugin_basename( $file );

		$msg = get_option( 'plugin_err_' . $this_plugin_base, false );
		
		$wp_list_table = _get_list_table('WP_Plugins_List_Table');
			
		if ( !empty( $msg ) ) { 

			echo '<tr class="plugin-update-tr"><td colspan="' . $wp_list_table->get_column_count() . '" class="plugin-update colspanchange"><div class="update-message notice inline notice-warning notice-alt">';

			ignite_plugin_add_table_css( $file );
			
			echo '<p>' . $msg . '</p>';

			echo '</div></td></tr>';
		}
		
		// This value is an wp_option that stores references to plugins whose key is active on the site: 
		global $ignitewoo_updater_activated;
		
		if ( empty( $ignitewoo_updater_activated ) || !$ignitewoo_updater_activated ) { 
			$ignitewoo_updater_activated = get_option( 'ignitewoo-updater-activated', false );
		}

		$got_it = false;
			
		// check if license key is active: 
		if ( isset( $ignitewoo_updater_activated ) && !empty( $ignitewoo_updater_activated ) && is_array( $ignitewoo_updater_activated ) && count( $ignitewoo_updater_activated ) > 0 )
		foreach( $ignitewoo_updater_activated as $k => $v ) {
			if ( $k == $this_plugin_base ) {
				$got_it = true; 
				break;
			}
		}

		$plugins = array_keys( get_plugins() );
		
		// Is the Updater installed and active on the site? 
		$ignitewoo_updater_installed = false;
		
		//$is_updater_active = false; 
		
		if ( count( $plugins ) ) { 
			foreach( $plugins as $plugin ) { 
				if ( false !== strpos( $plugin, 'ignitewoo-updater.php' ) ) { 
					// $is_updater_active = is_plugin_active( $plugin );
					$ignitewoo_updater_installed = true;
				}
			}
		}
		
		// Updater not installed yet or not active yet? Show that message:
		// If the class doesn't exist then it's not load even if it is installed
		if ( ! class_exists( 'IgniteWoo_Updater' ) ) {

			if ( is_multisite() && 1 == get_current_blog_id() ) {
				$is_network_plugin_page = true;
			}
			
			if ( empty( $is_network_plugin_page ) ) { 
				if ( $ignitewoo_updater_installed ) { 

					$url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=ignitewoo-updater%2Fignitewoo-updater.php' . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_ignitewoo-updater/ignitewoo-updater.php' );

					echo '<tr class="plugin-update-tr" data-plugin="' . $file . '"><td colspan="' . $wp_list_table->get_column_count() . '" class="plugin-update colspanchange"><div class="update-message notice inline notice-error notice-alt">';
									
					ignite_plugin_add_table_css( $file );
					
					echo '<p>' . __( 'Updates are not available for this plugin.', 'ignitewoo' );
					
					echo ' ' . __( sprintf( '<a href="%s">Activate the IgniteWoo Updater</a> to receive updates and support', $url ), 'ignitewoo' );

					echo '</p></div></td></tr>';
				}
				
			} else { 
				return;
			}
			
			if ( $ignitewoo_updater_installed ) { 
				return;
			}
				
			$slug = 'ignitewoo-updater';
			
			$install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $slug ), 'install-plugin_' . $slug );
			
			echo '<tr class="plugin-update-tr" data-plugin="' . $file . '"><td colspan="' . $wp_list_table->get_column_count() . '" class="plugin-update colspanchange"><div class="update-message notice inline notice-warning notice-alt"><p>';

			ignite_plugin_add_table_css( $file );

			_e( sprintf( '<a href="%s">Install the IgniteWoo Updater</a> so that you can activate your license key to receive updates and support', $install_url ), 'ignitewoo' );

			echo '</p></div></td></tr>';
			
		} else if ( empty( $ignitewoo_updater_installed ) || !$ignitewoo_updater_installed  ) {
		
			$activate_url = 'plugins.php?action=activate&plugin=' . urlencode( 'ignitewoo-updater/ignitewoo-updater.php' ) . '&plugin_status=all&paged=1&s&_wpnonce=' . urlencode( wp_create_nonce( 'activate-plugin_ignitewoo-updater/ignitewoo-updater.php' ) );
			
			echo '<tr class="plugin-update-tr" data-plugin="' . $file . '"><td colspan="' . $wp_list_table->get_column_count() . '" class="plugin-update colspanchange"><div class="update-message notice inline notice-warning notice-alt"><p>';

			ignite_plugin_add_table_css( $file );

			_e( sprintf( '<a href="%s">Automatic update is not available for this plugin. Install and activate the IgniteWoo Updater plugin.', esc_url( admin_url( $activate_url ) ), 'ignitewoo' ) );

			echo '</p></div></td></tr>';
		
		} else if ( !$got_it ) {
		
			echo '<tr class="plugin-update-tr" data-plugin="' . $file . '"><td colspan="' . $wp_list_table->get_column_count() . '" class="plugin-update colspanchange"><div class="update-message notice inline notice-warning notice-alt"><p>';
		
			ignite_plugin_add_table_css( $file );
				
			echo '<span>' . __( 'There may be a new version of this plugin, but plugin updates and support are unavailable.', 'ignitewoo' ) . '</span>';
			
			echo ' <span style="color:#cf0000"> ';

			_e( sprintf( '<a href="%s" style="color:#cf0000">Activate or renew your license key</a> to receive updates and support.', admin_url('index.php?page=ignitewoo-licenses') ), 'ignitewoo' );

			echo '</span></p></div></td></tr>';
		}
	}
}
/**
 * Load installer for the IgniteWoo Updater.
 * @return $api Object
 */
if ( ! class_exists( 'IgniteWoo_Updater' ) && ! function_exists( 'ignitewoo_updater_install' ) ) {
	function ignitewoo_updater_install( $api, $action, $args ) {
	
		$download_url = 'https://ignitewoo.com/api/ignitewoo-updater.zip';

		if ( 'plugin_information' != $action ||
			false !== $api ||
			! isset( $args->slug ) ||
			'ignitewoo-updater' != $args->slug
		) { 
			return $api;
		}

		$api = new stdClass();
		@$api->name = 'IgniteWoo Updater';
		@$api->version = '1.0.0';
		@$api->download_link = esc_url( $download_url );
		
		return $api;
	}

	add_filter( 'plugins_api', 'ignitewoo_updater_install', 9999, 3 );
}

/**
 * Updater Installation Prompts
 */
if ( ! class_exists( 'IgniteWoo_Updater' ) && ! function_exists( 'ignitewoo_updater_notice' ) ) {

	/**
	 * Display a notice if the "IgniteWoo Updater" plugin hasn't been installed.
	 * @return void
	 */
	function ignitewoo_updater_notice() {
	
		$active_plugins = apply_filters( 'active_plugins', get_option('active_plugins' ) );
		
		if ( in_array( 'ignitewoo-updater/ignitewoo-updater.php', $active_plugins ) )
			return;

		$slug = 'ignitewoo-updater';
		
		$install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $slug ), 'install-plugin_' . $slug );
		
		$activate_url = 'plugins.php?action=activate&plugin=' . urlencode( 'ignitewoo-updater/ignitewoo-updater.php' ) . '&plugin_status=all&paged=1&s&_wpnonce=' . urlencode( wp_create_nonce( 'activate-plugin_ignitewoo-updater/ignitewoo-updater.php' ) );

		$message = '<a href="' . esc_url( $install_url ) . '">Install the IgniteWoo Updater plugin</a> to get updates and support for your IgniteWoo plugins.';
		
		$is_downloaded = false;
		
		$plugins = array_keys( get_plugins() );

		foreach ( $plugins as $plugin ) {
			if ( strpos( $plugin, 'ignitewoo-updater.php' ) !== false ) {
				$is_downloaded = true;
				$message = '<a href="' . esc_url( admin_url( $activate_url ) ) . '">Activate the IgniteWoo Updater plugin</a> to get updates and support for your IgniteWoo plugins.';
			}
		}

		echo '<div class="updated fade"><p>' . $message . '</p></div>' . "\n";
	}

	add_action( 'admin_notices', 'ignitewoo_updater_notice' );
}

/**
 * Prevent conflicts with older versions
 */
if ( ! class_exists( 'IgniteWoo_Plugin_Updater' ) ) {
	class IgniteWoo_Plugin_Updater { 
		function init() {
			// do nothing, just a placeholder.
		} 
	}
}
