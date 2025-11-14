<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once 'wpeliteplugins-updater-functions.php';

if ( ! class_exists( 'WPElitePlugins_Updater' ) ) {
	class WPElitePlugins_Updater {

		public $plugin_base    = '';
		public $plugin_slug    = '';
		public $plugin_name    = '';
		public $plugin_version = '';
		public $update_url     = 'https://wpeliteplugins.com/Updates/license_api.php';
		public $purchase_code  = '';
		public $email          = '';
		public $item_id        = '';

		/**
		 * Constructor
		 */
		public function __construct( $args ) {

			// Initialize plugins information
			$this->plugin_base    = $args['plugin_base'];
			$this->plugin_slug    = $args['plugin_slug'];
			$this->plugin_name    = $args['plugin_name'];
			$this->plugin_version = $args['plugin_version'];
			$this->item_id        = $args['item_id'];

			$this->purchase_code = wpeliteplugins_get_plugins_purchase_code( $this->plugin_slug );
			$this->email         = wpeliteplugins_get_plugins_purchase_email( $this->plugin_slug );

			// Plugin updates
			wpeliteplugins_queue_update( $this->plugin_base, $this );

			// Manual check for updates
			add_action( 'admin_init', array( $this, 'check_if_forced_for_update' ) );
			add_action( 'all_admin_notices', array( $this, 'display_manual_check_result' ) );

			// Plugin Information for the Popup
			add_filter( 'plugins_api', array( $this, 'inject_info' ), 20, 3 );

			// Push the Update Information into WP Transients
			add_filter( 'site_transient_update_plugins', array( $this, 'inject_update' ) );

			// Cache the results to make it awesomely fast
			add_action( 'upgrader_process_complete', array( $this, 'after_update' ), 10, 2 );

			// Add check for update link in plugin row
			add_filter( 'plugin_row_meta', array( $this, 'check_for_update' ), 10, 2 );

			// Add configure your license to get automatic updates message
			add_action( 'in_plugin_update_message-' . $this->plugin_base, array( $this, 'configure_your_license' ), 10, 2 );

		}

		/**
		 * Show informaton on click of view details
		 */
		public function inject_info( $res, $action, $args ) {

			// do nothing if this is not about getting plugin information
			if ( 'plugin_information' !== $action ) {
				return $res;
			}

			// do nothing if it is not our plugin
			if ( $this->plugin_slug !== $args->slug ) {
				return $res;
			}

			$remote = $this->get_transient();

			if ( $remote ) {

				$res = new \stdClass();

				$res->name           = $remote->name;
				$res->slug           = $this->plugin_slug;
				$res->version        = $remote->version;
				$res->tested         = $remote->tested;
				$res->requires       = $remote->requires;
				$res->author         = '<a href="https://www.wpeliteplugins.com">WPElitePlugins</a>';
				$res->author_profile = 'https://www.wpeliteplugins.com';
				$res->download_link  = $remote->download_url;
				$res->trunk          = $remote->download_url;
				$res->last_updated   = $remote->last_updated;

				$res->sections = array(
					'description'  => $remote->sections->description,
					'installation' => $remote->sections->installation,
					'changelog'    => $remote->sections->changelog,
				);

				// in case you want the screenshots tab, use the following HTML format for its content:
				// <ol><li><a href="IMG_URL" target="_blank"><img src="IMG_URL" alt="CAPTION" /></a><p>CAPTION</p></li></ol>
				if ( ! empty( $remote->sections->screenshots ) ) {
					$res->sections['screenshots'] = $remote->sections->screenshots;
				}

				if ( isset( $remote->banners ) && ! empty( $remote->banners ) ) {
					$low          = isset( $remote->banners->low ) ? $remote->banners->low : '';
					$high         = isset( $remote->banners->high ) ? $remote->banners->high : '';
					$res->banners = array(
						'low'  => $low,
						'high' => $high,
					);
				}
			}

			return $res;

		}

		/**
		 * Site transient update plugins
		 */
		public function inject_update( $transient ) {

			if ( empty( $transient->checked ) ) {
				return $transient;
			}

			$remote = $this->get_transient();

			if ( $remote &&
				version_compare( $this->plugin_version, $remote->version, '<' ) &&
				version_compare( $remote->requires, get_bloginfo( 'version' ), '<' )
			) {

				$res                                 = new \stdClass();
				$res->slug                           = $this->plugin_slug;
				$res->plugin                         = $this->plugin_base;
				$res->new_version                    = $remote->version;
				$res->tested                         = $remote->tested;
				$res->package                        = $remote->download_url;
				$transient->response[ $res->plugin ] = $res;
			}

			return $transient;
		}

		/**
		 * Clean transient after update
		 */
		public function after_update( $upgrader_object, $options ) {

			if ( $options['action'] == 'update' && $options['type'] === 'plugin' ) {
				// just clean the cache when new plugin version is installed
				delete_transient( 'wpeliteplugins_update_' . $this->plugin_slug );
			}
		}

		/**
		 * Manually check for updates
		 */
		public function check_for_update( $links, $file ) {
			if ( strpos( $file, $this->plugin_base ) !== false ) {
				$link_url                 = wp_nonce_url(
					add_query_arg(
						array(
							'wpeliteplugins_check_for_update' => 1,
							'plugin' => $this->plugin_slug,
						),
						is_network_admin() ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' )
					),
					'wpeliteplugins_check_for_update'
				);
				$link_text                = 'Check for updates';
				$check_for_updates_link[] = sprintf( '<a href="%s">%s</a>', esc_attr( $link_url ), $link_text );
				$links                    = array_merge( $links, $check_for_updates_link );
			}
			return $links;
		}

		/**
		 * Get data from transient. If not available then only call API
		 */
		private function get_transient() {

			// trying to get from cache first
			$remote = get_transient( 'wpeliteplugins_update_' . $this->plugin_slug );

			if ( ! $remote ) {

				// call to the server and get plugin information if license key is valid
				$result = wp_remote_get(
					add_query_arg(
						array(
							'act'    => 'check_update',
							'domain' => network_site_url(),
							'lickey' => $this->purchase_code,
							'itemid' => $this->item_id,
							'email'  => $this->email,
						),
						$this->update_url
					),
					array(
						'timeout' => 10,
						'headers' => array(
							'Accept' => 'application/json',
						),
					)
				);

				if ( ! is_wp_error( $result ) && isset( $result['response']['code'] ) && $result['response']['code'] == 200 && ! empty( $result['body'] ) ) {
					$remote = json_decode( stripslashes( $result['body'] ) );
					set_transient( 'wpeliteplugins_update_' . $this->plugin_slug, $remote, 43200 ); // 12 hours cache
				} else {
					return false;
				}
			}

			return $remote;
		}

		/**
		 * Manually check for updates
		 */
		private function check_for_updates() {
			$remote = $this->get_transient();

			if ( $remote &&
				version_compare( $this->plugin_version, $remote->version, '<' ) &&
				version_compare( $remote->requires, get_bloginfo( 'version' ), '<' )
			) {
				return 'update_available';
			} elseif ( $remote &&
				version_compare( $this->plugin_version, $remote->version, '=' ) ) {
					return 'no_update';
			} else {
				return 'error';
			}
		}

		/**
		 * Manually check for updates
		 */
		public function check_if_forced_for_update() {
			if (
				isset( $_GET['wpeliteplugins_check_for_update'] ) && $_GET['wpeliteplugins_check_for_update'] == '1'
				&& isset( $_GET['plugin'] ) && is_string( $_GET['plugin'] ) && $_GET['plugin'] == $this->plugin_slug
				&& current_user_can( 'update_plugins' )
				&& check_admin_referer( 'wpeliteplugins_check_for_update' )
			) {
				delete_transient( 'wpeliteplugins_update_' . $this->plugin_slug );
				$update = $this->check_for_updates();
				wp_redirect(
					add_query_arg(
						array(
							'wpeliteplugins_update_check_result' => $update,
							'wpeliteplugins_slug' => $this->plugin_slug,
						),
						is_network_admin() ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' )
					)
				);
			}
		}

		/**
		 * Display message after manual check
		 */
		public function display_manual_check_result() {
			if ( isset( $_GET['wpeliteplugins_update_check_result'], $_GET['wpeliteplugins_slug'] ) && ( $_GET['wpeliteplugins_slug'] == $this->plugin_slug ) ) {
				$status = strval( $_GET['wpeliteplugins_update_check_result'] );
				if ( $status == 'no_update' ) {
					$message = 'This plugin is up to date.';
				} elseif ( $status == 'update_available' ) {
					$message = 'A new version of this plugin is available.';
				} else {
					$message = sprintf( 'Unknown update checker status "%s"', htmlentities( $status ) );
				}
				printf(
					'<div class="updated"><p>%s</p></div>',
					$message
				);
			}
		}

		/**
		 * Display message for automatic updates on plugin page
		 */
		public function configure_your_license( $plugin_info_array, $plugin_info_object ) {
			if ( empty( $plugin_info_array['package'] ) ) {
				$updater_page = add_query_arg(
					array(
						'page' => 'wpeliteplugins-upd-helper',
					),
					admin_url( 'index.php' )
				);
				echo ' <em><a href="' . $updater_page . '">Please enter a valid license to get Automatic Updates</a></em>';
			}
		}
	}
}
