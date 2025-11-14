<?php
/*-----------------------------------------------------------------------------------*/

/*	AG Fraud Checks
/*-----------------------------------------------------------------------------------*/

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || die( "No script kiddies please!" );

if( class_exists( 'ag_ePDQ_fraud_checks' ) ) {
	return;
}

class ag_ePDQ_fraud_checks {

	public static $single_instance = NULL;

	public static $args = array();

	// Industry statistics for context
	private static $industry_stats = array(
		'address_mismatch' => 15, // 15% of legitimate orders have address mismatch
		'postcode_mismatch' => 12, // 12% of legitimate orders have postcode mismatch
		'cvc_mismatch' => 8, // 8% of legitimate orders have CVC mismatch
		'no_3d_secure' => 25, // 25% of legitimate orders don't use 3D Secure
		'3d_secure_failed' => 5, // 5% of legitimate orders have 3D Secure failures
	);

	public function __construct() {

		add_action( 'admin_enqueue_scripts', array( $this, 'AG_fraud_css' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_order_check' ) );
		
		add_action( 'admin_footer', array( $this, 'output_modal' ) );

		// Catch when old version of Woo used.
		if( ! class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			AG_ePDQ_Helpers::ag_log( 'Your version of WooCommerce is out of date, please update your WooCommerce plugin to 7.+ this will allow the use of HPOS and our traffic light system', 'warning', 'yes' );
		}
		try {
			OrderUtil::custom_orders_table_usage_is_enabled();
		} catch ( Error $e ) {
			AG_ePDQ_Helpers::ag_log( 'Error: OrderUtil class or custom_orders_table_usage_is_enabled method is not available.', 'warning', 'yes' );

			return;
		}

		if( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			add_filter( 'woocommerce_shop_order_list_table_columns', array( $this, 'custom_shop_order_column_HPOS' ), 20 );
			add_action( 'woocommerce_shop_order_list_table_custom_column', array( $this, 'custom_orders_list_column_content_HPOS' ), 10, 2 );
		} else {
			add_filter( 'manage_edit-shop_order_columns', array( $this, 'custom_shop_order_column' ), 20 );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'custom_orders_list_column_content' ), 20, 2 );
		}

	}


	public function custom_shop_order_column_HPOS( $columns ) {

		if( ! defined( 'disable_ag_checks' ) ) {
			$columns['ag_tls'] = 'AG Traffic Light System';
		}

		return $columns;
	}

	public function custom_orders_list_column_content_HPOS( $column, $order ) {

		if( 'ag_tls' !== $column ) {
			return;
		}

		$display = ag_ePDQ_fraud_checks::get_order_details( $order );

		if( defined( 'disable_ag_checks' ) ) {
			return;
		}

		if( ! $order ) {
			return;
		}

		if( $order->get_status() === 'cancelled' || $order->get_status() === 'pending' ) {
			return;
		}

		// Is EPDQ order check
		if( $order->get_payment_method() !== 'epdq_checkout' ) {
			return;
		}

		// Is MOTO payment
		if( $order->get_meta( 'is_moto' ) ) {
			return;
		}

		if( ! $display ) {
			return;
		}

		$status = strtoupper( $order->get_meta( 'Status' ) );
		$fail_reason = $order->get_meta( 'FAIL_REASON' );
		
		// Compact display for orders list
		echo '<div class="fraud-check-compact">';
		
		// Check for system errors first
		if ( !empty( $fail_reason ) ) {
			$system_error = self::analyze_system_error( $fail_reason );
			if ( $system_error ) {
				// Show system error badge
				echo '<div class="liability-status system-error">⚠️ ' . $system_error['title'] . '</div>';
			} else {
				// Check if this is a payment failure
				if ( $status === 'DECLINED' || $status === 'FAILED' ) {
					$failure_risk = self::analyze_payment_failure( $fail_reason );
					if ( $failure_risk ) {
						// Show payment failure badge with appropriate color
						$badge_class = 'payment-failure-' . $failure_risk['risk_color'];
						echo '<div class="liability-status ' . $badge_class . '">';
						echo $failure_risk['risk_color'] === 'danger' ? '🚨 ' : ( $failure_risk['risk_color'] === 'warning' ? '⚠️ ' : 'ℹ️ ' );
						echo $failure_risk['title'];
						echo '</div>';
					} else {
						// Show generic payment failure badge
						echo '<div class="liability-status payment-failure">❌ Payment Failed</div>';
					}
				} else {
					// Show liability status for other payment errors
					if ( $status === 'APPROVED' || $status === 'AUTHORIZED' || $status === 'CAPTURED' ) {
						echo '<div class="liability-status protected">✅ Protected</div>';
					} else {
						echo '<div class="liability-status review">⚠️ Review</div>';
					}
				}
			}
		} else {
			// Show liability status for payment errors
			if ( $status === 'APPROVED' || $status === 'AUTHORIZED' || $status === 'CAPTURED' ) {
				echo '<div class="liability-status protected">✅ Protected</div>';
			} else {
				echo '<div class="liability-status review">⚠️ Review</div>';
			}
		}
		
		// Individual checks (smaller)
		echo '<div class="checks-compact">';
		echo( $display['address_code_display']['display'] );
		echo( $display['postal_code_display']['display']  );
		echo( $display['cvc_code_display']['display']  );
		echo( $display['Code_3DS_display']['display']  );
		echo '</div>';
		
		echo '</div>';
	}

	/**
	 * @param $args
	 *
	 * @return ag_ePDQ_fraud_checks|null
	 */

	public static function run_instance( $args = array() ) {

		if( self::$single_instance === NULL ) {
			self::$args = $args;
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * @return void
	 */
	public function add_order_check() {

		$screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';

		add_meta_box( 'ag_fraud_check', __( 'AG Traffic Light System', 'ag_epdq_server' ), array( $this, 'order_check_preview' ), $screen, 'side', 'core' );

	}

	/**
	 * @return void
	 */
	public function AG_fraud_css() {

		$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';

		if( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			if( $screen !== 'woocommerce_page_wc-orders' ) {
				return;
			}
		} else {
			if( $screen !== 'shop_order' ) {
				return;
			}
		}

		wp_enqueue_style( 'AG_fraud_css', AG_ePDQ_server_path . 'inc/assets/css/fraud-style.css', FALSE, AG_ePDQ_server::$AGversion );
		
		// Add inline CSS for tooltip positioning
		wp_add_inline_style( 'AG_fraud_css', '
			.checks-display .fraud-tooltip:first-child .fraud-tooltiptext {
				left: 150px !important;
			}
			.checks-display .fraud-tooltip:last-child .fraud-tooltiptext {
				left: -130px !important;
			}
		' );
	}

	/**
	 * @return void
	 */
	public function order_check_preview( $post_or_order_object ) {

		// Merchant has set to hide feature
		if( defined( 'disable_ag_checks' ) ) {
			return;
		}

		$order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;

		if( ! $order ) {
			return;
		}

		// Is firstdata order check
		if( $order->get_payment_method() !== 'epdq_checkout' ) {
			return;
		}

		if( $order->get_status() === 'cancelled' || $order->get_status() === 'pending' ) {
			return;
		}

		if( $order->get_meta( 'is_moto' ) ) {
			return;
		}

		$display = $this->get_order_details( $order );
		$fail_reason = $order->get_meta( 'FAIL_REASON' );
		$status = strtoupper( $order->get_meta( 'Status' ));
		$txntype = $order->get_meta( 'TXNTYPE' );

		if( $display ) {
			// Calculate risk score and get recommendations
			$risk_data = self::calculate_risk_score( $display, $status, $fail_reason );
			
			// Ensure failure_type is always defined to prevent warnings
			if ( !isset( $risk_data['failure_type'] ) ) {
				$risk_data['failure_type'] = '';
			}
			
			$recommendations = self::get_recommendations( $risk_data['failed_checks'], $risk_data['liability_shifted'], $risk_data['system_errors'], $risk_data['error_type'], $risk_data['failure_type'] );
			
			echo '<div class="fraud-check-container">';
			
			// Risk Score and Summary
			echo '<div class="risk-summary">';
			
			// Show risk score for payment errors and payment failures, not system errors
			if ( $risk_data['error_type'] === 'payment' || $risk_data['error_type'] === 'payment_failure' || ( $risk_data['error_type'] === 'system' && !empty( $risk_data['system_errors'] ) && $risk_data['system_errors'][0]['show_risk_score'] ) ) {
				echo '<div class="risk-score">';
				echo '<div class="risk-score-tooltip">';
				echo '<span class="risk-score-number">' . $risk_data['score'] . '</span>';
				echo '<span class="risk-score-tooltiptext">';
				echo '<strong>Risk Score: ' . $risk_data['score'] . '/100</strong><br>';
				echo '<strong>Risk Level:</strong> ' . ucfirst( $risk_data['risk_level'] ) . '<br><br>';
				echo '<strong>How it\'s calculated:</strong><br>';
				if ( $risk_data['error_type'] === 'payment_failure' ) {
					echo '• Payment failure risk: ' . $risk_data['score'] . ' points<br>';
					echo '• Based on failure type and bank response<br>';
				} else {
					echo '• Base risk: ' . ( $risk_data['liability_shifted'] ? '10' : '50' ) . ' points<br>';
					if ( !empty( $risk_data['failed_checks'] ) ) {
						foreach ( $risk_data['failed_checks'] as $check ) {
							switch ( $check ) {
								case 'address':
									echo '• Address mismatch: +15 points<br>';
									break;
								case 'postcode':
									echo '• Postcode mismatch: +12 points<br>';
									break;
								case 'cvc':
									echo '• CVC mismatch: +18 points<br>';
									break;
								case '3d_secure':
									echo '• 3D Secure failed: +20 points<br>';
									break;
								case '3d_secure_missing':
									echo '• 3D Secure missing: +10 points<br>';
									break;
							}
						}
					}
				}
				echo '<br><strong>Risk Levels:</strong><br>';
				echo '• 0-39: Low Risk (Green)<br>';
				echo '• 40-69: Medium Risk (Yellow)<br>';
				echo '• 70-100: High Risk (Red)<br><br>';
				if ( $risk_data['error_type'] === 'payment_failure' ) {
					echo '<strong>Note:</strong> This is a payment failure, not a fraud risk. The score indicates the severity of the payment issue.';
				} else {
					echo '<strong>Note:</strong> When payment is APPROVED, AUTHORIZED or CAPTURED liability shifts to the payment provider regardless of individual check results.';
				}
				echo '</span>';
				echo '</div>';
				echo '<span class="risk-score-label">Risk Score</span>';
				echo '</div>';
				echo '<div class="risk-level risk-level-' . $risk_data['risk_color'] . '">';
				echo '<span class="risk-level-text">' . ucfirst( $risk_data['risk_level'] ) . ' Risk</span>';
				echo '</div>';
				
				// Show liability status under risk level for payment errors
				if ( $risk_data['error_type'] === 'payment' && $risk_data['liability_shifted'] ) {
					echo '<div class="liability-shifted-badge">';
					echo '<span class="liability-shifted-text">✅ Liability Shifted</span>';
					echo '</div>';
				}
			}
			
			// Show system error badge for system errors
			if ( $risk_data['error_type'] === 'system' && !empty( $risk_data['system_errors'] ) ) {
				$system_error = $risk_data['system_errors'][0]; // Get first system error
				echo '<div class="system-error-badge">';
				echo '<span class="system-error-text">⚠️ ' . $system_error['title'] . '</span>';
				echo '</div>';
			}
			
			// Show payment failure badge for payment failures
			if ( $risk_data['error_type'] === 'payment_failure' && isset( $risk_data['failure_type'] ) && !empty( $risk_data['failure_type'] ) ) {
				$failure_info = self::get_payment_failure_info( $risk_data['failure_type'] );
				if ( $failure_info ) {
					echo '<div class="payment-failure-badge payment-failure-' . $failure_info['risk_color'] . '">';
					echo '<span class="payment-failure-text">';
					echo $failure_info['risk_color'] === 'danger' ? '🚨 ' : ( $failure_info['risk_color'] === 'warning' ? '⚠️ ' : 'ℹ️ ' );
					echo $failure_info['title'];
					echo '</span>';
					echo '</div>';
				}
			}
			
			echo '</div>';
			
			// Security Checks (for all error types)
			echo '<div class="checks-display">';
			echo '<h4>Security Checks:</h4>';
			echo( $display['address_code_display']['display'] );
			echo( $display['postal_code_display']['display'] );
			echo( $display['cvc_code_display']['display'] );
			echo( $display['Code_3DS_display']['display'] );
			echo '</div>';
			
			// System Error Details (if applicable)
			if ( $risk_data['error_type'] === 'system' && !empty( $risk_data['system_errors'] ) ) {
				$system_error = $risk_data['system_errors'][0];
				echo '<div class="system-error-details">';
				echo '<h4>System Error Details:</h4>';
				echo '<div class="system-error-info">';
				echo '<p><strong>Error:</strong> ' . $system_error['title'] . '</p>';
				echo '<p><strong>Description:</strong> ' . $system_error['description'] . '</p>';
				echo '<p><strong>Impact:</strong> ' . $system_error['impact'] . '</p>';
				if ( isset( $system_error['solution'] ) ) {
					echo '<p><strong>Solution:</strong> ' . $system_error['solution'] . '</p>';
				}
				
				// Show contact support information for unknown system errors
				if ( isset( $system_error['contact_support'] ) && $system_error['contact_support'] ) {
					echo '<div class="contact-support-section">';
					echo '<h5>🚨 Contact AG Support Required</h5>';
					echo '<p><strong>This is a new error type we haven\'t encountered before.</strong></p>';
					echo '<p>Please contact AG Support with the following details so we can add proper handling for this error:</p>';
					
					if ( isset( $system_error['support_details'] ) ) {
						echo '<div class="support-details">';
						echo '<h6>Details to Provide:</h6>';
						echo '<ul>';
						if ( !empty( $system_error['support_details']['status'] ) ) {
							echo '<li><strong>Status:</strong> ' . $system_error['support_details']['status'] . '</li>';
						}
						if ( !empty( $system_error['support_details']['fail_reason'] ) ) {
							echo '<li><strong>Fail Reason:</strong> ' . $system_error['support_details']['fail_reason'] . '</li>';
						}
						if ( !empty( $system_error['support_details']['order_id'] ) ) {
							echo '<li><strong>Order ID:</strong> ' . $system_error['support_details']['order_id'] . '</li>';
						}
						if ( !empty( $system_error['support_details']['plugin_version'] ) ) {
							echo '<li><strong>Plugin Version:</strong> ' . $system_error['support_details']['plugin_version'] . '</li>';
						}
						echo '</ul>';
						echo '</div>';
					}
					
					echo '<div class="support-contact">';
					echo '<p><strong>Contact Methods:</strong></p>';
					echo '<ul>';
					echo '<li>📧 Email: <a href="mailto:support@weareag.co.uk">support@weareag.co.uk</a></li>';
					echo '<li>🌐 Website: <a href="https://weareag.co.uk/contact/" target="_blank">weareag.co.uk/contact</a></li>';
					echo '<li>📞 Phone: Check your support documentation for contact details</li>';
					echo '</ul>';
					echo '</div>';
					echo '</div>';
				}
				
				echo '</div>';
				echo '</div>';
			}
			
			// Recommendations
			if ( !empty( $recommendations ) ) {
				echo '<div class="recommendations">';
				echo '<h4>Recommendations:</h4>';
				foreach ( $recommendations as $rec ) {
					echo '<div class="recommendation recommendation-' . $rec['type'] . '">';
					echo '<div class="recommendation-message">' . $rec['message'] . '</div>';
					if ( isset( $rec['action'] ) && $rec['action'] !== 'none' ) {
						echo '<div class="recommendation-action"><strong>Action:</strong> ' . $rec['action'] . '</div>';
					}
					if ( isset( $rec['context'] ) ) {
						echo '<div class="recommendation-context"><small>' . $rec['context'] . '</small></div>';
					}
					if ( isset( $rec['common_causes'] ) && !empty( $rec['common_causes'] ) ) {
						echo '<div class="recommendation-causes"><strong>Common Causes:</strong><ul>';
						foreach ( $rec['common_causes'] as $cause ) {
							echo '<li>' . $cause . '</li>';
						}
						echo '</ul></div>';
					}
					echo '</div>';
				}
				echo '</div>';
			}
			
			// Preauth Capture Reminder
			if ( $order->get_status() === 'on-hold' && $txntype === 'preauth' ) {
				echo '<div class="preauth-reminder">';
				echo '<div class="recommendation recommendation-warning">';
				echo '<div class="recommendation-message">';
				echo '<strong>⚠️ Payment Capture Required</strong><br>';
				echo 'This order is on hold with a pre-authorization. You must capture the payment to complete the transaction.';
				echo '</div>';
				echo '<div class="recommendation-action">';
				echo '<strong>Action:</strong> Go to the order actions and select "Capture payment" to complete the transaction.';
				echo '</div>';
				echo '<div class="recommendation-context">';
				echo '<small>Pre-authorizations expire after a certain period. Capture the payment to avoid losing the authorization.</small>';
				echo '</div>';
				echo '</div>';
				echo '</div>';
			}
			
			// Additional Information
			echo '<div class="additional-info">';
			if( ! empty( $status ) ) {
				echo '<span class="fraud-message"><strong>EPDQ Status: </strong> ' . $status . ' </span>';
			}
			if( ! empty( $fail_reason ) ) {
				echo '<br><hr> <span class="fraud-message"><strong>Fail Reason: </strong> ' . $fail_reason . ' </span>';
			}

			echo '</div>';
			
			// Help Links - Documentation
			echo '<div class="help-section">';
			echo '<div class="help-links">';
			echo '<h4>📚 Documentation</h4>';
			echo '<span class="fraud-message"><small><a href="https://weareag.co.uk/docs/barclays-epdq-payment-gateway/traffic-light-system-barclays-epdq-payment-gateway/setting-up-ag-order-check/" target="_blank">Learn more about this feature here.</a></small></span>';
			echo '</div>';
			echo '</div>';
			
			// Help Links - Check Explanations
			if ( $risk_data['error_type'] === 'payment' ) {
				echo '<div class="help-section">';
				echo '<div class="help-links">';
				echo '<h4>🔍 Check Details</h4>';
				echo '<span class="fraud-message"><small><a href="#" class="show-check-explanations">View detailed check explanations</a></small></span>';
				echo '</div>';
				echo '</div>';
			}
			
			// Help Links - Payment Failure Guidance
			if ( $risk_data['error_type'] === 'payment_failure' ) {
				echo '<div class="help-section">';
				echo '<div class="help-links">';
				echo '<h4>🔍 Check Explanations</h4>';
				echo '<span class="fraud-message"><small><a href="#" class="show-check-explanations">View detailed check explanations</a></small></span>';
				echo '</div>';
				echo '</div>';
			}
			
			// Help Links - Feature Requests
			echo '<div class="help-section">';
			echo '<div class="help-links">';
			echo '<h4>💡 Help Us Improve</h4>';
			echo '<span class="fraud-message"><small><a href="https://weareag.co.uk/feature-request/" target="_blank">Request new features or improvements</a></small></span>';
			echo '</div>';
			echo '</div>';
			
			// Store explanations data for modal (for payment errors and payment failures)
			if ( $risk_data['error_type'] === 'payment' || $risk_data['error_type'] === 'payment_failure' ) {
				$explanations = self::get_check_explanations();
				// Store in a global variable to access later
				global $ag_fraud_explanations;
				$ag_fraud_explanations = $explanations;
			}
			
			echo '</div>';
		}

		// Add inline JavaScript for fraud check functionality
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Handle modal popup for check explanations
			$(document).on('click', '.show-check-explanations', function(e) {
				e.preventDefault();
				$('#check-explanations-modal').fadeIn(300);
				$('body').addClass('modal-open');
			});
			
			// Close modal when clicking overlay or close button
			$(document).on('click', '.modal-overlay, .modal-close', function(e) {
				e.preventDefault();
				$('#check-explanations-modal').fadeOut(300);
				$('body').removeClass('modal-open');
			});
			
			// Close modal with Escape key
			$(document).on('keydown', function(e) {
				if (e.key === 'Escape' && $('#check-explanations-modal').is(':visible')) {
					$('#check-explanations-modal').fadeOut(300);
					$('body').removeClass('modal-open');
				}
			});
			
			// Prevent modal content clicks from closing modal
			$(document).on('click', '.modal-content', function(e) {
				e.stopPropagation();
			});
			
			// Add loading states for better UX
			$('.fraud-check-container').each(function() {
				var $container = $(this);
				
				// Add a subtle loading animation
				$container.addClass('loaded');
			});
		});
		</script>
		<?php

		return;

	}

	/**
	 * Output the modal at the end of the page
	 */
	public function output_modal() {
		global $ag_fraud_explanations;
		
		// Only output modal on relevant admin pages
		$screen = get_current_screen();
		if ( !$screen || ( $screen->id !== 'shop_order' && $screen->id !== 'woocommerce_page_wc-orders' ) ) {
			return;
		}

		
		// Only output modal if we have valid explanations data
		if ( !empty( $ag_fraud_explanations ) && is_array( $ag_fraud_explanations ) ) {
			// Validate that we have the required data structure
			$has_valid_data = false;
			$validation_errors = array();
			
			foreach ( $ag_fraud_explanations as $check_code => $explanation ) {
				if ( !is_array( $explanation ) ) {
					$validation_errors[] = "Check code {$check_code}: Not an array";
					continue;
				}
				
				$required_fields = array( 'title', 'description', 'how_it_works', 'what_it_means', 'common_causes' );
				foreach ( $required_fields as $field ) {
					if ( !isset( $explanation[$field] ) ) {
						$validation_errors[] = "Check code {$check_code}: Missing {$field}";
						continue 2;
					}
				}
				
				if ( !is_array( $explanation['what_it_means'] ) || empty( $explanation['what_it_means'] ) ) {
					$validation_errors[] = "Check code {$check_code}: what_it_means is not a valid array";
					continue;
				}
				
				if ( !is_array( $explanation['common_causes'] ) || empty( $explanation['common_causes'] ) ) {
					$validation_errors[] = "Check code {$check_code}: common_causes is not a valid array";
					continue;
				}
				
				$has_valid_data = true;
				break;
			}
			
			if ( !$has_valid_data ) {
				// Log validation errors for debugging

				
				// Clean up the global variable if data is invalid
				unset( $GLOBALS['ag_fraud_explanations'] );
				return; // Don't output modal if data is invalid
			}
			
			// Log successful modal output

			
			echo '<div id="check-explanations-modal" class="check-explanations-modal" style="display: none;">';
			echo '<div class="modal-overlay"></div>';
			echo '<div class="modal-content">';
			echo '<div class="modal-header">';
			echo '<h3>Understanding Each Check</h3>';
			echo '<button class="modal-close">&times;</button>';
			echo '</div>';
			
			// Add information about where to find this data
			echo '<div class="modal-info">';
			echo '<div class="info-box">';
			echo '<strong>📋 What you\'re looking at:</strong><br>';
			echo 'This is the fraud check data from your EPDQ payment processor. You can find this information in the "AG Traffic Light System" section when viewing any EPDQ order.';
			echo '</div>';
			echo '</div>';
			echo '<div class="modal-body">';
			
			foreach ( $ag_fraud_explanations as $check_code => $explanation ) {
				// Skip invalid explanation data (double-check for safety)
				if ( !is_array( $explanation ) || 
					 !isset( $explanation['title'] ) || 
					 !isset( $explanation['description'] ) || 
					 !isset( $explanation['how_it_works'] ) || 
					 !isset( $explanation['what_it_means'] ) || 
					 !isset( $explanation['common_causes'] ) ||
					 !is_array( $explanation['what_it_means'] ) ||
					 !is_array( $explanation['common_causes'] ) ||
					 empty( $explanation['what_it_means'] ) ||
					 empty( $explanation['common_causes'] ) ) {
					continue;
				}
				
				echo '<div class="check-explanation">';
				echo '<h5>' . esc_html( $explanation['title'] ) . ' (' . esc_html( $check_code ) . ')</h5>';
				echo '<p><strong>What it does:</strong> ' . esc_html( $explanation['description'] ) . '</p>';
				echo '<p><strong>How it works:</strong> ' . esc_html( $explanation['how_it_works'] ) . '</p>';
				
				echo '<div class="explanation-results">';
				echo '<strong>What the results mean:</strong>';
				echo '<ul>';
				foreach ( $explanation['what_it_means'] as $code => $meaning ) {
					// Show all codes as styled badges for consistency
					echo '<li><span class="result-code">' . esc_html( $code ) . '</span> ' . esc_html( $meaning ) . '</li>';
				}
				echo '</ul>';
				echo '</div>';
				
				echo '<div class="explanation-causes">';
				echo '<strong>Common causes of mismatches:</strong>';
				echo '<ul>';
				foreach ( $explanation['common_causes'] as $cause ) {
					echo '<li>' . esc_html( $cause ) . '</li>';
				}
				echo '</ul>';
				echo '</div>';
				echo '</div>';
			}
			
			echo '</div>';
			echo '</div>';
			echo '</div>';
			
			// Clean up the global variable after output
			unset( $GLOBALS['ag_fraud_explanations'] );
		}
	}

	/**
	 * @param $my_var_one
	 * @param $order
	 *
	 * @return array|void
	 */
	public static function get_order_details( $order ) {

		
		$Code_3DS = 'EMPTY';
		$address_code = 'EMPTY';
		$postal_code = 'EMPTY';
		$cvc_code = 'EMPTY';
		$code_aav = 'EMPTY';

		if( empty( $order ) ) {
			return;
		}

		$keys = array_column( $order->get_meta_data(), 'key' );
		$search_address_check = array_search( 'AAVADDRESS', $keys, TRUE );
		$search_postal_check = array_search( 'AAVZIP', $keys, TRUE );
		$search_CVC_check = array_search( 'CVCCheck', $keys, TRUE );
		$search_3D_check = array_search( 'ECI', $keys, TRUE );
		$search_aav_check = array_search( 'AAVCheck', $keys, TRUE );

		if( $search_aav_check ) {
			$code_aav = $order->get_meta_data()[ $search_aav_check ]->value;
			$address_code_display = self::approval_code_display( 'A', $code_aav );
		} else {
			$address_code_display = self::approval_code_display( 'A', $code_aav );
		}

		if( $search_address_check ) {
			$address_code = $order->get_meta_data()[ $search_address_check ]->value;
			$address_code_display = self::approval_code_display( 'A', $address_code );
		} else {
			$address_code_display = self::approval_code_display( 'A', $address_code );
		}

		if( $search_postal_check ) {
			$postal_code = $order->get_meta_data()[ $search_postal_check ]->value;
			$postal_code_display = self::approval_code_display( 'P', $postal_code );
		} else {
			$postal_code_display = self::approval_code_display( 'P', $postal_code );
		}

		if( $search_3D_check ) {
			$Code_3DS = $order->get_meta_data()[ $search_3D_check ]->value;
			$Code_3DS_display = self::secure3D_display( $Code_3DS );
		} else {
			$Code_3DS_display = self::secure3D_display( $Code_3DS );
		}

		if( $search_CVC_check ) {
			$cvc_code = $order->get_meta_data()[ $search_CVC_check ]->value;
			$cvc_code_display = self::approval_code_display( 'C', $cvc_code );
		} else {
			$cvc_code_display = self::approval_code_display( 'C', $cvc_code );
		}

		return [
			'address_code_display' => $address_code_display,
			'postal_code_display'  => $postal_code_display,
			'cvc_code_display'     => $cvc_code_display,
			'Code_3DS_display'     => $Code_3DS_display
		];

	}

	/**
	 * @param $approval_code
	 *
	 * @return string[]
	 */
	public static function approval_code_display(  $type, $result ) {

		$message = 'No data was returned';
		$display = self::display_tooltip( $type, 0 );

		switch ( $type ) {
			case 'A' :
				if( $result === 'OK' ) {
					$message = __( 'The Address is Correct', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 1 );
				} elseif( $result === 'KO' ) {
					$message = __( 'The address has been sent but the acquirer has given a negative response for the address check.', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 0 );
				} elseif( $result === 'NO' ) {
					$message = __( 'Invalid or no Address has been transmitted.', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 0 );
				} else {
					$message = 'Parameters for this Address has not been set.';
					$display = self::display_tooltip( $type, 0 );
				}
				break;
			case 'P':
				if( $result === 'OK' ) {
					$message = __( 'The Postal Code is Correct', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 1 );
				} elseif( $result === 'KO' ) {
					$message = __( 'The Postal Code has been sent but the acquirer has given a negative response for the address check.', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 0 );
				} elseif( $result === 'NO' ) {
					$message = __( 'Invalid or no Postal Code has been transmitted.', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 0 );
				} else {
					$message = __( 'Parameters for Postal Code has not been set.', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 0 );
				}
				break;
			case 'C':
				if( $result === 'OK' ) {
					$message = __( 'The CVC has been sent and the acquirer has given a positive response to the CVC check', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 1 );
				} elseif( $result === 'KO' ) {
					$message = __( 'The CVC has been sent but the acquirer has given a negative response to the CVC check, i.e. the CVC is wrong.', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 0 );
				} elseif( $result === 'NO' ) {
					$message = __( 'Invalid or no CVC has been transmitted', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 0 );
				} else {
					$message = __( 'Parameters for CVC has not been set.', 'ag_epdq_server' );
					$display = self::display_tooltip( $type, 0 );
				}
				break;
		}

		return [
			'display' => $display,
			'message' => '<strong>' . $type . ': </strong> ' . $message,

		];
	}

	/**
	 * @param $type
	 * @param $status
	 *
	 * @return string
	 */
	public static function display_tooltip( $type, $status ) {

		$explanations = self::get_check_explanations();
		$explanation = isset( $explanations[$type] ) ? $explanations[$type] : null;
		
		switch ( $type ) {
			case 'A':
				$tooltip = $explanation ? $explanation['description'] : __( 'Address Check', 'ag_epdq_server' );
				break;
			case 'P':
				$tooltip = $explanation ? $explanation['description'] : __( 'Postcode check', 'ag_epdq_server' );
				break;
			case 'C':
				$tooltip = $explanation ? $explanation['description'] : __( 'CVC check', 'ag_epdq_server' );
				break;
			case '3':
				$tooltip = $explanation ? $explanation['description'] : __( '3D Secure check', 'ag_epdq_server' );
				break;
		}
		
		// Error = 0, Success = 1, Warning = 2
		if( $status === 1 ) {
			$tooltip_style = ( $type === 'A' ) ? ' style="left: 0px;"' : '';
			return '<div class="fraud-tooltip">
                <span class="fraud-badge-success" >' . $type . '</span>
                <span class="fraud-tooltiptext"' . $tooltip_style . '>' . $tooltip . '</span>
                </div>';

		} elseif( $status === 2 ) {
			$tooltip_style = ( $type === 'A' ) ? ' style="left: 0px;"' : '';
			return '<div class="fraud-tooltip">
                <span class="fraud-badge-warning" >' . $type . '</span>
                <span class="fraud-tooltiptext"' . $tooltip_style . '>' . $tooltip . '</span>
                </div>';

		} elseif( $status === 3 ) {
			$tooltip_style = ( $type === 'A' ) ? ' style="left: 0px;"' : '';
			return '<div class="fraud-tooltip">
				<span class="fraud-badge-sub" >' . $type . '</span>
				<span class="fraud-tooltiptext"' . $tooltip_style . '>' . $tooltip . '</span>
				</div>';

		} else {
			$tooltip_style = ( $type === 'A' ) ? ' style="left: 0px;"' : '';
			return '<div class="fraud-tooltip">
                <span class="fraud-badge-danger" >' . $type . '</span>
                <span class="fraud-tooltiptext"' . $tooltip_style . '>' . $tooltip . '</span>
                </div>';
		}
	}

	/**
	 * @param $Code_3DS
	 *
	 * @return array
	 */
		public static function secure3D_display( $Code_3DS ) {

		switch ( $Code_3DS ) {
			case 1:
				$display = self::display_tooltip( '3', 0 );
				$message = __( 'Manually keyed (MOTO) (card not present)', 'ag_epdq_server' );
				break;
			case 2:
				$display = self::display_tooltip( '3', 3 );
				$message = __( 'Recurring (from MOTO)', 'ag_epdq_server' );
				break;
			case 3:
				$display = self::display_tooltip( '3', 2 );
				$message = __( 'Installment payments', 'ag_epdq_server' );
				break;
			case 4:
				$display = self::display_tooltip( '3', 2 );
				$message = __( 'Manually keyed, card present', 'ag_epdq_server' );
				break;
			case 5:
				$display = self::display_tooltip( '3', 1 );
				$message = __( 'Cardholder identification successful', 'ag_epdq_server' );
				break;
			case 6:
				$display = self::display_tooltip( '3', 2 );
				$message = __( 'Merchant supports identification but not cardholder,', 'ag_epdq_server' );
				break;
			case 7:
				$display = self::display_tooltip( '3', 0 );
				$message = __( 'E-commerce with SSL encryption', 'ag_epdq_server' );
				break;
			case 9:
				$display = self::display_tooltip( '3', 3 );
				$message = __( 'Recurring (from e-commerce)', 'ag_epdq_server' );
				break;
			case 'EMPTY':
				$display = self::display_tooltip( '3', 0 );
				$message = __( 'Parameters for 3D has not been set.', 'ag_epdq_server' );
				break;
			default:
				$display = self::display_tooltip( '3', 0 );
				$message = "No data was returned";
		}

		return [
			'display' => $display,
			'message' => '<strong> 3D: </strong> ' . $message
		];
	}

	/**
	 * Calculate fraud risk score (0-100) based on all checks
	 * @param array $display_data
	 * @param string $status
	 * @param string $fail_reason
	 * @return array
	 */
	public static function calculate_risk_score( $display_data, $status, $fail_reason = '' ) {
		$score = 0;
		$failed_checks = array();
		$system_errors = array();
		
		// Check for system errors first (these override normal scoring)
		if ( !empty( $fail_reason ) ) {
			$system_error = self::analyze_system_error( $fail_reason );
			if ( $system_error ) {
				$system_errors[] = $system_error;
				$score = $system_error['risk_score'];
				$failed_checks = $system_error['failed_checks'];
				
				return array(
					'score' => $score,
					'risk_level' => $system_error['risk_level'],
					'risk_color' => $system_error['risk_color'],
					'failed_checks' => $failed_checks,
					'system_errors' => $system_errors,
					'liability_shifted' => false, // System errors mean no liability shift
					'error_type' => 'system',
					'failure_type' => '' // Empty string for system errors
				);
			}
		}
		
		// Handle payment failures (DECLINED, FAILED statuses)
		if ( $status === 'DECLINED' || $status === 'FAILED' ) {
			// Analyze the specific failure reason for better risk assessment
			$failure_risk = self::analyze_payment_failure( $fail_reason );
			$score = $failure_risk['risk_score'];
			$failed_checks = $failure_risk['failed_checks'];
			
			return array(
				'score' => $score,
				'risk_level' => $failure_risk['risk_level'],
				'risk_color' => $failure_risk['risk_color'],
				'failed_checks' => $failed_checks,
				'system_errors' => array(),
				'liability_shifted' => false,
				'error_type' => 'payment_failure',
				'failure_type' => $failure_risk['failure_type']
			);
		}
		
		// If status is approved, authorized, or captured, liability is shifted - lower base risk
		$base_risk = ( $status === 'APPROVED' || $status === 'AUTHORIZED' || $status === 'CAPTURED' ) ? 10 : 50;
		$score = $base_risk;
		
		// Check address verification
		if ( strpos( $display_data['address_code_display']['message'], 'does not match' ) !== false ) {
			$score += 15;
			$failed_checks[] = 'address';
		}
		
		// Check postcode verification
		if ( strpos( $display_data['postal_code_display']['message'], 'does not match' ) !== false ) {
			$score += 12;
			$failed_checks[] = 'postcode';
		}
		
		// Check CVC verification
		if ( strpos( $display_data['cvc_code_display']['message'], 'does not match' ) !== false ) {
			$score += 18;
			$failed_checks[] = 'cvc';
		}
		
		// Check 3D Secure
		$message_3d = $display_data['Code_3DS_display']['message'];
		if ( strpos( $message_3d, 'Authentication failed' ) !== false || 
			 strpos( $message_3d, 'Unable to authenticate' ) !== false ||
			 strpos( $message_3d, 'Cardholder not enrolled' ) !== false ) {
			$score += 20;
			$failed_checks[] = '3d_secure';
		} elseif ( strpos( $message_3d, 'No data was returned' ) !== false ) {
			$score += 10;
			$failed_checks[] = '3d_secure_missing';
		}
		
		// Cap score at 100
		$score = min( $score, 100 );
		
		// Determine risk level
		$risk_level = 'low';
		$risk_color = 'success';
		
		if ( $score >= 70 ) {
			$risk_level = 'high';
			$risk_color = 'danger';
		} elseif ( $score >= 40 ) {
			$risk_level = 'medium';
			$risk_color = 'warning';
		}
		
		return array(
			'score' => $score,
			'risk_level' => $risk_level,
			'risk_color' => $risk_color,
			'failed_checks' => $failed_checks,
			'system_errors' => $system_errors,
			'liability_shifted' => ( $status === 'APPROVED' || $status === 'AUTHORIZED' || $status === 'CAPTURED' ),
			'error_type' => 'payment',
			'failure_type' => '' // Empty string for payment errors (not payment failures)
		);
	}

	/**
	 * Analyze system errors and provide appropriate risk assessment
	 * @param string $fail_reason
	 * @return array|null
	 */
	public static function analyze_system_error( $fail_reason ) {
		// First, check if this is a legitimate payment failure (not a system error)
		$payment_failure_patterns = array(
			// Standard payment processor responses
			'transaction declined',
			'card declined',
			'insufficient funds',
			'card expired',
			'invalid card',
			'card not supported',
			'3d secure authentication failed',
			'3d secure failed',
			'authentication failed',
			'authorization failed',
			'payment declined',
			'declined',
			'failed',
			'rejected',
			'not authorised',
			'not authorized',
			'cardholder authentication',
			'fraudulent',
			'suspicious',
			'risk',
			'security',
			'verification failed',
			'address verification failed',
			'cvv failed',
			'cvc failed',
			'postcode verification failed',
			'do not honor',
			'pick up card',
			'stolen card',
			'lost card',
			'card error',
			'processing error',
			'bank error',
			'issuer error',
			'card issuer',
			'bank declined',
			'issuer declined',
			'cardholder',
			'customer',
			'fraud',
			'fraudulent',
			'suspicious activity',
			'high risk',
			'security check',
			'verification',
			'card verification',
			'address verification',
			'cvv verification',
			'cvc verification',
			'postcode verification',
			'zip verification',
			'postal code verification',
			
			// EPDQ-specific payment failure patterns
			'fraud security',
			'cancelled by user',
			'contact issuer',
			'addit authen req',
			'card expired',
			'txn not allowed',
			'unable to get payer authentication',
			'life cycle',
			'auth prohibited',
			'cancelled',
			'user cancelled',
			'payer authentication',
			'3d secure',
			'3ds',
			'secure authentication',
			'issuer',
			'card issuer',
			'bank',
			'authentication required',
			'authorization required',
			'fraud',
			'security',
			'declined',
			'not allowed',
			'prohibited',
			'lifecycle',
			'expired',
			'expiry',
			'expiration',
			'duplicate transaction',
			'duplicate'
		);
		
		$fail_reason_lower = strtolower( $fail_reason );
		
		// If it matches payment failure patterns, return null (not a system error)
		foreach ( $payment_failure_patterns as $pattern ) {
			if ( strpos( $fail_reason_lower, $pattern ) !== false ) {

				return null; // This is a payment failure, not a system error
			}
		}
		
		// Now check for actual system errors (these should be rare)
		$error_patterns = array(
			'order already exists' => array(
				'risk_score' => 85,
				'risk_level' => 'high',
				'risk_color' => 'danger',
				'failed_checks' => array('duplicate_order'),
				'error_type' => 'duplicate_order',
				'title' => 'Duplicate Order Detected',
				'description' => 'This order already exists in the payment system database.',
				'impact' => 'High risk of duplicate processing or system conflict.',
				'show_risk_score' => false, // Don't show risk score for system errors
				'solution' => 'Install a WooCommerce Order ID Prefix plugin to prevent duplicate order IDs.',
				'common_causes' => array(
					'Customer submitted the same order multiple times',
					'Network timeout causing duplicate submissions',
					'Browser refresh during payment process',
					'System synchronization issues',
					'Order ID conflicts between WooCommerce and payment processor'
				)
			),
			'invalid merchant' => array(
				'risk_score' => 95,
				'risk_level' => 'high',
				'risk_color' => 'danger',
				'failed_checks' => array('merchant_config'),
				'error_type' => 'merchant_config',
				'title' => 'Merchant Configuration Error',
				'description' => 'Invalid merchant credentials or configuration.',
				'impact' => 'Payment processing is blocked due to account issues.',
				'show_risk_score' => false,
				'solution' => 'Verify your merchant credentials and account status with your payment processor.',
				'common_causes' => array(
					'Incorrect merchant ID or credentials',
					'Account suspended or inactive',
					'Configuration changes not properly saved',
					'API endpoint misconfiguration'
				)
			),
			'invalid amount' => array(
				'risk_score' => 75,
				'risk_level' => 'high',
				'risk_color' => 'danger',
				'failed_checks' => array('amount_validation'),
				'error_type' => 'amount_validation',
				'title' => 'Invalid Transaction Amount',
				'description' => 'The payment amount is invalid or outside acceptable limits.',
				'impact' => 'Transaction rejected due to amount validation failure.',
				'show_risk_score' => false,
				'solution' => 'Review the order amount and currency settings. Ensure amounts are within your processing limits.',
				'common_causes' => array(
					'Zero or negative amount submitted',
					'Amount exceeds merchant limits',
					'Currency mismatch or formatting issues',
					'Decimal precision errors'
				)
			),
			'timeout' => array(
				'risk_score' => 60,
				'risk_level' => 'medium',
				'risk_color' => 'warning',
				'failed_checks' => array('system_timeout'),
				'error_type' => 'system_timeout',
				'title' => 'System Timeout',
				'description' => 'The payment request timed out before completion.',
				'impact' => 'Transaction status uncertain - may have been processed.',
				'show_risk_score' => false,
				'solution' => 'Check if the payment was actually processed despite the timeout. Contact payment processor to verify transaction status.',
				'common_causes' => array(
					'Slow network connection',
					'Payment processor server issues',
					'Customer took too long to complete payment',
					'System overload or maintenance'
				)
			),
			'connection error' => array(
				'risk_score' => 65,
				'risk_level' => 'medium',
				'risk_color' => 'warning',
				'failed_checks' => array('connection_error'),
				'error_type' => 'connection_error',
				'title' => 'Connection Error',
				'description' => 'Unable to connect to the payment processor.',
				'impact' => 'Transaction could not be processed due to connectivity issues.',
				'show_risk_score' => false,
				'solution' => 'Check your internet connection and firewall settings. Try processing the payment again.',
				'common_causes' => array(
					'Internet connectivity issues',
					'Payment processor server down',
					'Firewall or security software blocking connection',
					'DNS resolution problems'
				)
			),
			// Add more specific system error patterns here if needed
			'system error' => array(
				'risk_score' => 80,
				'risk_level' => 'high',
				'risk_color' => 'danger',
				'failed_checks' => array('system_error'),
				'error_type' => 'system_error',
				'title' => 'System Error',
				'description' => 'A system error occurred during payment processing.',
				'impact' => 'Transaction failed due to system issues.',
				'show_risk_score' => false,
				'solution' => 'Contact your payment processor support with the error details.',
				'common_causes' => array(
					'Payment processor system issues',
					'Configuration problems',
					'Network connectivity issues',
					'Technical problems'
				)
			)
		);
		
		foreach ( $error_patterns as $pattern => $error_info ) {
			if ( strpos( $fail_reason_lower, $pattern ) !== false ) {
				// Log when a system error is correctly identified

				return $error_info;
			}
		}

		
		// If we reach here, it might be a genuine unknown system error
		// Check if it has characteristics that suggest it's a system error rather than payment failure
		$system_error_indicators = array(
			'system',
			'error',
			'technical',
			'configuration',
			'setup',
			'initialization',
			'plugin',
			'woocommerce',
			'wordpress',
			'database',
			'api',
			'gateway',
			'processor',
			'connection',
			'timeout',
			'invalid',
			'missing',
			'failed to',
			'unable to',
			'could not',
			'cannot',
			'error code',
			'error message'
		);
		
		$has_system_indicators = false;
		foreach ( $system_error_indicators as $indicator ) {
			if ( strpos( $fail_reason_lower, $indicator ) !== false ) {
				$has_system_indicators = true;
				break;
			}
		}
		
		// If it has system error indicators, treat it as an unknown system error
		if ( $has_system_indicators ) {
			// Get the current order status for better context
			global $post;
			$current_status = '';
			if ( $post && $post->post_type === 'shop_order' ) {
				$order = wc_get_order( $post->ID );
				if ( $order ) {
					$current_status = strtoupper( $order->get_meta( 'Status' ));
				}
			}
			
			return self::handle_unknown_system_error( $fail_reason, $current_status );
		}
		
		return null;
	}

	/**
	 * Analyze payment failures and provide appropriate risk assessment
	 * @param string $fail_reason
	 * @return array
	 */
	public static function analyze_payment_failure( $fail_reason ) {
		$fail_reason_lower = strtolower( $fail_reason );
		
		// Define different types of payment failures with appropriate risk levels
		$failure_patterns = array(
			// High risk - potential fraud indicators
			'fraud security' => array(
				'risk_score' => 85,
				'risk_level' => 'high',
				'risk_color' => 'danger',
				'failed_checks' => array('fraud_security'),
				'failure_type' => 'fraud_security',
				'title' => 'Fraud Security Alert',
				'description' => 'Payment declined due to fraud security measures.',
				'impact' => 'High risk transaction blocked by security systems.',
				'recommendation' => 'Review order details and customer information. Consider manual review.',
				'common_causes' => array(
					'Suspicious transaction patterns',
					'High-risk customer behavior',
					'Unusual order characteristics',
					'Multiple failed attempts'
				)
			),
			
			// Medium risk - authentication/verification issues
			'3d secure authentication failed' => array(
				'risk_score' => 65,
				'risk_level' => 'medium',
				'risk_color' => 'warning',
				'failed_checks' => array('3d_secure_failed'),
				'failure_type' => '3d_secure_failed',
				'title' => '3D Secure Authentication Failed',
				'description' => 'Customer failed to complete 3D Secure authentication.',
				'impact' => 'Payment declined due to authentication failure.',
				'recommendation' => 'Ask customer to try again with correct credentials.',
				'common_causes' => array(
					'Incorrect password or PIN',
					'Expired authentication codes',
					'Customer not enrolled in 3D Secure',
					'Bank authentication system issues'
				)
			),
			'unable to get payer authentication' => array(
				'risk_score' => 60,
				'risk_level' => 'medium',
				'risk_color' => 'warning',
				'failed_checks' => array('payer_authentication'),
				'failure_type' => 'payer_authentication',
				'title' => 'Payer Authentication Unavailable',
				'description' => 'Unable to complete payer authentication process.',
				'impact' => 'Payment declined due to authentication unavailability.',
				'recommendation' => 'Try a different card or payment method.',
				'common_causes' => array(
					'Card not enrolled in 3D Secure',
					'Bank authentication system down',
					'Technical connectivity issues',
					'Card issuer restrictions'
				)
			),
			'addit authen req' => array(
				'risk_score' => 55,
				'risk_level' => 'medium',
				'risk_color' => 'warning',
				'failed_checks' => array('additional_authentication'),
				'failure_type' => 'additional_authentication',
				'title' => 'Additional Authentication Required',
				'description' => 'Bank requires additional authentication steps.',
				'impact' => 'Payment declined until additional verification.',
				'recommendation' => 'Customer should contact their bank for verification.',
				'common_causes' => array(
					'Bank security policies',
					'Unusual transaction patterns',
					'High-value transactions',
					'International transactions'
				)
			),
			
			// Lower risk - standard card issues
			'card expired' => array(
				'risk_score' => 30,
				'risk_level' => 'low',
				'risk_color' => 'success',
				'failed_checks' => array('card_expired'),
				'failure_type' => 'card_expired',
				'title' => 'Card Expired',
				'description' => 'Payment card has expired.',
				'impact' => 'Payment declined due to expired card.',
				'recommendation' => 'Ask customer to use a different card.',
				'common_causes' => array(
					'Card past expiration date',
					'Customer unaware of expiration',
					'Old card information stored'
				)
			),
			'contact issuer' => array(
				'risk_score' => 40,
				'risk_level' => 'low',
				'risk_color' => 'success',
				'failed_checks' => array('contact_issuer'),
				'failure_type' => 'contact_issuer',
				'title' => 'Contact Card Issuer',
				'description' => 'Bank requires customer to contact them.',
				'impact' => 'Payment declined by bank policy.',
				'recommendation' => 'Customer should contact their bank.',
				'common_causes' => array(
					'Bank security holds',
					'Account verification required',
					'Unusual spending patterns',
					'Bank policy restrictions'
				)
			),
			'not authorised' => array(
				'risk_score' => 45,
				'risk_level' => 'low',
				'risk_color' => 'success',
				'failed_checks' => array('not_authorized'),
				'failure_type' => 'not_authorized',
				'title' => 'Payment Not Authorized',
				'description' => 'Bank declined to authorize the payment.',
				'impact' => 'Payment declined by card issuer.',
				'recommendation' => 'Customer should contact their bank.',
				'common_causes' => array(
					'Insufficient funds',
					'Card spending limits',
					'Bank security policies',
					'Account restrictions'
				)
			),
			'txn not allowed' => array(
				'risk_score' => 35,
				'risk_level' => 'low',
				'risk_color' => 'success',
				'failed_checks' => array('transaction_not_allowed'),
				'failure_type' => 'transaction_not_allowed',
				'title' => 'Transaction Not Allowed',
				'description' => 'This type of transaction is not permitted.',
				'impact' => 'Payment declined due to transaction restrictions.',
				'recommendation' => 'Try a different payment method.',
				'common_causes' => array(
					'Card type restrictions',
					'Merchant category restrictions',
					'Bank policy limitations',
					'International transaction blocks'
				)
			),
			'life cycle' => array(
				'risk_score' => 40,
				'risk_level' => 'low',
				'risk_color' => 'success',
				'failed_checks' => array('life_cycle'),
				'failure_type' => 'life_cycle',
				'title' => 'Card Lifecycle Issue',
				'description' => 'Card has reached end of lifecycle.',
				'impact' => 'Payment declined due to card status.',
				'recommendation' => 'Customer should use a different card.',
				'common_causes' => array(
					'Card replaced by bank',
					'Account closed or suspended',
					'Card deactivated',
					'Bank maintenance issues'
				)
			),
			'auth prohibited' => array(
				'risk_score' => 50,
				'risk_level' => 'medium',
				'risk_color' => 'warning',
				'failed_checks' => array('auth_prohibited'),
				'failure_type' => 'auth_prohibited',
				'title' => 'Authorization Prohibited',
				'description' => 'Bank prohibits authorization for this transaction.',
				'impact' => 'Payment declined by bank policy.',
				'recommendation' => 'Customer should contact their bank.',
				'common_causes' => array(
					'Bank security policies',
					'Account restrictions',
					'Fraud prevention measures',
					'Regulatory compliance'
				)
			),
			
			// User-initiated cancellations
			'cancelled by user' => array(
				'risk_score' => 20,
				'risk_level' => 'low',
				'risk_color' => 'success',
				'failed_checks' => array('user_cancelled'),
				'failure_type' => 'user_cancelled',
				'title' => 'Cancelled by User',
				'description' => 'Customer cancelled the payment process.',
				'impact' => 'Payment cancelled by customer choice.',
				'recommendation' => 'No action required - customer chose to cancel.',
				'common_causes' => array(
					'Customer changed mind',
					'Found better price elsewhere',
					'Technical difficulties',
					'Payment method issues'
				)
			),
			
			// Duplicate transaction handling
			'duplicate transaction' => array(
				'risk_score' => 35,
				'risk_level' => 'low',
				'risk_color' => 'success',
				'failed_checks' => array('duplicate_transaction'),
				'failure_type' => 'duplicate_transaction',
				'title' => 'Duplicate Transaction',
				'description' => 'Payment declined due to duplicate transaction detection.',
				'impact' => 'Payment blocked to prevent duplicate processing.',
				'recommendation' => 'If testing: Wait 2-3 minutes between attempts. If customer: Check if previous payment was successful.',
				'common_causes' => array(
					'Merchant testing multiple payments too quickly',
					'Customer clicking submit button multiple times',
					'Network timeout causing customer to retry',
					'Browser refresh during payment process',
					'Previous payment already processed successfully'
				)
			)
		);
		
		// Check for specific failure patterns
		foreach ( $failure_patterns as $pattern => $failure_info ) {
			if ( strpos( $fail_reason_lower, $pattern ) !== false ) {

				return $failure_info;
			}
		}
		
		// Default for unknown payment failures - treat as medium risk
		return array(
			'risk_score' => 50,
			'risk_level' => 'medium',
			'risk_color' => 'warning',
			'failed_checks' => array('unknown_payment_failure'),
			'failure_type' => 'unknown_payment_failure',
			'title' => 'Payment Failed',
			'description' => 'Payment was declined for an unknown reason.',
			'impact' => 'Payment could not be processed.',
			'recommendation' => 'Customer should contact their bank or try a different payment method.',
			'common_causes' => array(
				'Bank declined payment',
				'Card restrictions',
				'Account issues',
				'Technical problems'
			)
		);
	}

	/**
	 * Get actionable recommendations for failed checks
	 * @param array $failed_checks
	 * @param bool $liability_shifted
	 * @param array $system_errors
	 * @param string $error_type
	 * @param string $failure_type
	 * @return array
	 */
	public static function get_recommendations( $failed_checks, $liability_shifted, $system_errors = array(), $error_type = 'payment', $failure_type = '' ) {
		$recommendations = array();
		
		// Handle system errors first
		if ( $error_type === 'system' && !empty( $system_errors ) ) {
			foreach ( $system_errors as $error ) {
				$recommendations[] = array(
					'type' => 'danger',
					'message' => $error['title'] . ': ' . $error['description'],
					'action' => isset( $error['solution'] ) ? $error['solution'] : self::get_system_error_action( $error['error_type'] ),
					'context' => 'Impact: ' . $error['impact'],
					'common_causes' => $error['common_causes']
				);
				
				// Add special recommendation for unknown system errors
				if ( isset( $error['contact_support'] ) && $error['contact_support'] ) {
					$recommendations[] = array(
						'type' => 'warning',
						'message' => '🚨 Contact AG Support Required - This is a new error type we haven\'t encountered before.',
						'action' => 'Contact AG Support with the Status and Fail Reason details so we can add proper handling for this error type.',
						'context' => 'This will help us improve the plugin and prevent future occurrences.',
						'common_causes' => array(
							'New error type from payment processor',
							'System configuration changes',
							'Payment processor updates',
							'Unknown technical issues'
						)
					);
				}
			}
			return $recommendations;
		}
		
		// Handle payment failures
		if ( $error_type === 'payment_failure' && !empty( $failure_type ) ) {
			$failure_info = self::get_payment_failure_info( $failure_type );
			if ( $failure_info ) {
				$recommendations[] = array(
					'type' => $failure_info['risk_color'] === 'danger' ? 'danger' : ( $failure_info['risk_color'] === 'warning' ? 'warning' : 'info' ),
					'message' => $failure_info['title'] . ': ' . $failure_info['description'],
					'action' => $failure_info['recommendation'],
					'context' => 'Impact: ' . $failure_info['impact'],
					'common_causes' => $failure_info['common_causes']
				);
			}
			return $recommendations;
		}
		
		if ( $liability_shifted ) {
			$recommendations[] = array(
				'type' => 'success',
				'message' => 'Payment approved - liability shifted to payment provider. No action required.',
				'action' => 'none'
			);
		}
		
		foreach ( $failed_checks as $check ) {
			switch ( $check ) {
				case 'address':
					$recommendations[] = array(
						'type' => 'info',
						'message' => 'Address mismatch detected. ' . self::$industry_stats['address_mismatch'] . '% of legitimate orders have address mismatches.',
						'action' => 'Contact customer to verify billing address details',
						'context' => 'Common causes: recent moves, apartment numbers, business addresses'
					);
					break;
					
				case 'postcode':
					$recommendations[] = array(
						'type' => 'info',
						'message' => 'Postcode mismatch detected. ' . self::$industry_stats['postcode_mismatch'] . '% of legitimate orders have postcode mismatches.',
						'action' => 'Contact customer to confirm correct postal code',
						'context' => 'Common causes: postal code format differences, recent address changes'
					);
					break;
					
				case 'cvc':
					$recommendations[] = array(
						'type' => 'warning',
						'message' => 'CVC mismatch detected. ' . self::$industry_stats['cvc_mismatch'] . '% of legitimate orders have CVC mismatches.',
						'action' => 'Ask customer to check and re-enter the 3-digit security code',
						'context' => 'Common causes: worn cards, customer confusion about CVC location'
					);
					break;
					
				case '3d_secure':
					$recommendations[] = array(
						'type' => 'warning',
						'message' => '3D Secure authentication failed. ' . self::$industry_stats['3d_secure_failed'] . '% of legitimate orders have 3D Secure issues.',
						'action' => 'Contact customer to try payment again or use a different card',
						'context' => 'Common causes: bank authentication issues, customer not enrolled'
					);
					break;
					
				case '3d_secure_missing':
					$recommendations[] = array(
						'type' => 'info',
						'message' => '3D Secure not attempted. ' . self::$industry_stats['no_3d_secure'] . '% of legitimate orders don\'t use 3D Secure.',
						'action' => 'Monitor for other risk indicators',
						'context' => 'Common causes: card not enrolled, merchant not requiring 3D Secure'
					);
					break;
			}
		}
		
		return $recommendations;
	}

	/**
	 * Get specific action recommendations for system errors
	 * @param string $error_type
	 * @return string
	 */
	public static function get_system_error_action( $error_type ) {
		$actions = array(
			'duplicate_order' => 'Install a WooCommerce Order ID Prefix plugin to prevent duplicate order IDs. Check for duplicate orders in your system and contact customer to confirm if they intended to place multiple orders.',
			'merchant_config' => 'Verify your merchant credentials and account status. Contact your payment processor support if issues persist.',
			'amount_validation' => 'Review the order amount and currency settings. Ensure amounts are within your processing limits.',
			'system_timeout' => 'Check if the payment was actually processed despite the timeout. Contact payment processor to verify transaction status.',
			'connection_error' => 'Check your internet connection and firewall settings. Try processing the payment again.',
			'system_error' => 'Contact your payment processor support with the error details. Document the issue for troubleshooting.',
			'unknown_system_error' => '🚨 Contact AG Support with the Status and Fail Reason details so we can add proper handling for this error type. This will help us improve the plugin and prevent future occurrences.'
		);
		
		return isset( $actions[$error_type] ) ? $actions[$error_type] : 'Contact support for assistance with this system error.';
	}

	/**
	 * Get detailed explanation of each check
	 * @return array
	 */
	public static function get_check_explanations() {
		return array(
			'A' => array(
				'title' => 'Address Verification (AVS)',
				'description' => 'Checks if the billing address provided matches the address on file with the card issuer.',
				'how_it_works' => 'The payment processor compares the street address and house number with the cardholder\'s registered address.',
				'what_it_means' => array(
					'OK' => 'Address matches exactly - low fraud risk',
					'KO' => 'The address has been sent but the acquirer has given a negative response for the address check. - moderate risk',
					'NO' => 'Invalid or no Address has been transmitted. - moderate risk',
					'' => 'Address information unavailable - cannot assess risk'
				),
				'common_causes' => array(
					'Recent address changes not updated with bank',
					'Apartment numbers or unit numbers missing',
					'Business addresses vs personal addresses',
					'International address format differences'
				)
			),
			'P' => array(
				'title' => 'Postal Code Verification',
				'description' => 'Verifies that the postal code provided matches the one associated with the card.',
				'how_it_works' => 'The payment processor checks the postal code against the cardholder\'s registered postal code.',
				'what_it_means' => array(
					'Y' => 'Postal code matches - low fraud risk',
					'N' => 'Postal code does not match - may indicate fraud or error',
					'U' => 'Postal code information unavailable - cannot assess risk'
				),
				'common_causes' => array(
					'Postal code format differences (e.g., 12345 vs 12345-6789)',
					'Recent moves not updated with bank',
					'International postal code formats',
					'Customer confusion about billing vs shipping address'
				)
			),
			'C' => array(
				'title' => 'Card Verification Code (CVC/CVV)',
				'description' => 'Validates the 3-4 digit security code on the back (or front) of the card.',
				'how_it_works' => 'The payment processor verifies the CVC code with the card issuer to confirm the card is physically present.',
				'what_it_means' => array(
					'M' => 'CVC matches - card likely present, low fraud risk',
					'N' => 'CVC does not match - may indicate stolen card data',
					'P' => 'CVC not processed - risk assessment incomplete',
					'S' => 'CVC not present on card - may be legitimate for some card types',
					'U' => 'CVC information unavailable - cannot assess risk'
				),
				'common_causes' => array(
					'Worn or damaged cards with unreadable CVC',
					'Customer confusion about CVC location',
					'Virtual cards or digital wallets',
					'International cards with different CVC formats'
				)
			),
			'3D' => array(
				'title' => '3D Secure Authentication',
				'description' => 'Additional security layer that requires cardholder authentication through their bank.',
				'how_it_works' => 'The customer is redirected to their bank\'s authentication page to verify their identity using passwords, SMS codes, or biometrics.',
				'what_it_means' => array(
					'1' => 'Successful authentication - highest security level',
					'2' => 'Successful authentication without AVV - still secure',
					'3' => 'Authentication failed - incorrect credentials',
					'4' => 'Authentication attempted - partial verification',
					'5' => 'Unable to authenticate - technical issues',
					'6' => 'Unable to authenticate - server issues',
					'7' => 'Cardholder not enrolled - cannot use 3D Secure',
					'8' => 'Invalid 3D Secure values - technical error'
				),
				'common_causes' => array(
					'Customer not enrolled in 3D Secure program',
					'Bank authentication system issues',
					'Incorrect passwords or expired codes',
					'Technical connectivity problems',
					'International cards with different 3D Secure requirements'
				)
			)
		);
	}

	/**
	 * @param $columns
	 *
	 * @return array
	 */

	public function custom_shop_order_column( $columns ) {

		$reordered_columns = array();

		foreach( $columns as $key => $column ) {
			$reordered_columns[ $key ] = $column;

			if( $key === 'order_status' ) {
				if( ! defined( 'disable_ag_checks' ) ) {
					$reordered_columns['checks'] = __( 'AG Order Checks', 'ag_epdq_server' );
				}
			}
		}

		return $reordered_columns;
	}

	/**
	 * @param $column
	 * @param $post_id
	 *
	 * @return void
	 */
	public function custom_orders_list_column_content( $column, $post_id ) {

		switch ( $column ) {
			case 'checks' :

				$order = wc_get_order( $post_id );
				$display = $this->get_order_details( $order );

				if( defined( 'disable_ag_checks' ) ) {
					return;
				}

				if( ! $order ) {
					return;
				}

				// Is EPDQ order check
				if( $order->get_payment_method() !== 'epdq_checkout' ) {
					return;
				}

				if( $order->get_status() === 'cancelled' || $order->get_status() === 'pending' ) {
					return;
				}

				// Is MOTO payment
				if( $order->get_meta( 'is_moto' ) ) {
					return;
				}

				if( ! $display ) {
					return;
				}

				$status = strtoupper( $order->get_meta( 'Status' ));
				$fail_reason = $order->get_meta( 'FAIL_REASON' );
				
				// Compact display for orders list
				echo '<div class="fraud-check-compact">';
				
				// Check for system errors first
				if ( !empty( $fail_reason ) ) {
					$system_error = self::analyze_system_error( $fail_reason );
					if ( $system_error ) {
						// Show system error badge
						echo '<div class="liability-status system-error">⚠️ ' . $system_error['title'] . '</div>';
					} else {
						// Check if this is a payment failure
						if ( $status === 'DECLINED' || $status === 'FAILED' ) {
							$failure_risk = self::analyze_payment_failure( $fail_reason );
							if ( $failure_risk ) {
								// Show payment failure badge with appropriate color
								$badge_class = 'payment-failure-' . $failure_risk['risk_color'];
								echo '<div class="liability-status ' . $badge_class . '">';
								echo $failure_risk['risk_color'] === 'danger' ? '🚨 ' : ( $failure_risk['risk_color'] === 'warning' ? '⚠️ ' : 'ℹ️ ' );
								echo $failure_risk['title'];
								echo '</div>';
							} else {
								// Show generic payment failure badge
								echo '<div class="liability-status payment-failure">❌ Payment Failed</div>';
							}
						} else {
							// Show liability status for other payment errors
							if ( $status === 'APPROVED' || $status === 'AUTHORIZED' || $status === 'CAPTURED' ) {
								echo '<div class="liability-status protected">✅ Protected</div>';
							} else {
								echo '<div class="liability-status review">⚠️ Review</div>';
							}
						}
					}
				} else {
					// Show liability status for payment errors
					if ( $status === 'APPROVED' || $status === 'AUTHORIZED' || $status === 'CAPTURED' ) {
						echo '<div class="liability-status protected">✅ Protected</div>';
					} else {
						echo '<div class="liability-status review">⚠️ Review</div>';
					}
				}
				
				// Individual checks (smaller)
				echo '<div class="checks-compact">';
				echo $display['address_code_display']['display'];
				echo $display['postal_code_display']['display'];
				echo $display['cvc_code_display']['display'];
				echo $display['Code_3DS_display']['display'];
				echo '</div>';
				
				echo '</div>';

				break;

		}
	}

	/**
	 * Get payment failure information for a specific failure type
	 * @param string $failure_type
	 * @return array|null
	 */
	public static function get_payment_failure_info( $failure_type ) {
		// Safety check for empty or invalid failure type
		if ( empty( $failure_type ) || !is_string( $failure_type ) ) {
			return null;
		}
		
		$failure_patterns = array(
			'fraud_security' => array(
				'risk_color' => 'danger',
				'title' => 'Fraud Security Alert',
				'description' => 'Payment declined due to fraud security measures.',
				'impact' => 'High risk transaction blocked by security systems.',
				'recommendation' => 'Review order details and customer information. Consider manual review.',
				'common_causes' => array(
					'Suspicious transaction patterns',
					'High-risk customer behavior',
					'Unusual order characteristics',
					'Multiple failed attempts'
				)
			),
			'auth_prohibited' => array(
				'risk_color' => 'warning',
				'title' => 'Authorization Prohibited',
				'description' => 'Bank prohibits authorization for this transaction.',
				'impact' => 'Payment declined by bank policy.',
				'recommendation' => 'Customer should contact their bank.',
				'common_causes' => array(
					'Bank security policies',
					'Account restrictions',
					'Fraud prevention measures',
					'Regulatory compliance'
				)
			),
			'user_cancelled' => array(
				'risk_color' => 'success',
				'title' => 'Cancelled by User',
				'description' => 'Customer cancelled the payment process.',
				'impact' => 'Payment cancelled by customer choice.',
				'recommendation' => 'No action required - customer chose to cancel.',
				'common_causes' => array(
					'Customer changed mind',
					'Found better price elsewhere',
					'Technical difficulties',
					'Payment method issues'
				)
			),
			'duplicate_transaction' => array(
				'risk_color' => 'success',
				'title' => 'Duplicate Transaction',
				'description' => 'Payment declined due to duplicate transaction detection.',
				'impact' => 'Payment blocked to prevent duplicate processing.',
				'recommendation' => 'If testing: Wait 2-3 minutes between attempts. If customer: Check if previous payment was successful.',
				'common_causes' => array(
					'Merchant testing multiple payments too quickly',
					'Customer clicking submit button multiple times',
					'Network timeout causing customer to retry',
					'Browser refresh during payment process',
					'Previous payment already processed successfully'
				)
			),
			'unknown_payment_failure' => array(
				'risk_color' => 'warning',
				'title' => 'Payment Failed',
				'description' => 'Payment was declined for an unknown reason.',
				'impact' => 'Payment could not be processed.',
				'recommendation' => 'Customer should contact their bank or try a different payment method.',
				'common_causes' => array(
					'Bank declined payment',
					'Card restrictions',
					'Account issues',
					'Technical problems'
				)
			)
		);
		
		return isset( $failure_patterns[$failure_type] ) ? $failure_patterns[$failure_type] : null;
	}

	/**
	 * Handle unknown system errors and provide guidance for contacting support
	 * @param string $fail_reason
	 * @param string $status
	 * @return array
	 */
	public static function handle_unknown_system_error( $fail_reason, $status ) {

		return array(
			'risk_score' => 90,
			'risk_level' => 'high',
			'risk_color' => 'danger',
			'failed_checks' => array('unknown_system_error'),
			'error_type' => 'unknown_system_error',
			'title' => 'Unknown System Error',
			'description' => 'An unexpected system error occurred that we haven\'t encountered before.',
			'impact' => 'Transaction failed due to an unknown system issue that requires investigation.',
			'show_risk_score' => false,
			'solution' => 'Contact AG Support with the Status and Fail Reason details so we can add proper handling for this error type.',
			'common_causes' => array(
				'New error type from payment processor',
				'System configuration changes',
				'Payment processor updates',
				'Unknown technical issues'
			),
			'contact_support' => true,
			'support_details' => array(
				'status' => $status,
				'fail_reason' => $fail_reason,
				'order_id' => 'Current Order ID',
				'plugin_version' => defined( 'AG_ePDQ_server::$AGversion' ) ? AG_ePDQ_server::$AGversion : 'Unknown'
			)
		);
	}

}