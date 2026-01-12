<?php namespace TierPricingTable\Addons\ReactProductEditorAddon\Blocks;

use TierPricingTable\Addons\ReactProductEditorAddon\Block;

class PremiumWrapper extends Block {
	
	public function getCustomBlockFolder(): ?string {
		return __DIR__ . '/../js/premium-wrapper';
	}
	
	public function getId(): string {
		return 'tiered-pricing-table/premium-wrapper';
	}
	
	public function getBlockName(): string {
		return 'tiered-pricing-table/premium-wrapper';
	}
	
	public function getOrder(): int {
		return 30;
	}
	
	public function getSectionId(): string {
		return '';
	}
	
	public function getAttributes(): array {
		return array(
			'isPremium' => true,
		);
	}
	
	public function isCustomBlock(): bool {
		return true;
	}
}
