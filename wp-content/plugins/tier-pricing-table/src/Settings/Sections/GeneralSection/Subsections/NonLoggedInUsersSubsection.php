<?php namespace TierPricingTable\Settings\Sections\GeneralSection\Subsections;

use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\CustomOptions\TPTTextTemplate;
use TierPricingTable\Settings\Sections\SubsectionAbstract;
use TierPricingTable\Settings\Settings;

class NonLoggedInUsersSubsection extends SubsectionAbstract {
	
	public function getTitle(): string {
		return __( 'Non logged-in users', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Control whether to display prices for non-logged in users or not. Allow them to purchase products or not.',
			'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'non-logged-in-users';
	}
	
	public function getSettings(): array {
		return array(
			array(
				'title'    => __( 'Prevent purchase for non-logged-in users', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'non_logged_in_users_prevent_purchase',
				'type'     => TPTSwitchOption::FIELD_TYPE,
				'default'  => 'no',
				'desc_tip' => false,
			),
			
			array(
				'title'       => __( 'Add-to-cart button label for non-logged-in users', 'tier-pricing-table' ),
				'id'          => Settings::SETTINGS_PREFIX . 'non_logged_in_users_add_to_cart_label',
				'type'        => 'text',
				'default'     => '',
				'desc'        => __( 'Change default Add to cart label to something else to be displayed for non-logged users.',
					'tier-pricing-table' ),
				'placeholder' => __( 'Leave empty to keep it as it is', 'tier-pricing-table' ),
				'desc_tip'    => false,
			),
			
			array(
				'title'        => __( 'Error message when non-logged-in users add to cart', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'non_logged_in_users_purchase_message',
				'type'         => TPTTextTemplate::FIELD_TYPE,
				'placeholders' => array(),
				// translators: %s: login page url
				'default'      => sprintf( __( 'Please enter %s to make a purchase.', 'tier-pricing-table' ),
					sprintf( '<a href="%s">%s</a>', wc_get_account_endpoint_url( 'dashboard' ),
						__( 'your account', 'tier-pricing-table' ) ) ),
			),
			
			array(
				'title'    => __( 'Hide prices for non-logged-in users', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'non_logged_in_users_hide_prices',
				'type'     => TPTSwitchOption::FIELD_TYPE,
				'default'  => 'no',
				'desc_tip' => false,
			),
			array(
				'title'        => __( 'Price message for non-logged-in users', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'non_logged_in_users_price_message',
				'type'         => TPTTextTemplate::FIELD_TYPE,
				'description'  => __( 'This message will be displayed instead of the price for non-logged-in users.',
					'tier-pricing-table' ),
				'placeholders' => array(),
				// translators: %s: login page url
				'default'      => sprintf( __( '%s see prices', 'tier-pricing-table' ),
					sprintf( '<a href="%s">%s</a>', wc_get_account_endpoint_url( 'dashboard' ),
						__( 'Login', 'tier-pricing-table' ) ) ),
			),
		);
	}
}
