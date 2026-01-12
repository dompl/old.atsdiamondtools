<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Form;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs\ProductAndCategories;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs\Quantity;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs\Pricing;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs\Settings;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs\UsersAndRoles;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\GlobalTieredPricingCPT;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Core\ServiceContainerTrait;
use WP_Post;

class Form {
	
	use ServiceContainerTrait;
	
	/**
	 * Tabs
	 *
	 * @var FormTab[]
	 */
	protected $tabs;
	
	protected $defaultTab = 'pricing';
	
	protected $pricingRuleInstance = null;
	
	public function __construct() {
		
		add_action( 'init', function () {
			$this->tabs = apply_filters( 'tiered_pricing_table/global_pricing/form_tabs', array(
				new Pricing( $this ),
				new ProductAndCategories( $this ),
				new UsersAndRoles( $this ),
				new Quantity( $this ),
				new Settings( $this ),
			) );
		} );
		
		add_action( 'edit_form_after_title', function ( WP_Post $post ) {
			if ( GlobalTieredPricingCPT::SLUG !== $post->post_type ) {
				return;
			}
			
			$this->render( $post );
		} );
	}
	
	protected function includeAssets() {
		?>
		<style>
			/**
			* Externals
			 */
			/* do not display any notices on rule creation */
			.wrap .notice:not(.notice-success, .tpt__admin__feedback-discount-banner) {
				display: none
			}

			.tpt-global-pricing-rule-form .woocommerce-help-tip {
				margin-left: 5px;
			}

			/* tab hint */
			.tpt-global-pricing-rule-hint {
				display: flex;
				align-items: center;
				padding: 10px 10px;
				border: 1px solid #eee5ed;
				background: #faf6f9;
				color: #814c77 !important;
				margin-bottom: 20px;
			}

			.tpt-global-pricing-rule-hint--top-level {
				margin-top: 10px;
				border: 1px solid #888;
			}

			.tpt-global-pricing-rule-hint__icon {
				margin-right: 10px;
			}

			.tpt-global-pricing-rule-form {
				margin: 20px 0;
				display: flex;
				overflow: hidden;
				border-radius: 3px;
				flex-wrap: nowrap;
			}

			.tpt-global-pricing-rule-form__tabs {
				width: 30%;
				max-width: 300px;
				min-width: 250px;
			}

			.tpt-global-pricing-rule-form-tab {
				background: #fff;
				border-bottom: 1px solid #e8e8e8;
				border-left: 1px solid #e8e8e8;
				overflow: hidden;
				cursor: pointer;
				display: flex;
				align-items: center;
				padding: 15px 10px;
			}

			.tpt-global-pricing-rule-form-tab:first-child {
				border-top: 1px solid #e8e8e8;
			}

			.tpt-global-pricing-rule-form-tab:hover:not(.tpt-global-pricing-rule-form-tab--active) {
				background: #fbfbfb;
			}

			.tpt-global-pricing-rule-form-tab--active {
				cursor: default;
				background: #faf6f9;
			}

			.tpt-global-pricing-rule-form-tab__icon {
				transition: all .1s;
				margin-right: 10px;
				height: 40px;
				aspect-ratio: 1/1;
				border-radius: 50%;
				background: #faf6f9;
				text-align: center;
				color: #814c77;
				font-size: 20px;
				font-weight: bold;
				display: flex;
				justify-content: center;
				align-items: center;
			}

			.tpt-global-pricing-rule-form-tab--active h3,
			.tpt-global-pricing-rule-form-tab--active div {
				color: #814c77 !important;
			}

			.tpt-global-pricing-rule-form-tab--active .tpt-global-pricing-rule-form-tab__icon {
				background: #fff;
			}

			.tpt-global-pricing-rule-form-tab__title h3 {
				font-size: 1.1em;
				margin: 0;
			}

			.tpt-global-pricing-rule-form-tab__title div {
				margin-top: 5px;
				color: #777;
			}

			.tpt-global-pricing-rule-form-tab-content {
				display: none;
			}

			.tpt-global-pricing-rule-form-tab-content--active {
				display: block;
			}

			.tpt-global-pricing-rule-form__content {
				width: 70%;
				background: #fff;
				flex-grow: 1;
				padding: 10px;
				border: 1px solid #e8e8e8;
				box-shadow: 0 0 8px rgba(0, 0, 0, .1);
			}

			.tpt-global-pricing-rule-form input[type="text"],
			.tpt-global-pricing-rule-form input[type="number"],
			.tpt-global-pricing-rule-form .tiered-pricing-pricing-rules-form-row__inputs {
				width: 75% !important;
			}

			.tpt-global-pricing-rule-form #tiered_pricing_type {
				max-width: 75%;
				width: 75% !important;
			}

			@media screen and (max-width: 1248px) {

				.tpt-global-pricing-rule-form input[type="text"],
				.tpt-global-pricing-rule-form input[type="number"],
				.tpt-global-pricing-rule-form .tiered-pricing-pricing-rules-form-row__inputs {
					width: 100% !important;
				}

				.tpt-global-pricing-rule-form #tiered_pricing_type {
					max-width: 100%;
					width: 100% !important;
				}


				.tpt-global-pricing-rule-form {
					flex-wrap: wrap;
				}

				.tpt-global-pricing-rule-form__tabs {
					display: flex;
					max-width: 100%;
					width: 100%;
				}

				.tpt-global-pricing-rule-form-tab__icon {
					display: none;
				}

				.tpt-global-pricing-rule-form-tab--active {
					border-bottom: 3px solid #814c77;
				}
			}

			@media screen and (max-width: 500px) {
				.tiered-pricing-form-block {
					padding: 5px 20px !important;
				}
			}
		</style>
		<script>
			jQuery(document).ready(function () {
				let tabs = jQuery('.tpt-global-pricing-rule-form-tab');
				let tabsContent = jQuery('.tpt-global-pricing-rule-form-tab-content');

				tabs.click(function (e) {
					e.preventDefault();

					tabsContent.removeClass('tpt-global-pricing-rule-form-tab-content--active');
					tabs.removeClass('tpt-global-pricing-rule-form-tab--active');

					jQuery(this).addClass('tpt-global-pricing-rule-form-tab--active');

					const target = jQuery(this).data('target');

					jQuery('#' + target).addClass('tpt-global-pricing-rule-form-tab-content--active');
				});
			});
		</script>
		<?php
	}
	
	protected function render( WP_Post $post ) {
		
		$this->includeAssets();
		
		$rulesCount = (int) wp_count_posts( GlobalTieredPricingCPT::SLUG )->publish;
		
		if ( $this->isNewRule() && $rulesCount < 1 ) {
			$this->renderHelpingSteps();
		}
		
		if ( ! $this->isNewRule() && ! $this->getPricingRuleInstance( $post )->isValidPricing() ) {
			$this->tabs[0]->renderHint( __( 'The pricing rule does not affect either prices or product quantity limits. The rule will be skipped.',
				'tier-pricing-table' ), array( 'custom_class' => 'tpt-global-pricing-rule-hint--top-level' ) );
		}
		
		?>
		<div class="tpt-global-pricing-rule-form">

			<nav class="tpt-global-pricing-rule-form__tabs">
				<?php foreach ( $this->tabs as $tab ) : ?>
					<div class="tpt-global-pricing-rule-form-tab <?php echo esc_attr( $tab->getId() === $this->defaultTab ? 'tpt-global-pricing-rule-form-tab--active' : '' ); ?>"
						 data-target="tpt-global-pricing-rule-form-tab-<?php echo esc_attr( $tab->getId() ); ?>">

						<div class="tpt-global-pricing-rule-form-tab__icon" style="">
							<?php if ( $tab->getIcon() === '$' ) : ?>
								<span>$</span>
							<?php else : ?>
								<span class="dashicons <?php echo esc_attr( $tab->getIcon() ); ?>"></span>
							<?php endif; ?>
						</div>

						<div class="tpt-global-pricing-rule-form-tab__title">
							<h3>
								<?php echo esc_html( $tab->getTitle() ); ?>
							</h3>
							<div><?php echo esc_html( $tab->getDescription() ); ?></div>
						</div>
					</div>
				<?php endforeach; ?>
			</nav>

			<section class="tpt-global-pricing-rule-form__content woocommerce_options_panel">
				<?php foreach ( $this->tabs as $tab ) : ?>
					<div class="tpt-global-pricing-rule-form-tab-content <?php echo esc_attr( $tab->getId() === $this->defaultTab ? 'tpt-global-pricing-rule-form-tab-content--active' : '' ); ?>"
						 id="tpt-global-pricing-rule-form-tab-<?php echo esc_attr( $tab->getId() ); ?>">
						<?php
							$tab->render( $this->getPricingRuleInstance( $post ) );
							
							do_action( 'tiered_pricing_table/global_pricing/form/tab_end', $tab,
								$this->getPricingRuleInstance( $post ) );
						?>
					</div>
				<?php endforeach; ?>
			</section>
		</div>
		<?php
	}
	
	/**
	 * Get pricing rule instance
	 *
	 * @param  WP_Post  $post
	 *
	 * @return GlobalPricingRule
	 */
	public function getPricingRuleInstance( WP_Post $post ): GlobalPricingRule {
		if ( empty( $this->pricingRuleInstance ) ) {
			$this->pricingRuleInstance = GlobalPricingRule::build( $post->ID );
		}
		
		return $this->pricingRuleInstance;
	}
	
	public function renderHelpingSteps() {
		?>
		<style>
			.tpt-global-pricing-rule-helping {
				background: #fff;
				border: 1px solid #e8e8e8;
				padding: 20px;
				position: relative;
				text-align: center;
				margin: 20px 0;
			}

			.tpt-global-pricing-rule-helping__close {
				position: absolute;
				top: 10px;
				width: 26px;
				height: 26px;
				background: #faf6f9;
				color: #814c77;
				text-align: center;
				line-height: 24px;
				right: 10px;
				font-weight: bold;
				border-radius: 50%;
			}

			.tpt-global-pricing-rule-helping__close:hover {
				background: #f3ddee;
				cursor: pointer;
			}

			.tpt-global-pricing-rule-helping__title {
				font-size: 1.5em;
				font-weight: bold;
				margin-bottom: 15px;
			}

			.tpt-global-pricing-rule-helping__steps {
				justify-content: center;
				gap: 20px;
				display: flex;
				align-items: center;
				margin-top: 30px
			}

			.tpt-global-pricing-rule-helping-step--arrow {
				padding: 5px;
				border-radius: 50%;
				background: #faf6f9;
				color: #814c77;
			}

			.tpt-global-pricing-rule-helping-step__title {
				font-size: 1.4em;
				font-weight: 600;
			}

			.tpt-global-pricing-rule-helping-step__description {
				margin-top: 10px;
			}

			.tpt-global-pricing-rule-helping-step__icon,
			.tpt-global-pricing-rule-helping-step__icon span {
				width: 50px;
				height: 50px;
				border-radius: 50%;
				background: #faf6f9;
				margin: 0 auto 15px;
				text-align: center;
				color: #814c77;
				font-size: 25px;
				font-weight: bold;
				line-height: 50px;
			}
		</style>
		<script>
			jQuery(document).ready(function () {
				jQuery('.tpt-global-pricing-rule-helping__close').click(function () {
					jQuery(this).parent().hide();
				})
			})
		</script>
		<?php
		$steps = array(
			array(
				'title'         => 'Add pricing',
				'description'   => 'Set up custom regular and/or tiered pricing.',
				'icon'          => '$',
				'has_next_step' => true,
			),
			array(
				'title'         => 'Select products',
				'description'   => 'Select products or product categories that the rule will apply to.',
				'icon'          => '<span class="dashicons dashicons-archive"></span>',
				'has_next_step' => true,
			),
			array(
				'title'         => 'Select users',
				'description'   => 'Select users or user roles that the rule will apply to.',
				'icon'          => '<span class="dashicons dashicons-admin-users"></span>',
				'has_next_step' => true,
			),
			array(
				'title'         => 'Specify quantity',
				'description'   => 'Specify the minimum, maximum, and quantity step for products.',
				'icon'          => '<span class="dashicons dashicons-database"></span>',
				'has_next_step' => false,
			),
		)
		?>
		<div class="tpt-global-pricing-rule-helping">
			<div class="tpt-global-pricing-rule-helping__title">
				<?php esc_html_e( 'How global pricing rules work', 'tier-pricing-table' ); ?>
			</div>
			<p>
				<?php
					esc_html_e( 'Global rules are useful when you need to set custom pricing for multiple products and apply it to a specific group of users.',
						'tier-pricing-table' );
				?>
			</p>

			<p>
				<?php
					esc_html_e( 'Please note that depending on the priority settings, the global pricing rule may override product-level tiered pricing or quantity limits.',
						'tier-pricing-table' );
				?>
			</p>

			<div class="tpt-global-pricing-rule-helping__steps">
				
				<?php foreach ( $steps as $step ) : ?>

					<div class="tpt-global-pricing-rule-helping-step">
						<div class="tpt-global-pricing-rule-helping-step__icon">
							<?php echo wp_kses_post( $step['icon'] ); ?>
						</div>

						<div class="tpt-global-pricing-rule-helping-step__title">
							<?php echo esc_html( $step['title'] ); ?>
						</div>

						<div class="tpt-global-pricing-rule-helping-step__description">
							<?php echo esc_html( $step['description'] ); ?>
						</div>
					</div>
					
					<?php if ( $step['has_next_step'] ) : ?>
						<div class="tpt-global-pricing-rule-helping-step tpt-global-pricing-rule-helping-step--arrow">
							<span class="dashicons dashicons-arrow-right-alt"></span>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
			<div class="tpt-global-pricing-rule-helping__close">
				&times;
			</div>
		</div>
		<?php
	}
	
	public function isNewRule(): bool {
		global $pagenow;
		
		return 'post-new.php' == $pagenow;
	}
}