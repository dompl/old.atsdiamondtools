(function($) {
	"use strict";
	
	$( document ).on( 'updated_checkout', function( data ) {
		
		if( WooRecaptchaPulicVar.attempts ) {
			var attempts = localStorage.getItem("woorecaptcha_checkout_attempts");
			if( attempts == null ) {
				attempts = 0;
			} else {
				attempts = parseInt(attempts);
			}
			if( attempts < WooRecaptchaPulicVar.attempts ) {
				return;
			}
		}

		var recaptcha_field_id = $('.woocommerce-checkout-payment').find('.g-recaptcha').attr('id');
		if( recaptcha_field_id != null ) {
			recaptcha_field_id = grecaptcha.render( recaptcha_field_id, {
				'sitekey' : WooRecaptchaPulicVar.sitekey,
				'theme' : WooRecaptchaPulicVar.theme,
				'size' : WooRecaptchaPulicVar.size
			});			
		}
	});

	$( document ).on( 'checkout_error', function( data ) {
		
		if( WooRecaptchaPulicVar.attempts ) {
			var attempts = localStorage.getItem("woorecaptcha_checkout_attempts");
			if( attempts == null ) {
				attempts = 1;
			} else {
				attempts = parseInt( attempts ) + 1;
			}
			localStorage.setItem("woorecaptcha_checkout_attempts",  attempts );
			
			// if attempts limit reached, render captcha 
			if( attempts == WooRecaptchaPulicVar.attempts ) {
				var recaptcha_field_id = $('.woocommerce-checkout-payment').find('.g-recaptcha').attr('id');
				if( recaptcha_field_id != null ) {
					recaptcha_field_id = grecaptcha.render( recaptcha_field_id, {
						'sitekey' : WooRecaptchaPulicVar.sitekey,
						'theme' : WooRecaptchaPulicVar.theme,
						'size' : WooRecaptchaPulicVar.size
					});			
				}
			}
		}
	});

})(jQuery);