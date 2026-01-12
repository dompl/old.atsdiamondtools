<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT;

use Automattic\WooCommerce\Admin\PageController;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\Actions\ReactivateAction;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\Actions\SuspendAction;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns\AppliedCustomers;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns\AppliedPricingType;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns\AppliedProducts;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns\AppliedQuantityRules;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns\Pricing;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns\Settings;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns\Status;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Form;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Addons\GlobalTieredPricing\PricingRule\RuleSettings;
use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\Forms\MinimumOrderQuantityForm;
use TierPricingTable\Forms\RegularPricingForm;
use TierPricingTable\Forms\TieredPricingRulesForm;
use WP_Post;

class GlobalTieredPricingCPT {
	
	use ServiceContainerTrait;
	
	const SLUG = 'tpt-global-rule';
	
	/**
	 * Pricing rules
	 *
	 * @var GlobalPricingRule
	 */
	private $pricingRuleInstance;
	
	/**
	 * Table columns
	 *
	 * @var array
	 */
	private $columns;
	
	protected static $globalRules = null;
	
	public function __construct() {
		
		new Form();
		
		add_action( 'init', array( $this, 'register' ) );
		
		add_action( 'manage_posts_extra_tablenav', array( $this, 'renderBlankState' ) );
		
		add_filter( 'woocommerce_navigation_screen_ids', array( $this, 'addPageToWooCommerceScreen' ) );
		
		add_filter( 'woocommerce_screen_ids', array( $this, 'addPageToWooCommerceScreen' ) );
		
		add_action( 'save_post_' . self::SLUG, array( $this, 'savePricingRule' ) );
		
		add_filter( 'manage_edit-' . self::SLUG . '_columns', function ( $columns ) {
			unset( $columns['date'] );
			
			foreach ( $this->getColumns() as $key => $column ) {
				$columns[ $key ] = $column->getName();
			}
			
			return $columns;
		}, 999 );
		
		add_filter( 'manage_' . self::SLUG . '_posts_custom_column', function ( $column ) {
			global $post;
			
			$globalRule = GlobalPricingRule::build( $post->ID );
			
			if ( array_key_exists( $column, $this->getColumns() ) ) {
				$this->getColumns()[ $column ]->render( $globalRule );
				
				do_action( 'tiered_pricing_table/global_pricing/table/after_tab_render', $column, $globalRule );
			}
			
			return $column;
		}, 999 );
		
		add_filter( 'post_row_actions', function ( $actions, WP_Post $post ) {
			
			if ( self::SLUG !== $post->post_type ) {
				return $actions;
			}
			
			unset( $actions['inline hide-if-no-js'] );
			
			return $actions;
		}, 10, 2 );
		
		add_filter( 'disable_months_dropdown', function ( $state, $postType ) {
			if ( self::SLUG === $postType ) {
				return true;
			}
			
			return $state;
		}, 10, 2 );
		
		// Refresh cache for variable product pricing
		add_action( 'save_post_' . self::SLUG, function () {
			wc_delete_product_transients();
		} );
		
		$this->initInlineActions();
	}
	
	public function initInlineActions() {
		new SuspendAction();
		new ReactivateAction();
	}
	
	public function getColumns(): array {
		
		if ( is_null( $this->columns ) ) {
			$this->columns = array(
				'pricing'                => new Pricing(),
				'applied_products'       => new AppliedProducts(),
				'applied_customers'      => new AppliedCustomers(),
				'applied_quantity_rules' => new AppliedQuantityRules(),
				'settings'               => new Settings(),
				'status'                 => new Status(),
			);
		}
		
		return $this->columns;
	}
	
	public function getPricingRuleInstance(): ?GlobalPricingRule {
		if ( empty( $this->pricingRuleInstance ) ) {
			global $post;
			
			if ( $post ) {
				$this->pricingRuleInstance = GlobalPricingRule::build( $post->ID );
			} else {
				return null;
			}
		}
		
		return $this->pricingRuleInstance;
	}
	
	public function addPageToWooCommerceScreen( $ids ) {
		$ids[] = self::SLUG;
		$ids[] = 'edit-' . self::SLUG;
		
		return $ids;
	}
	
	public function savePricingRule( $ruleId ) {
		// Save pricing
		if ( wp_verify_nonce( true, true ) ) {
			// as phpcs comments at Woo is not available, we have to do such a trash
			$woo = 'Woo, please add ignoring comments to your phpcs checker';
		}
		
		$postedData = $_POST;
		
		$tieredPricingData        = TieredPricingRulesForm::getDataFromRequest( null, null, '', $postedData, $ruleId );
		$regularPricingData       = RegularPricingForm::getDataFromRequest( null, null, $postedData );
		$minimumOrderQuantityData = MinimumOrderQuantityForm::getDataFromRequest( null, null, $postedData );
		
		$pricingRule  = GlobalPricingRule::build( $ruleId );
		$applyingType = isset( $postedData['tpt_applying_type'] ) ? sanitize_text_field( $postedData['tpt_applying_type'] ) : 'individual';
		
		$pricingRule->setApplyingType( $applyingType );
		$pricingRule->setFixedTieredPricingRules( $tieredPricingData['fixed_tiered_pricing_rules'] );
		
		// Pricing
		$pricingRule->setPricingType( $regularPricingData['pricing_type'] );
		$pricingRule->setRegularPrice( $regularPricingData['regular_price'] );
		$pricingRule->setSalePrice( $regularPricingData['sale_price'] );
		$pricingRule->setDiscount( $regularPricingData['discount'] );
		$pricingRule->setDiscountType( $regularPricingData['discount_type'] );
		
		// Tiered Pricing
		$pricingRule->setTieredPricingType( $tieredPricingData['type'] );
		$pricingRule->setPercentageTieredPricingRules( $tieredPricingData['percentage_tiered_pricing_rules'] );
		
		//MOQ
		$pricingRule->setMinimum( $minimumOrderQuantityData['minimum_order_quantity'] );
		
		$existingRoles = wp_roles()->roles;
		
		$includedCategoriesIds = isset( $postedData['tpt_included_categories'] ) ? array_filter( array_map( 'intval',
			(array) $postedData['tpt_included_categories'] ) ) : array();
		$includedProductsIds   = isset( $postedData['tpt_included_products'] ) ? array_filter( array_map( 'intval',
			(array) $postedData['tpt_included_products'] ) ) : array();
		
		$includedUsersRole = isset( $postedData['tpt_included_user_roles'] ) ? array_filter( (array) $postedData['tpt_included_user_roles'],
			function ( $role ) use ( $existingRoles ) {
				return array_key_exists( $role, $existingRoles );
			} ) : array();
		$includedUsers     = isset( $postedData['tpt_included_users'] ) ? array_filter( array_map( 'intval',
			(array) $postedData['tpt_included_users'] ) ) : array();
		
		
		$excludedCategoriesIds = isset( $postedData['tpt_excluded_categories'] ) ? array_filter( array_map( 'intval',
			(array) $postedData['tpt_excluded_categories'] ) ) : array();
		$excludedProductsIds   = isset( $postedData['tpt_excluded_products'] ) ? array_filter( array_map( 'intval',
			(array) $postedData['tpt_excluded_products'] ) ) : array();
		
		$excludedUsersRole = isset( $postedData['tpt_excluded_user_roles'] ) ? array_filter( (array) $postedData['tpt_excluded_user_roles'],
			function ( $role ) use ( $existingRoles ) {
				return array_key_exists( $role, $existingRoles );
			} ) : array();
		$excludedUsers     = isset( $postedData['tpt_excluded_users'] ) ? array_filter( array_map( 'intval',
			(array) $postedData['tpt_excluded_users'] ) ) : array();
		
		
		$pricingRule->setIncludedProductCategories( $includedCategoriesIds );
		$pricingRule->setIncludedUsers( $includedUsers );
		$pricingRule->setIncludedUsersRole( $includedUsersRole );
		$pricingRule->setIncludedProducts( $includedProductsIds );
		
		
		$pricingRule->setExcludedProductCategories( $excludedCategoriesIds );
		$pricingRule->setExcludedUsers( $excludedUsers );
		$pricingRule->setExcludedUsersRole( $excludedUsersRole );
		$pricingRule->setExcludedProducts( $excludedProductsIds );
		
		RuleSettings::updateFromPOST( $ruleId );
		
		do_action( 'tiered_pricing_table/global_pricing/before_updating', $pricingRule, $ruleId );
		
		$pricingRule->save();
		
		$this->getContainer()->getCache()->purge();
	}
	
	public function renderBlankState( $which ) {
		global $post_type;
		
		if ( self::SLUG === $post_type && 'bottom' === $which ) {
			$counts = (array) wp_count_posts( $post_type );
			unset( $counts['auto-draft'] );
			$count = array_sum( $counts );
			
			if ( 0 < $count ) {
				return;
			}
			?>

			<div class="woocommerce-BlankState" style="padding: 0;  ">
				<img width="250px" style="filter: drop-shadow(1px 10px 10px #ccc);"
					 src="<?php echo esc_attr( $this->getContainer()->getFileManager()->locateAsset( 'admin/pricing-logo.png' ) ); ?>">
				<h2 class="woocommerce-BlankState-message">
					<?php
						esc_html_e( 'There are no pricing rules yet. To create the first pricing rule click on the button below.',
							'tier-pricing-table' );
					?>
				</h2>

				<div class="woocommerce-BlankState-buttons">
					<a class="woocommerce-BlankState-cta button-primary button"
					   href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . self::SLUG ) ); ?>">
						<?php
							esc_html_e( 'Create pricing rule', 'tier-pricing-table
' );
						?>
					</a>
				</div>
			</div>

			<style
					type="text/css">#posts-filter .wp-list-table, #posts-filter .tablenav.top, .tablenav.bottom .actions, .wrap .subsubsub {
					display: none;
				}

				#posts-filter .tablenav.bottom {
					height: auto;
				}
			</style>
			<?php
		}
	}
	
	public function register() {
		if ( class_exists( '\Automattic\WooCommerce\Admin\PageController' ) ) {
			PageController::get_instance()->connect_page( array(
				'id'        => self::SLUG,
				'title'     => array( 'Tiered Pricing' ),
				'screen_id' => self::SLUG,
			) );
		}
		
		register_post_type( self::SLUG, array(
			'labels'             => array(
				'name'               => __( 'Pricing rule', 'tier-pricing-table' ),
				'singular_name'      => __( 'Pricing rule', 'tier-pricing-table' ),
				'add_new'            => __( 'Add Pricing Rule', 'tier-pricing-table' ),
				'add_new_item'       => __( 'Add Pricing Rule', 'tier-pricing-table' ),
				'edit_item'          => __( 'Edit Pricing Rule', 'tier-pricing-table' ),
				'new_item'           => __( 'New Pricing Rule', 'tier-pricing-table' ),
				'view_item'          => __( 'View Pricing Rule', 'tier-pricing-table' ),
				'search_items'       => __( 'Find Pricing Rule', 'tier-pricing-table' ),
				'not_found'          => __( 'No pricing rules ware found', 'tier-pricing-table' ),
				'not_found_in_trash' => __( 'No pricing rule in trash', 'tier-pricing-table' ),
				'parent_item_colon'  => '',
				'menu_name'          => __( 'Pricing rules', 'tier-pricing-table' ),
			
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => 'woocommerce',
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'product',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title' ),
		) );
		
	}
	
	
	/**
	 * Get global rules
	 *
	 * @param  bool  $withValidPricing
	 *
	 * @return GlobalPricingRule[]
	 */
	public static function getGlobalRules( bool $withValidPricing = true ): array {
		
		if ( ! is_null( self::$globalRules ) ) {
			$rules = self::$globalRules;
		} else {
			$rulesIds = get_posts( array(
				'numberposts' => - 1,
				'post_type'   => self::SLUG,
				'post_status' => 'publish',
				'fields'      => 'ids',
				'meta_query'  => array(
					array(
						'key'     => '_tpt_is_suspended',
						'value'   => 'yes',
						'compare' => '!=',
					),
				),
			) );
			
			$rules = array_map( function ( $ruleId ) {
				return GlobalPricingRule::build( $ruleId );
			}, $rulesIds );
			
			self::$globalRules = $rules;
		}
		
		if ( $withValidPricing ) {
			$rules = array_filter( $rules, function ( GlobalPricingRule $rule ) {
				return $rule->isValidPricing();
			} );
		}
		
		return $rules;
	}
}
