<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Form;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Core\ServiceContainerTrait;

abstract class FormTab {
	
	use ServiceContainerTrait;
	
	/**
	 * Form
	 *
	 * @var Form
	 */
	protected $form;
	
	public function __construct( Form $form ) {
		$this->form = $form;
	}
	
	abstract public function getId();
	
	abstract public function getTitle();
	
	abstract public function getDescription();
	
	abstract public function getIcon(): string;
	
	abstract public function render( GlobalPricingRule $pricingRule );
	
	public function renderRadioOptions( $args = array(), $inline = false ) {
		?>
		<div class="tiered-pricing-form-block">
			<label for="tpt_tiered_pricing_type">
				<?php echo esc_html( $args['title'] ); ?>
			</label>
			<div style="min-height: 25px">
				<?php if ( $inline ) : ?>
					
					<?php foreach ( $args['options'] as $optionId => $optionLabel ) : ?>
						<span style="margin-right:15px">
						<label style="margin:0; float:none; font-size: 12px;">
							<input style="margin-right:3px;"
								   type="radio"
								   name="<?php echo esc_attr( $args['id'] ); ?>"
								   value="<?php echo esc_attr( $optionId ); ?>"
								<?php checked( $optionId, $args['value'] ); ?>
							>
							<?php echo esc_html( $optionLabel ); ?>
						</label>
					</span>
					<?php endforeach; ?>
				
				<?php else : ?>
					<ul style="margin:0">
						<?php foreach ( $args['options'] as $optionId => $optionLabel ) : ?>
							<li style="margin-bottom: 10px">
								<label style="margin:0;float:none;font-size: 12px;">
									<input style="margin-right:3px;"
										   type="radio"
										   name="<?php echo esc_attr( $args['id'] ); ?>"
										   value="<?php echo esc_attr( $optionId ); ?>"
										<?php checked( $optionId, $args['value'] ); ?>
									>
									<?php echo esc_html( $optionLabel ); ?>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

		</div>
		<?php
	}
	
	public function renderCheckbox( $args = array() ) {
		
		$args = wp_parse_args( $args, array(
			'title'       => '',
			'id'          => '',
			'value'       => '',
			'label'       => '',
			'description' => '',
		) );
		
		?>
		<div class="tiered-pricing-form-block">
			<label for="tpt_tiered_pricing_type">
				<?php echo esc_html( $args['title'] ); ?>
			</label>
			<div style="min-height: 25px">
				<label style="margin:0; float:none; font-size: 12px;">
					<input style="margin-right:3px;"
						   type="checkbox"
						   name="<?php echo esc_attr( $args['id'] ); ?>"
						   value="<?php echo esc_attr( $args['value'] ); ?>"
						<?php checked( $args['value'] ); ?>
					>
					<?php echo esc_html( $args['label'] ); ?>
				</label>
			</div>
			
			<?php if ( $args['description'] ) : ?>
				<p class="description" style="margin:0">
					<?php echo esc_html( $args['description'] ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}
	
	public function renderSectionTitle( $sectionTitle, $args = array() ) {
		
		$args = wp_parse_args( $args, array(
			'description' => '',
		));
		
		?>

		<div class="tpt-global-pricing-title">
			<?php echo esc_attr( $sectionTitle ); ?>
			<?php
			if ( $args['description'] ) {
				echo wc_help_tip( $args['description'] );
			}
			?>
		</div>
		<?php
	}
	
	public function renderSelect2( $args = array() ) {
		
		$args = wp_parse_args( $args, array(
			'id'                   => '',
			'search_action'        => '',
			'value'                => '',
			'options'              => null,
			'placeholder'          => '',
			'multiple'             => true,
			'width'                => '100%',
			'description'          => '',
			'desc_tip'             => true,
			'minimum_input_length' => 1,
			'css_class'            => 'wc-product-search',
		) );
		
		?>
		<div class="tiered-pricing-form-block">
			<label for="<?php echo esc_attr( $args['id'] ); ?>">
				<?php echo esc_html( $args['label'] ); ?>
			</label>

			<select class="<?php echo esc_attr( $args['css_class'] ); ?>" <?php echo esc_attr( $args['multiple'] ? 'multiple="multiple"' : '' ); ?>
					style="width: <?php echo esc_attr( $args['width'] ); ?>"
					id="<?php echo esc_attr( $args['id'] ); ?>"
					name="<?php echo esc_attr( $args['multiple'] ? $args['id'] . '[]' : $args['id'] ); ?>"
					data-placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>"
					data-action="<?php echo esc_attr( $args['search_action'] ); ?>"
					data-minimum_input_length="<?php echo esc_attr( $args['minimum_input_length'] ); ?>">
				>
				
				<?php if ( $args['options'] ) : ?>
					
					<?php foreach ( $args['options'] as $optionId => $label ) : ?>
						<option
							<?php selected( in_array( $optionId, $args['value'] ) ); ?>
								value="<?php echo esc_attr( $optionId ); ?>">
							<?php echo esc_attr( $label ); ?>
						</option>
					<?php endforeach; ?>
				
				<?php else : ?>
					
					<?php foreach ( $args['value'] as $optionId => $label ) : ?>
						<option selected
								value="<?php echo esc_attr( $optionId ); ?>">
							<?php echo esc_attr( $label ); ?>
						</option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
			
			<?php if ( $args['description'] ) : ?>
				<?php if ( $args['desc_tip'] ) : ?>
					<?php echo wp_kses_post( wc_help_tip( $args['description'] ) ); ?>
				<?php else : ?>
					<p class="description" style="margin:0">
						<?php echo esc_html( $args['description'] ); ?>
					</p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}
	
	public function renderHint( $hint, $args = array() ) {
		
		$args = wp_parse_args( $args, array(
			'only_for_new_rules' => false,
			'show_icon'          => true,
			'custom_class'       => '',
		) );
		
		if ( ! $hint ) {
			return;
		}
		
		if ( $args['only_for_new_rules'] && ! $this->form->isNewRule() ) {
			return;
		}
		
		?>
		<div class="tpt-global-pricing-rule-hint <?php echo esc_attr( $args['custom_class'] ); ?>">
			<?php if ( $args['show_icon'] ) : ?>
				<div class="tpt-global-pricing-rule-hint__icon">
					<span class="dashicons dashicons-editor-help"></span>
				</div>
			<?php endif; ?>
			<div class="tpt-global-pricing-rule-hint__content">
				<?php echo wp_kses_post( $hint ); ?>
			</div>
		</div>
		<?php
	}
}
