<?php namespace TierPricingTable\Settings\CustomOptions;

class TPTIntegrationOption {

	const FIELD_TYPE = 'tpt_integration_option';

	public function __construct() {
		add_action( 'woocommerce_admin_field_' . self::FIELD_TYPE, array( $this, 'render' ) );

		add_action( 'woocommerce_admin_settings_sanitize_option', function ( $value, $option, $rawValue ) {

			if ( self::FIELD_TYPE === $option['type'] ) {
				$value = in_array( $value, array( 1, 'yes' ) ) ? 'yes' : 'no';
			}

			return $value;
		}, 10, 3 );
	}

	public function render( $value ) {
		if ( ! isset( $value['id'] ) ) {
			$value['id'] = '';
		}

		if ( ! isset( $value['title'] ) ) {
			$value['title'] = isset( $value['name'] ) ? $value['name'] : '';
		}
		if ( ! isset( $value['default'] ) ) {
			$value['default'] = '';
		}

		if ( ! isset( $value['value'] ) ) {
			$value['value'] = \WC_Admin_Settings::get_option( $value['id'], $value['default'] );
		}

		if ( ! isset( $value['on_label'] ) ) {
			$value['on_label'] = __( 'On', 'role-and-customer-based-pricing-for-woocommerce' );
		}

		if ( ! isset( $value['off_label'] ) ) {
			$value['off_label'] = __( 'Off', 'role-and-customer-based-pricing-for-woocommerce' );
		}
		if ( ! isset( $value['desc'] ) ) {
			$value['desc'] = '';
		}

		$option_value = $value['value'];
		?>

		<tr class="tpt-integration-item" style="display: flex; width: 50%; min-width: 400px;">
			<td>
				<div style="display: flex; gap: 20px;">
					<div class="tpt-integration-item__image" style="width:100px;">
						<img src="<?php echo esc_attr( $value['icon_url'] ); ?>" width="100px" height="100px"
							 alt="<?php echo esc_html( $value['title'] ); ?>">
					</div>
					<div class="tpt-integration-item__description">

						<?php if ( $value['author_url'] ) : ?>
							<a target="_blank" href="<?php echo esc_attr( $value['author_url'] ); ?>">
								<h4 style="margin-top: 0; margin-bottom: 10px"><?php echo esc_html( $value['title'] ); ?></h4>
							</a>
						<?php else : ?>
							<h4 style="margin-top: 0; margin-bottom: 10px"><?php echo esc_html( $value['title'] ); ?></h4>
						<?php endif; ?>

						<p class="description">
							<?php echo esc_html( $value['desc'] ); ?>
						</p>

						<?php if ( isset( $value['extended_description'] ) ) : ?>
							<div class="tpt-toggle-extended-description">
								<?php
									if ( is_callable( $value['extended_description'] ) ) {
										call_user_func( $value['extended_description'] );
									} else {
										echo esc_html( $value['extended_description'] );
									}
								?>
							</div>
						<?php endif; ?>

						<div class="tpt-integration-item-checkbox" style="margin-top: 10px">
							<input
									name="<?php echo esc_attr( $value['id'] ); ?>"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									type="checkbox"
									value="1"
									<?php checked( $option_value, 'yes' ); ?>
									class="tpt-toggle-switch"
							/>
							<label for="<?php echo esc_attr( $value['id'] ); ?>">
								<span data-tpt-toggle-switch-on><?php echo esc_attr( $value['on_label'] ); ?></span>
								<span data-tpt-toggle-switch-off><?php echo esc_attr( $value['off_label'] ); ?></span>
							</label>
						</div>
					</div>
				</div>
			</td>
		</tr>
		<?php
	}
}
