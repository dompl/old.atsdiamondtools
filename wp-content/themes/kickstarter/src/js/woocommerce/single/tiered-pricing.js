/**
 * Tiered Pricing Table Integration for Custom Theme
 * Ensures compatibility between Tiered Pricing Table plugin and the theme's custom quantity field
 */
;(function($) {
    'use strict';

    // Wait for DOM and plugin to be ready
    $(document).ready(function() {

        console.log('[Tiered Pricing] Integration script loaded');

        /**
         * Show loading overlay on .main-left
         */
        function showLoadingOverlay() {
            var $mainLeft = $('.main-left');

            // Check if overlay already exists
            if ($mainLeft.find('.tiered-pricing-loading-overlay').length === 0) {
                var overlayHtml = '<div class="tiered-pricing-loading-overlay">' +
                    '<div class="tiered-pricing-spinner"></div>' +
                    '</div>';
                $mainLeft.append(overlayHtml);
            }

            // Use CSS to show with flex display (for centering)
            var $overlay = $mainLeft.find('.tiered-pricing-loading-overlay');
            $overlay.stop(true, true); // Stop any running animations
            $overlay.css({
                'display': 'flex',
                'opacity': '0',
                'pointer-events': 'auto'
            }).animate({
                'opacity': '1'
            }, 200);

            console.log('[Tiered Pricing] Loading overlay shown');
        }

        /**
         * Hide loading overlay
         */
        function hideLoadingOverlay() {
            var $overlay = $('.tiered-pricing-loading-overlay');
            $overlay.stop(true, true); // Stop any running animations
            $overlay.css({
                'display': 'none',
                'opacity': '0',
                'pointer-events': 'none'
            });
            console.log('[Tiered Pricing] Loading overlay hidden');
        }

        /**
         * Initialize the integration
         * This must run BEFORE the plugin initializes
         */
        function initTieredPricingIntegration() {

            // Check if the plugin's global object exists
            if (typeof document.__tieredPricing === 'undefined') {
                console.log('[Tiered Pricing] Plugin not loaded yet, will wait');
                return false;
            }

            console.log('[Tiered Pricing] Plugin detected, setting up overrides');

            /**
             * Override the $getQuantityField function
             * This is CRITICAL - the plugin uses this to find the quantity input
             */
            document.__tieredPricing.overrides.$getQuantityField = function(parentId, productPageManager) {
                console.log('[Tiered Pricing] $getQuantityField called for product ID:', parentId);

                var $quantityField;

                // Method 1: Try the plugin's expected class first
                $quantityField = $('.quantity-input-product-' + parentId);
                if ($quantityField.length > 0) {
                    console.log('[Tiered Pricing] Found quantity field by product ID class');
                    return $quantityField;
                }

                // Method 2: Find by our theme's standard classes
                $quantityField = $('.input-text.qty.text');
                if ($quantityField.length > 0) {
                    console.log('[Tiered Pricing] Found quantity field by theme classes');

                    // Add the expected class for future lookups
                    $quantityField.addClass('quantity-input-product-' + parentId);
                    return $quantityField;
                }

                // Method 3: Find by name attribute
                $quantityField = $('input[name="quantity"]');
                if ($quantityField.length > 0) {
                    console.log('[Tiered Pricing] Found quantity field by name attribute');

                    // Add both expected classes
                    $quantityField.addClass('qty input-text text quantity-input-product-' + parentId);
                    return $quantityField;
                }

                // Method 4: Look within the product wrapper
                $quantityField = $('.product .quantity input[type="number"]');
                if ($quantityField.length > 0) {
                    console.log('[Tiered Pricing] Found quantity field within product wrapper');

                    $quantityField.addClass('qty input-text text quantity-input-product-' + parentId);
                    return $quantityField;
                }

                console.error('[Tiered Pricing] Could not find quantity field!');
                return $();
            };

            /**
             * Override $getPriceContainer
             * This tells the plugin where to update the price display
             * We add the data attributes directly to existing elements to avoid breaking layout
             */
            document.__tieredPricing.overrides.$getPriceContainer = function(productId, parentId, productPageManager) {
                console.log('[Tiered Pricing] $getPriceContainer called');

                // Find the theme's price containers
                var $priceContainers = $('.product-header .product-price, .product-price');

                if ($priceContainers.length === 0) {
                    // Fallback to finding p.price directly
                    $priceContainers = $('p.price');
                }

                // Add data attributes to the containers without changing structure
                $priceContainers.each(function() {
                    var $this = $(this);
                    $this.attr('data-price-type', 'dynamic');
                    $this.attr('data-product-id', productId);
                    $this.attr('data-parent-id', parentId);
                });

                console.log('[Tiered Pricing] Found', $priceContainers.length, 'price containers');
                return $priceContainers;
            };

            /**
             * Override the updateProductPriceHTML method for all instances
             * This ensures price updates go to the correct element (p.price) not the container
             */
            setTimeout(function() {
                if (document.__tieredPricing.activeInstances && document.__tieredPricing.activeInstances.length > 0) {
                    console.log('[Tiered Pricing] Found', document.__tieredPricing.activeInstances.length, 'active instances');

                    document.__tieredPricing.activeInstances.forEach(function(instance) {
                        if (instance.productPageManager) {
                            var originalUpdatePrice = instance.productPageManager.updateProductPriceHTML;

                            instance.productPageManager.updateProductPriceHTML = function(priceHtml, skipDefault) {
                                console.log('[Tiered Pricing] Updating price with:', priceHtml);

                                // The plugin's method updates containers with data-price-type="dynamic"
                                // But we need to update the p.price element inside, not the container

                                // Find all p.price elements (theme uses this for price display)
                                // Update BOTH top and bottom price displays
                                var $priceElements = $('.product-header .product-price p.price, p.price');

                                if ($priceElements.length > 0) {
                                    $priceElements.html(priceHtml);
                                    console.log('[Tiered Pricing] Updated', $priceElements.length, 'p.price elements');
                                } else {
                                    // Fallback to updating .product-price containers
                                    $('.product-price').html(priceHtml);
                                    console.log('[Tiered Pricing] Updated .product-price containers');
                                }

                                console.log('[Tiered Pricing] Price updated successfully');
                            };
                        }
                    });
                }
            }, 1000);

            console.log('[Tiered Pricing] Overrides installed successfully');
            return true;
        }

        // Try to initialize immediately
        var initialized = initTieredPricingIntegration();

        // If plugin not ready, try again after a short delay
        if (!initialized) {
            setTimeout(function() {
                console.log('[Tiered Pricing] Retrying initialization...');
                initTieredPricingIntegration();
            }, 500);
        }

        /**
         * Add product ID class to quantity fields
         * This ensures the plugin can find the field using its default method
         */
        function addProductIdClassToQuantityFields() {
            $('.variations_form, .cart, .product').each(function() {
                var $form = $(this);
                var $quantityInput = $form.find('input[name="quantity"], .qty');

                if ($quantityInput.length > 0) {
                    // Get product ID from various sources
                    var productId = $form.find('input[name="product_id"]').val() ||
                                  $form.data('product_id') ||
                                  $form.data('product-id') ||
                                  $form.closest('.product').data('product-id') ||
                                  $('.tpt__tiered-pricing').data('product-id');

                    if (productId) {
                        console.log('[Tiered Pricing] Adding product class to quantity field for ID:', productId);
                        $quantityInput.addClass('quantity-input-product-' + productId);
                    }
                }
            });
        }

        // Initialize quantity field classes
        addProductIdClassToQuantityFields();

        /**
         * Show loading overlay when variation selection changes
         * But only if tier pricing container exists (meaning tiers are configured)
         */
        $('.variations_form').on('woocommerce_variation_select_change', function() {
            console.log('[Tiered Pricing] Variation selection changed');

            // Check if tier pricing container exists at all
            // If it doesn't exist, this product doesn't have tier pricing
            var $tierContainer = $('.tpt__tiered-pricing');

            if ($tierContainer.length > 0) {
                console.log('[Tiered Pricing] Tier pricing available, showing loading overlay');
                showLoadingOverlay();
            } else {
                console.log('[Tiered Pricing] No tier pricing configured for this product, skipping overlay');
            }
        });

        /**
         * Check if tier pricing is visible and loaded
         */
        function checkTierPricingLoaded() {
            // Check if tier pricing table/blocks/options are visible
            var $tierPricing = $('.tpt__tiered-pricing:visible');
            var $pricingElements = $tierPricing.find('.tiered-pricing-plain-texts, [data-tiered-pricing-table], .tiered-pricing-blocks, .tiered-pricing-options, .tiered-pricing-dropdown');

            console.log('[Tiered Pricing] Checking if loaded - found', $pricingElements.length, 'visible elements');

            return $pricingElements.length > 0 && $pricingElements.is(':visible');
        }

        // Store the check interval globally so we can clear it if needed
        var tierPricingCheckInterval = null;

        /**
         * Wait for tier pricing to become visible, then hide overlay
         * If no tier pricing exists, hide immediately
         */
        function waitForTierPricingOrHide(maxAttempts) {
            maxAttempts = maxAttempts || 20; // Max 2 seconds (20 * 100ms)
            var attempts = 0;

            // Clear any existing interval first
            if (tierPricingCheckInterval) {
                console.log('[Tiered Pricing] Clearing previous check interval');
                clearInterval(tierPricingCheckInterval);
                tierPricingCheckInterval = null;
            }

            tierPricingCheckInterval = setInterval(function() {
                attempts++;

                // Check if tier pricing exists and is visible
                var hasTierPricing = checkTierPricingLoaded();

                // Check if tpt__tiered-pricing container exists at all
                var $tierContainer = $('.tpt__tiered-pricing');
                var tierContainerExists = $tierContainer.length > 0;

                console.log('[Tiered Pricing] Attempt', attempts, '- Container exists:', tierContainerExists, '- Tier pricing visible:', hasTierPricing);

                if (hasTierPricing) {
                    // Tier pricing is loaded and visible - hide overlay
                    console.log('[Tiered Pricing] Tier pricing is visible, hiding overlay');
                    clearInterval(tierPricingCheckInterval);
                    tierPricingCheckInterval = null;
                    hideLoadingOverlay();
                } else if (!tierContainerExists || attempts >= maxAttempts) {
                    // No tier pricing container OR max attempts reached - hide overlay anyway
                    console.log('[Tiered Pricing] No tier pricing or timeout reached, hiding overlay');
                    clearInterval(tierPricingCheckInterval);
                    tierPricingCheckInterval = null;
                    hideLoadingOverlay();
                }
            }, 100); // Check every 100ms
        }

        /**
         * Re-initialize after variation changes
         */
        $('.variations_form').on('found_variation', function(event, variation) {
            console.log('[Tiered Pricing] Variation found, re-initializing');

            // Check if tier pricing container exists
            var $tierContainer = $('.tpt__tiered-pricing');

            // Only show loading if tier pricing is available
            if ($tierContainer.length > 0) {
                console.log('[Tiered Pricing] Tier pricing container found, showing overlay');

                // Show the tier pricing table if it was hidden
                $tierContainer.show();

                showLoadingOverlay();

                setTimeout(function() {
                    addProductIdClassToQuantityFields();

                    // Wait for tier pricing to load, or hide if it doesn't exist
                    waitForTierPricingOrHide();
                }, 100);
            } else {
                console.log('[Tiered Pricing] No tier pricing for this product');
                // Still need to add classes even without tier pricing
                setTimeout(function() {
                    addProductIdClassToQuantityFields();
                }, 100);
            }
        });

        /**
         * Handle variation reset - hide overlay after reset completes
         */
        $('.variations_form').on('reset_data', function() {
            console.log('[Tiered Pricing] Variation reset complete, hiding overlay');

            // Clear any running check interval
            if (tierPricingCheckInterval) {
                clearInterval(tierPricingCheckInterval);
                tierPricingCheckInterval = null;
            }

            // Hide the tier pricing table
            $('.tpt__tiered-pricing').hide();
            console.log('[Tiered Pricing] Tier pricing table hidden on reset');

            // Hide overlay after a short delay to ensure reset is complete
            setTimeout(function() {
                hideLoadingOverlay();
                addProductIdClassToQuantityFields();
            }, 100);
        });

        /**
         * Also handle the reset button click directly as a backup
         */
        $(document).on('click', '.reset_variations', function() {
            console.log('[Tiered Pricing] Reset button clicked, showing overlay');

            // Clear any running check interval
            if (tierPricingCheckInterval) {
                clearInterval(tierPricingCheckInterval);
                tierPricingCheckInterval = null;
            }

            // Show loading overlay during reset
            showLoadingOverlay();

            // Hide the tier pricing table
            $('.tpt__tiered-pricing').hide();
            console.log('[Tiered Pricing] Tier pricing table hidden on reset button click');
        });

        /**
         * Listen for tiered price updates
         * This is fired by the plugin when a tier is clicked
         */
        $(document).on('tiered_price_update', function(event, data) {
            console.log('[Tiered Pricing] Price update event received:', data);

            // Make sure our quantity field is synchronized
            if (data.quantity) {
                var $quantityField = $('.input-text.qty.text, input[name="quantity"]').first();
                if ($quantityField.length && $quantityField.val() != data.quantity) {
                    console.log('[Tiered Pricing] Syncing quantity field to:', data.quantity);
                    $quantityField.val(data.quantity);
                }

                // Update #product-option to show the selected quantity
                var quantityText = ' (Qty: ' + data.quantity + ')';
                var $productOption = $('#product-option');

                // Remove any existing quantity text
                var currentText = $productOption.text().replace(/\s*\(Qty:.*?\)/, '');

                // Add new quantity text
                if (data.quantity > 1) {
                    $productOption.text(currentText + quantityText);
                } else {
                    $productOption.text(currentText);
                }

                console.log('[Tiered Pricing] Updated #product-option with quantity:', data.quantity);
            }
        });

        /**
         * Re-initialize on AJAX cart updates
         */
        $(document.body).on('updated_wc_div updated_cart_totals', function() {
            console.log('[Tiered Pricing] Cart updated, re-initializing');
            addProductIdClassToQuantityFields();
        });

        console.log('[Tiered Pricing] Integration script initialized');
    });

})(jQuery);
