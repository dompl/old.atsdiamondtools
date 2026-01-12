<?php
/**
 * Tiered Pricing Table Plugin Integration
 *
 * Ensures compatibility between the Tiered Pricing Table plugin and the custom theme
 */

/**
 * Enqueue integration script for Tiered Pricing Table plugin
 */
function ats_enqueue_tiered_pricing_integration() {
    // Only load on product pages
    if (!is_product()) {
        return;
    }

    // Check if the Tiered Pricing plugin is active
    if (!wp_script_is('tiered-pricing-table-front-js', 'enqueued') &&
        !wp_script_is('tiered-pricing-table-front-js', 'registered')) {
        return;
    }

    // Enqueue our integration script
    // This must load AFTER the plugin's script
    wp_enqueue_script(
        'ats-tiered-pricing-integration',
        get_template_directory_uri() . '/src/js/woocommerce/single/tiered-pricing.js',
        array('jquery', 'tiered-pricing-table-front-js'),
        '1.0.6',
        true
    );

    // Add inline script to debug if needed
    $inline_script = "
        console.log('[ATS Theme] Tiered Pricing integration script enqueued');
    ";
    wp_add_inline_script('ats-tiered-pricing-integration', $inline_script, 'before');
}

// Hook into wp_enqueue_scripts with priority 20 (after plugin's priority 10)
add_action('wp_enqueue_scripts', 'ats_enqueue_tiered_pricing_integration', 20);

/**
 * Add custom CSS for tiered pricing styling
 */
function ats_tiered_pricing_custom_css() {
    if (!is_product()) {
        return;
    }

    ?>
    <style type="text/css">
        /* Tiered Pricing Table Integration Styles */
        .tiered-pricing-blocks tr,
        .tiered-pricing-options .tiered-pricing-option,
        .tiered-pricing-dropdown .tiered-pricing-dropdown-option,
        [data-tiered-pricing-table] tr,
        .tiered-pricing-block,
        .tiered-pricing-plain-text {
            cursor: pointer !important;
            transition: background-color 0.2s ease;
        }

        .tiered-pricing-blocks tr:hover,
        .tiered-pricing-options .tiered-pricing-option:hover,
        .tiered-pricing-dropdown .tiered-pricing-dropdown-option:hover,
        [data-tiered-pricing-table] tr:hover,
        .tiered-pricing-block:hover,
        .tiered-pricing-plain-text:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .tiered-pricing-blocks tr.tiered-pricing--active,
        .tiered-pricing-options .tiered-pricing-option.tiered-pricing--active,
        .tiered-pricing-dropdown .tiered-pricing-dropdown-option.tiered-pricing--active,
        [data-tiered-pricing-table] tr.tiered-pricing--active,
        .tiered-pricing-block.tiered-pricing--active,
        .tiered-pricing-plain-text.tiered-pricing--active {
            background-color: rgba(0, 120, 200, 0.1);
            font-weight: bold;
        }

        /* Make sure quantity input is always accessible */
        input[name="quantity"],
        .quantity input[type="number"] {
            pointer-events: auto !important;
            opacity: 1 !important;
        }

        /* Loading overlay for .main-left */
        .main-left {
            position: relative;
        }

        .tiered-pricing-loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.85);
            display: none !important;
            z-index: 1000;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }

        /* When shown, use flex display to center the spinner */
        .tiered-pricing-loading-overlay[style*="display: block"],
        .tiered-pricing-loading-overlay[style*="display: flex"] {
            display: flex !important;
        }

        /* Spinning wheel */
        .tiered-pricing-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(0, 0, 0, 0.1);
            border-top-color: #333;
            border-radius: 50%;
            animation: tiered-pricing-spin 0.8s linear infinite;
        }

        @keyframes tiered-pricing-spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'ats_tiered_pricing_custom_css', 100);
