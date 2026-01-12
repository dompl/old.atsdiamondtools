<?php namespace TierPricingTable\Settings;

use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\Settings\CustomOptions\TPTDisplayType;
use TierPricingTable\Settings\CustomOptions\TPTLinkButton;
use TierPricingTable\Settings\CustomOptions\TPTTableColumnsField;
use TierPricingTable\Settings\CustomOptions\TPTQuantityMeasurementField;
use TierPricingTable\Settings\CustomOptions\TPTIntegrationOption;
use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\CustomOptions\TPTTextTemplate;
use TierPricingTable\Settings\Sections\Advanced\AdvancedSection;
use TierPricingTable\Settings\Sections\CalculationLogic\CalculationLogic;
use TierPricingTable\Settings\Sections\GeneralSection\GeneralSection;
use TierPricingTable\Settings\Sections\Integrations\IntegrationSection;
use TierPricingTable\Settings\Sections\SectionAbstract;
use TierPricingTable\TierPricingTablePlugin;

/**
 * Class Settings
 *
 * @package TierPricingTable\Settings
 */
class Settings {

	use ServiceContainerTrait;

	const SETTINGS_PREFIX = 'tier_pricing_table_';

	const SETTINGS_PAGE = 'tiered_pricing_table_settings';

	const DEFAULT_SECTION = 'general';

	/**
	 * Settings
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Settings sections
	 *
	 * @var SectionAbstract[]
	 */
	protected $sections = array();

	/**
	 * Settings constructor.
	 */
	public function __construct() {
		$this->initCustomOptions();
		$this->hooks();
	}

	protected function initCustomOptions() {
		$this->getContainer()->add( 'settings.tpt_switch_option', new TPTSwitchOption() );
		$this->getContainer()->add( 'settings.tpt_integration_option', new TPTIntegrationOption() );
		$this->getContainer()->add( 'settings.tpt_text_template', new TPTTextTemplate() );
		$this->getContainer()->add( 'settings.tpt_display_type', new TPTDisplayType() );
		$this->getContainer()->add( 'settings.tpt_link_button', new TPTLinkButton() );
		$this->getContainer()->add( 'settings.tpt_quantity_measurement', new TPTQuantityMeasurementField() );
		$this->getContainer()->add( 'settings.tpt_multiple_fields', new TPTTableColumnsField() );
	}

	protected function initSections() {}

	/**
	 * Handle updating settings
	 */
	public function updateSettings() {
		woocommerce_update_options( $this->settings );
		$this->getContainer()->getCache()->purge();
	}

	/**
	 * Init all settings
	 */
	public function initSettings() {

		$this->sections = apply_filters( 'tiered_pricing_table/settings/sections', array(
				new GeneralSection(),
				new CalculationLogic(),
				new AdvancedSection(),
				new IntegrationSection(),
		) );

		foreach ( $this->sections as $section ) {
			if ( $section->isActive() ) {
				$this->settings = $section->getSettings();
				break;
			}
		}
	}

	/**
	 * Register hooks
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'initSettings' ) );

		add_filter( 'woocommerce_settings_tabs_' . self::SETTINGS_PAGE,
				array( $this, 'addTieredPricingTableSettings' ) );
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'addSettingsTab' ), 50 );
		add_action( 'woocommerce_update_options_' . self::SETTINGS_PAGE, array( $this, 'updateSettings' ) );

		add_action( 'woocommerce_settings_' . self::SETTINGS_PAGE, array( $this, 'renderSections' ), 99 );
	}

	public function renderSections() {
		?>
		<style>
			h2 {
				margin-top: 2em;
				font-size: 1.45em;
			}
		</style>
		<ul class="subsubsub" style="font-size: 1.1em; margin-top: 3px">
			<?php foreach ( $this->sections as $section ) : ?>
				<li>
					<?php if ( ! $section->isActive() ) : ?>
						<a href="<?php echo esc_attr( $section->getURL() ); ?>">
							<?php if ( $section->isIntegration() ) : ?>
								<small style="width: 10px; height: 10px; display: inline-block; margin-right: 10px;">
									<span class="dashicons dashicons-admin-plugins"></span>
								</small>
								<b><?php echo esc_html( $section->getName() ); ?></b>
							<?php else : ?>
								<?php echo esc_html( $section->getName() ); ?>
							<?php endif; ?>

						</a>
					<?php else : ?>

						<?php if ( $section->getSectionCSS() ) : ?>
							<style>
								<?php echo esc_html( $section->getSectionCSS() ); ?>
							</style>
						<?php endif; ?>

						<a class="current" href="#">
							<?php echo esc_html( $section->getName() ); ?>
						</a>
					<?php endif; ?>
					|
				</li>
			<?php endforeach; ?>
			<li>
				<a href="<?php echo esc_attr( TierPricingTablePlugin::getDocumentationURL() ); ?>" target="_blank">
					<?php esc_html_e( 'Documentation', 'tier-pricing-table' ); ?>
					<svg
							style="
							width: 0.8rem;
							height: 0.8rem;
							stroke: currentColor;
							fill: none;"
							xmlns='http://www.w3.org/2000/svg'
							stroke-width='10' stroke-dashoffset='0'
							stroke-dasharray='0' stroke-linecap='round'
							stroke-linejoin='round' viewBox='0 0 100 100'>
						<polyline fill="none" points="40 20 20 20 20 90 80 90 80 60"/>
						<polyline fill="none" points="60 10 90 10 90 40"/>
						<line fill="none" x1="89" y1="11" x2="50" y2="50"/>
					</svg>
				</a>
			</li>
		</ul>
		<br class="clear">
		<?php
	}

	/**
	 * Add own settings tab
	 *
	 * @param $settingsTabs
	 *
	 * @return array
	 */
	public static function addSettingsTab( $settingsTabs ): array {

		$settingsTabs = is_array( $settingsTabs ) ? $settingsTabs : array();

		$settingsTabs[ self::SETTINGS_PAGE ] = __( 'Tiered Pricing', 'tier-pricing-table' );

		return $settingsTabs;
	}

	/**
	 * Add settings to WooCommerce
	 */
	public function addTieredPricingTableSettings() {

		wp_enqueue_script( 'quantity-table-settings-js',
				$this->getContainer()->getFileManager()->locateJSAsset( 'admin/settings' ), array( 'jquery' ),
				TierPricingTablePlugin::VERSION );

		woocommerce_admin_fields( $this->settings );
	}

	/**
	 * Get setting by name
	 *
	 * @param  string  $optionName
	 * @param  mixed  $default
	 *
	 * @return mixed
	 */
	public function get( string $optionName, $default = null ) {
		return get_option( self::SETTINGS_PREFIX . $optionName, $default );
	}

	public static function deleteOptions() {
		GeneralSection::deleteOptions();
		AdvancedSection::deleteOptions();
		IntegrationSection::deleteOptions();
	}

	/**
	 * Get url to settings page
	 *
	 * @return string
	 */
	public function getLink(): string {
		return admin_url( 'admin.php?page=wc-settings&tab=tiered_pricing_table_settings' );
	}

	public static function hex2rgba( $color, $opacity = false ): string {

		$default = 'rgb(0,0,0)';

		if ( empty( $color ) ) {
			return $default;
		}

		if ( '#' === $color[0] ) {
			$color = substr( $color, 1 );
		}

		if ( strlen( $color ) == 6 ) {
			$hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
		} elseif ( strlen( $color ) == 3 ) {
			$hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
		} else {
			return $default;
		}

		$rgb = array_map( 'hexdec', $hex );

		if ( $opacity ) {
			if ( abs( $opacity ) > 1 ) {
				$opacity = 1.0;
			}

			$output = 'rgba(' . implode( ',', $rgb ) . ',' . $opacity . ')';
		} else {
			$output = 'rgb(' . implode( ',', $rgb ) . ')';
		}

		return $output;
	}

	public static function shadeHexWithOpacity( $hex, $opacity, $bgHex = '#FFFFFF' ) {
		// Normalize and parse the foreground hex
		$hex = ltrim( $hex, '#' );
		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( strlen( $hex ) !== 6 ) {
			return false;
		}

		// Normalize and parse the background hex
		$bgHex = ltrim( $bgHex, '#' );
		if ( strlen( $bgHex ) === 3 ) {
			$bgHex = $bgHex[0] . $bgHex[0] . $bgHex[1] . $bgHex[1] . $bgHex[2] . $bgHex[2];
		}
		if ( strlen( $bgHex ) !== 6 ) {
			return false;
		}

		// Clamp opacity
		$opacity = max( 0, min( 1, $opacity ) );

		// Extract RGB
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );

		$bgR = hexdec( substr( $bgHex, 0, 2 ) );
		$bgG = hexdec( substr( $bgHex, 2, 2 ) );
		$bgB = hexdec( substr( $bgHex, 4, 2 ) );

		// Blend with background using alpha compositing
		$newR = round( ( $opacity * $r ) + ( ( 1 - $opacity ) * $bgR ) );
		$newG = round( ( $opacity * $g ) + ( ( 1 - $opacity ) * $bgG ) );
		$newB = round( ( $opacity * $b ) + ( ( 1 - $opacity ) * $bgB ) );

		// Return final hex
		return sprintf( '#%02X%02X%02X', $newR, $newG, $newB );
	}

}