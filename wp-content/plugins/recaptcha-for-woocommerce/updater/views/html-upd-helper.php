<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPElitePlugins Helper Page
 *
 * Handle to display helper page
 *
 * @package wpeliteplugins Updater
 * @since 1.0.0
 */

global $wpeliteplugins_queued_updates;

$count = 0;
if ( ! empty( $wpeliteplugins_queued_updates ) ) {
	$count = count( $wpeliteplugins_queued_updates );
}

?>
<div class="wrap">
	<h2><?php echo __( 'WPElitePlugins Updater', 'woo_min_max_quantities' ); ?></h2>
	<?php

		if ( isset( $_GET['message'] ) && ! empty( $_GET['message'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Licence key has been updated successfully.', 'woo_min_max_quantities' ) . '</p></div>' . "\n";

			// clear transient after save so that newly updates can be checked.
			foreach ( $wpeliteplugins_queued_updates as $wpeliteplugins_queue ) {
				delete_transient( "wpeliteplugins_update_{$wpeliteplugins_queue->plugin_slug}" );
			}
		}

		echo '<div class="notice notice-success is-dismissible">' . wpautop( __( 'Add plugin Purchase Code to get Automatic updates', 'woo_min_max_quantities' ) ) . '</div>' . "\n";
		?>
	<form action="" method="post" id="wpelitepluginsupd-conf" enctype="multipart/form-data">
		<div class="tablenav top">
			<div class="tablenav-pages one-page"><span class="displaying-num"><?php echo $count . ' ' . __( 'item', 'woo_min_max_quantities' ); ?></span></div>
		</div>
		<table class="wp-list-table widefat fixed wpeliteplugins-licenses">
			<thead>
				<tr>					
					<th width="20%"><?php echo __( 'Product', 'woo_min_max_quantities' ); ?></th>
					<th width="10%"><?php echo __( 'Version', 'woo_min_max_quantities' ); ?></th>
					<th width="35%"><?php echo __( 'Email', 'woo_min_max_quantities' ); ?></th>
					<th width="35%"><?php echo __( 'Item Purchase Code', 'woo_min_max_quantities' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			if ( ! empty( $wpeliteplugins_queued_updates ) ) {
				$purchase_codes = wpeliteplugins_get_plugins_purchase_code();
				$emails         = wpeliteplugins_get_plugins_purchase_email();
				$counter        = 1;

				foreach ( $wpeliteplugins_queued_updates as $wpeliteplugins_queue ) {
					$plugin_key = $wpeliteplugins_queue->plugin_slug;
					$alternate  = ( $counter % 2 == 1 ) ? 'alternate' : '';
					$licence    = isset( $purchase_codes[ $plugin_key ] ) ? $purchase_codes[ $plugin_key ] : '';
					$email      = isset( $emails[ $plugin_key ] ) ? $emails[ $plugin_key ] : '';
					?>
						<tr class="<?php echo $alternate; ?>">
							<td><strong><?php echo $wpeliteplugins_queue->plugin_name; ?></strong></td>
							<td><?php echo $wpeliteplugins_queue->plugin_version; ?></td>
							<td>
								<input class="wpelitepluginsupd-email-field" size="40" type="text" value="<?php echo $email; ?>" name="wpelitepluginsupd_email[<?php echo $plugin_key; ?>]" placeholder="<?php echo __( 'Enter email', 'woo_min_max_quantities' ); ?>" />
							</td>
							<td>
								<input class="wpelitepluginsupd-key-field" size="40" type="text" value="<?php echo $licence; ?>" name="wpelitepluginsupd_lickey[<?php echo $plugin_key; ?>]" placeholder="<?php echo __( 'Enter item purchase code', 'woo_min_max_quantities' ); ?>" />
							</td>
						</tr>
						<?php

						$counter++;
				}
			} else {
				?>
					<tr><td colspan="3"><?php echo __( 'There is no product available for update.', 'woo_min_max_quantities' ); ?></td></tr>
				<?php
			}
			?>
			</tbody>
			<tfoot>
				<tr>
					<th><?php echo __( 'Product', 'woo_min_max_quantities' ); ?></th>
					<th><?php echo __( 'Version', 'woo_min_max_quantities' ); ?></th>
					<th><?php echo __( 'Email', 'woo_min_max_quantities' ); ?></th>							
					<th><?php echo __( 'Item Purchase Code', 'woo_min_max_quantities' ); ?></th>
				</tr>
			</tfoot>
		</table>
		<div class="tablenav bottom">
			<div class="tablenav-pages one-page"><span class="displaying-num"><?php echo $count . ' ' . __( 'item', 'woo_min_max_quantities' ); ?></span></div>
		</div>
		<?php

		if ( ! empty( $wpeliteplugins_queued_updates ) ) {
			?>
			<p class="submit">
				<input id="submit" class="button button-primary wpeliteplugins-upd-submit-button" type="submit" value="<?php echo __( 'Activate Products', 'woo_min_max_quantities' ); ?>" name="wpeliteplugins_upd_submit">
			</p>
			<?php
		}
		?>
	</form>
</div><!-- .wrap -->
