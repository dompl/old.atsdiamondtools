(function($) {
	"use strict";
	
	woorecaptcha_display_checkout();
	
	function woorecaptcha_display_checkout() {
		
		if( $('#woo_recaptcha_checkout').is(':checked') ) {
			$('#woo_recaptcha_checkout_position').parents('tr').fadeIn();	
		} else {
			$('#woo_recaptcha_checkout_position').parents('tr').fadeOut();
		}
		$('#woo_recaptcha_checkout_position').trigger('change');
	}
	
	// on click of checkout display settings
	$( document ).on( 'click', '#woo_recaptcha_checkout', function() {		
		woorecaptcha_display_checkout();				
	});

	var selected_positon = $("#woo_recaptcha_checkout_position").val();	
	if( selected_positon == "before_place_order" && $('#woo_recaptcha_checkout').is(':checked') ) {
		$('#woo_recaptcha_display_after_attempts').parents('tr').fadeIn();
	} else {
		$('#woo_recaptcha_display_after_attempts').parents('tr').fadeOut();
	}

	$('#woo_recaptcha_checkout_position').on( 'change', function() {
		if( this.value == "before_place_order" && $('#woo_recaptcha_checkout').is(':checked') ) {
			$('#woo_recaptcha_display_after_attempts').parents('tr').fadeIn();
		} else {
			$('#woo_recaptcha_display_after_attempts').parents('tr').fadeOut();
		}
	});

})(jQuery);