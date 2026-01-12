<?php namespace TierPricingTable\Addons\CustomColumns\Form;

use TierPricingTable\Addons\CustomColumns\CustomColumnsAddon;
use TierPricingTable\Addons\CustomColumns\Schema;
use TierPricingTable\Core\AdminNotifier;
use TierPricingTable\Core\ServiceContainerTrait;

class ColumnsForm {
	
	use ServiceContainerTrait;
	
	const ADD_NEW_ACTION = 'tiered_pricing_add_new_custom_column';
	const UPDATE_ACTION = 'tiered_pricing_update_custom_column';
	const REMOVE_COLUMN_ACTION = 'tiered_pricing_remove_custom_column';
	const REMOVE_ALL_CUSTOM_COLUMNS = 'tiered_pricing_remove_all_custom_columns';
	
	protected $addon;
	
	public function __construct( CustomColumnsAddon $addon ) {
		
		add_action( 'admin_post_' . self::ADD_NEW_ACTION, array( $this, 'handleAddNew' ) );
		add_action( 'admin_post_' . self::UPDATE_ACTION, array( $this, 'handleUpdate' ) );
		add_action( 'admin_post_' . self::REMOVE_COLUMN_ACTION, array( $this, 'handleRemove' ) );
		add_action( 'admin_post_' . self::REMOVE_ALL_CUSTOM_COLUMNS, array( $this, 'handleRemoveAll' ) );
		
		add_action( 'tiered_pricing_table/settings/table_columns/end', array( $this, 'renderColumnsForm' ) );
		add_action( 'tiered_pricing_table/settings/table_columns/after_fields', array( $this, 'renderButton' ) );
		
		$this->addon = $addon;
	}
	
	public function handleRemoveAll(): bool {
		
		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( $_GET['nonce'] ) : false;
		
		if ( ! wp_verify_nonce( $nonce, self::REMOVE_ALL_CUSTOM_COLUMNS ) ) {
			$this->getContainer()->getAdminNotifier()->flash( __( 'Invalid nonce, please try again',
				'tier-pricing-table' ), AdminNotifier::ERROR );
			
			return wp_safe_redirect( wp_get_referer() );
		}
		
		foreach ( $this->addon->columnsManager->getColumns() as $column ) {
			$column->remove();
		}
		
		$this->addon->columnsManager->updateRawColumns( array() );
		
		$this->getContainer()->getAdminNotifier()->flash( __( 'Columns were deleted successfully.',
			'tier-pricing-table' ) );
		
		return wp_safe_redirect( wp_get_referer() );
	}
	
	public function handleRemove(): bool {
		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( $_GET['nonce'] ) : false;
		
		if ( ! wp_verify_nonce( $nonce, self::REMOVE_COLUMN_ACTION ) ) {
			$this->getContainer()->getAdminNotifier()->flash( __( 'Invalid nonce, please try again',
				'tier-pricing-table' ), AdminNotifier::ERROR );
			
			return wp_safe_redirect( wp_get_referer() );
		}
		
		$slug = ! empty( $_GET['slug'] ) ? sanitize_text_field( $_GET['slug'] ) : false;
		
		if ( ! $slug ) {
			$this->getContainer()->getAdminNotifier()->flash( __( 'Invalid column.', 'tier-pricing-table' ),
				AdminNotifier::ERROR );
		} else {
			
			if ( $this->addon->columnsManager->removeColumn( $slug ) ) {
				
				$this->getContainer()->getAdminNotifier()->flash( __( 'Column removed successfully.',
					'tier-pricing-table' ) );
			} else {
				$this->getContainer()->getAdminNotifier()->flash( __( 'Something went wrong. Please try again.',
					'tier-pricing-table' ), AdminNotifier::ERROR );
			}
		}
		
		return wp_safe_redirect( wp_get_referer() );
	}
	
	public function handleAddNew(): bool {
		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( $_GET['nonce'] ) : false;
		
		if ( ! wp_verify_nonce( $nonce, self::ADD_NEW_ACTION ) ) {
			$this->getContainer()->getAdminNotifier()->flash( __( 'Invalid nonce, please try again',
				'tier-pricing-table' ), AdminNotifier::ERROR );
			
			return wp_safe_redirect( wp_get_referer() );
		}
		
		$name     = ! empty( $_GET['name'] ) ? sanitize_text_field( $_GET['name'] ) : false;
		$type     = ! empty( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : false;
		$dataType = ! empty  ( $_GET['data_type'] ) ? sanitize_text_field( $_GET['data_type'] ) : false;
		
		$slug = strtolower( wp_generate_password( 10, false ) );
		
		$columnInstance = $this->addon->columnsManager->getColumnInstance( $slug, array(
			'name'      => $name,
			'type'      => $type,
			'data_type' => $dataType,
		) );
		
		try {
			if ( $columnInstance && $columnInstance->save() ) {
				$this->getContainer()->getAdminNotifier()->flash( __( 'Column added successfully.',
					'tier-pricing-table' ) );
			} else {
				$this->getContainer()->getAdminNotifier()->flash( __( 'Something went wrong. Please try again.',
					'tier-pricing-table' ), AdminNotifier::ERROR );
			}
		} catch ( \Exception $e ) {
			$this->getContainer()->getAdminNotifier()->flash( __( 'Invalid column data.', 'tier-pricing-table' ),
				AdminNotifier::ERROR );
		}
		
		return wp_safe_redirect( wp_get_referer() );
	}
	
	public function handleUpdate(): bool {
		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( $_GET['nonce'] ) : false;
		
		if ( ! wp_verify_nonce( $nonce, self::UPDATE_ACTION ) ) {
			$this->getContainer()->getAdminNotifier()->flash( __( 'Invalid nonce, please try again',
				'tier-pricing-table' ), AdminNotifier::ERROR );
			
			return wp_safe_redirect( wp_get_referer() );
		}
		
		$slug     = ! empty( $_GET['slug'] ) ? sanitize_text_field( $_GET['slug'] ) : false;
		$name     = ! empty( $_GET['name'] ) ? sanitize_text_field( $_GET['name'] ) : false;
		$type     = ! empty( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : false;
		$dataType = ! empty  ( $_GET['data_type'] ) ? sanitize_text_field( $_GET['data_type'] ) : false;
		
		$columnInstance = $this->addon->columnsManager->getColumnInstance( $slug, array(
			'name'      => $name,
			'type'      => $type,
			'data_type' => $dataType,
		) );
		
		try {
			if ( $columnInstance && $columnInstance->save() ) {
				$this->getContainer()->getAdminNotifier()->flash( __( 'Column edited successfully.',
					'tier-pricing-table' ) );
			} else {
				$this->getContainer()->getAdminNotifier()->flash( __( 'Something went wrong. Please try again.',
					'tier-pricing-table' ), AdminNotifier::ERROR );
			}
		} catch ( \Exception $e ) {
			$this->getContainer()->getAdminNotifier()->flash( __( 'Invalid column data.', 'tier-pricing-table' ),
				AdminNotifier::ERROR );
		}
		
		return wp_safe_redirect( wp_get_referer() );
	}
	
	public function renderButton() {
		?>
		
		<?php foreach ( $this->addon->columnsManager->getColumns() as $column ) : ?>
			<div>
				<label for="tiered_pricing_custom_column_<?php echo esc_attr( $column->getSlug() ); ?>">
					<?php echo esc_attr( $column->getName() ); ?>:
				</label>
				<br>
				<input disabled
					   type="text" style="width: 190px;"
					   id="tiered_pricing_custom_column_<?php echo esc_attr( $column->getSlug() ); ?>"
					   placeholder="<?php echo esc_attr( $column->getFormattedType() ); ?>">
			</div>
		<?php endforeach; ?>

		<div>
			<button type="button" class="button button-tpt-button tiered-pricing-custom-columns-show-button">
				<?php esc_html_e( 'Manage custom columns', 'tier-pricing-table' ); ?>
			</button>
		</div>
		
		<?php
	}
	
	public function renderColumnsForm() {
		?>
		<script>
			jQuery(document).ready(function () {
				const CustomColumnsTable = function (container) {

					this.init = function () {
						jQuery('.tiered-pricing-custom-columns-show-button').click(function (e) {
							e.preventDefault();
							container.toggleClass('tiered-pricing-custom-columns-table--hidden');
						});

						jQuery('.tiered-pricing-custom-columns-table__new-column-row button').click((function (e) {

							e.preventDefault();

							const _container = container.find('.tiered-pricing-custom-columns-table__new-column-row');

							const name = _container.find('[name=tiered_pricing_custom_column_name]').val();
							const type = _container.find('[name=tiered_pricing_custom_column_type]').val();
							const dataType = _container.find('[name=tiered_pricing_custom_column_data_type]').val();

							if (!name) {
								alert('Please enter the column name');

								return;
							}

							this.request(this.getAddNewURL(), {
								'name': name,
								'type': type,
								'data_type': dataType
							});

						}).bind(this));

						jQuery('.tiered-pricing-custom-columns-table__edit-button').click((function (e) {

							e.preventDefault();

							const _container = jQuery(e.target).closest('tr');

							const slug = _container.data('slug');
							const name = _container.find('[name=tiered_pricing_custom_column_name]').val();
							const type = _container.find('[name=tiered_pricing_custom_column_type]').val();
							const dataType = _container.find('[name=tiered_pricing_custom_column_data_type]').val();

							if (!name) {
								alert('Please enter the column name');

								return;
							}

							this.request(this.getUpdateURL(), {
								'slug': slug,
								'name': name,
								'type': type,
								'data_type': dataType
							});
						}).bind(this));

						jQuery('[name=tiered_pricing_custom_column_type]').change(function () {

							const container = jQuery(this).closest('tr');

							const val = jQuery(this).val();

							if (val !== 'custom') {
								container.find('[name=tiered_pricing_custom_column_data_type]').parent().hide();
							} else {
								container.find('[name=tiered_pricing_custom_column_data_type]').parent().show();
							}
						}).trigger('change');
					}

					this.request = function (_URL, params) {
						let url = new URL(_URL);

						for (let name in params) {
							url.searchParams.append(name, params[name]);
						}

						window.location.href = url.href;
					}

					this.getAddNewURL = function () {
						return container.data('add-new-url');
					}
					this.getUpdateURL = function () {
						return container.data('update-url');
					}
				}

				const customColumnsTable = new CustomColumnsTable(jQuery('.tiered-pricing-custom-columns-table'));
				customColumnsTable.init();
			});
		</script>
		<style>
			.tiered-pricing-custom-columns-table {
				margin-top: 20px;
			}

			.tiered-pricing-custom-columns-table table {
				width: max-content;
			}

			.tiered-pricing-custom-columns-table--hidden {
				display: none;
			}

			.tiered-pricing-custom-columns-table table th, .tiered-pricing-custom-columns-table table td {
				padding: 20px;
			}
		</style>
		<?php
		$addNewURL = add_query_arg( array(
			'action' => self::ADD_NEW_ACTION,
			'nonce'  => wp_create_nonce( self::ADD_NEW_ACTION ),
		), admin_url( 'admin-post.php' ) );
		
		$updateURL = add_query_arg( array(
			'action' => self::UPDATE_ACTION,
			'nonce'  => wp_create_nonce( self::UPDATE_ACTION ),
		), admin_url( 'admin-post.php' ) )
		
		?>
		<div class="tiered-pricing-custom-columns-table tiered-pricing-custom-columns-table--hidden"
			 data-add-new-url="<?php echo esc_attr( $addNewURL ); ?>"
			 data-update-url=" <?php echo esc_attr( $updateURL ); ?>">
			<table class="wp-list-table widefat fixed striped">
				<thead>
				<tr style="text-align: center">
					<th><?php esc_html_e( 'Column name', 'tier-pricing-table' ); ?></th>
					<th><?php esc_html_e( 'Column content', 'tier-pricing-table' ); ?></th>
					<th><?php esc_html_e( 'Content type', 'tier-pricing-table' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'tier-pricing-table' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ( $this->addon->columnsManager->getColumns() as $column ) : ?>
					<tr data-slug="<?php echo esc_attr( $column->getSlug() ); ?>">
						<td>
							<input style="width: 100%"
								   name="tiered_pricing_custom_column_name"
								   type="text"
								   value="<?php echo esc_attr( $column->getName() ); ?>">
						</td>
						<td>
							<select style="width: 100%"
									name="tiered_pricing_custom_column_type">
								<?php foreach ( Schema::getAvailableCustomColumnsTypes() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>"
										<?php
											selected( $column->getType(), $key );
										?>
									>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
						<td>
							<div>
								<select style="width: 100%"
										name="tiered_pricing_custom_column_data_type">
									<?php foreach ( Schema::getAvailableDataTypes() as $key => $label ) : ?>
										<option
												value="<?php echo esc_attr( $key ); ?>"
											<?php
												selected( $column->getDataType(), $key );
											?>
										>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

						</td>
						<td>
							<button class="button button-tpt-button tiered-pricing-custom-columns-table__edit-button">
								<?php esc_html_e( 'Update', 'tier-pricing-table' ); ?>
							</button>
							<?php
								$removeURL = add_query_arg( array(
									'nonce'  => wp_create_nonce( self::REMOVE_COLUMN_ACTION ),
									'action' => self::REMOVE_COLUMN_ACTION,
									'slug'   => $column->getSlug(),
								), admin_url( 'admin-post.php' ) );
							?>

							<a class="button"
							   type="button"
							   href="<?php echo esc_attr( $removeURL ); ?>"
							   onclick="return confirm('Are you sure? All column\'s data will be deleted.');"
							   data-slug="<?php echo esc_attr( $column->getSlug() ); ?>" style="color: red !important;
				border-color: red !important;">
								<?php esc_html_e( 'Delete', 'tier-pricing-table' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
				<tfoot>
				<tr class="tiered-pricing-custom-columns-table__new-column-row" style="background: #e9e6ed;">
					<td style="vertical-align: top">
						<input type="text"
							   style="width: 100%"
							   placeholder="<?php esc_attr_e( 'Enter column name', 'tier-pricing-table' ); ?>"
							   name="tiered_pricing_custom_column_name">
						<div style="margin-top: 5px;">
							<ul>
								<li>
									<?php
										esc_html_e( 'Enter the name of the column.',
											'tier-pricing-table' );
									?>
								</li>
							</ul>
						</div>
					</td>
					<td style="vertical-align: top">
						<select name="tiered_pricing_custom_column_type" style="width: 100%">
							<?php foreach ( Schema::getAvailableCustomColumnsTypes() as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>">
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<div style="margin-top: 5px;">
							<ul>
								<li>
									<b><?php esc_html_e( 'Custom column', 'tier-pricing-table' ); ?></b>
									<?php
										esc_html_e( 'adds additional input to tiered pricing form where you can  input custom content for each tier.',
											'tier-pricing-table' );
									?>
								</li>
								<li>
									<b><?php esc_html_e( 'Price excluding taxes', 'tier-pricing-table' ); ?></b>
									<?php
										esc_html_e( 'shows the price excluding taxes for each tier.',
											'tier-pricing-table' );
									?>
								</li>
								<li>
									<b><?php esc_html_e( 'Price including taxes', 'tier-pricing-table' ); ?></b>
									<?php
										esc_html_e( 'shows the price including taxes for each tier.',
											'tier-pricing-table' );
									?>
								</li>
								<li>
									<b><?php esc_html_e( 'Total row price', 'tier-pricing-table' ); ?></b>
									<?php
										esc_html_e( 'shows the total price for each tier.', 'tier-pricing-table' );
									?>
								</li>
							</ul>
						</div>
					</td>
					<td style="vertical-align: top">

						<div>
							<select name="tiered_pricing_custom_column_data_type" style="width: 100%">
								<?php foreach ( Schema::getAvailableDataTypes() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>">
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<div style="margin-top: 5px;">
								<?php
									esc_html_e( 'Choose how to format content from your custom column:',
										'tier-pricing-table' );
								?>
								<ul>
									<li>
										<b><?php esc_html_e( 'Price', 'tier-pricing-table' ); ?>:</b>
										<?php
											esc_html_e( 'format the content as a price.', 'tier-pricing-table' );
										?>
									</li>
									<li>
										<b><?php esc_html_e( 'Number', 'tier-pricing-table' ); ?>:</b>
										<?php
											esc_html_e( 'format the content as a number.', 'tier-pricing-table' );
										?>
									</li>
									<li>
										<b><?php esc_html_e( 'Text', 'tier-pricing-table' ); ?>:</b>
										<?php
											esc_html_e( 'don\'t format the content. Display it as is.',
												'tier-pricing-table' );
										?>
									</li>
								</ul>

							</div>
						</div>
					</td>


					<td style="vertical-align: top">
						<button class="button button-tpt-button" type="button">
							<span style="font-size: 1.2em; line-height: 28px;" class="dashicons dashicons-plus-alt">
							</span>
							<?php esc_html_e( 'Add new column', 'tier-pricing-table' ); ?>
						</button>
					</td>
				</tr>

				</tfoot>
			</table>
			
			<?php if ( ! empty( $this->addon->columnsManager->getRawColumns() ) ) : ?>
				
				<?php
				$removeAllURL = add_query_arg( array(
					'nonce'  => wp_create_nonce( self::REMOVE_ALL_CUSTOM_COLUMNS ),
					'action' => self::REMOVE_ALL_CUSTOM_COLUMNS,
				), admin_url( 'admin-post.php' ) )
				?>

				<div style="margin-top: 10px">
					<a style="color:red"
					   type="button"
					   onclick="return confirm('Are you sure? All columns\' data will be deleted.');"
					   href=" <?php echo esc_attr( $removeAllURL ); ?> ">
						<?php esc_html_e( 'Remove all custom columns', 'tier-pricing-table' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}