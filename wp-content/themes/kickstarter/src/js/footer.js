// ==== FOOTER ==== //
;
(function($) {
		$(function() {

				function checkPasswordStrength($pass1,
						$pass2,
						$strengthResult,
						$submitButton,
						blacklistArray) {
						var pass1 = $pass1.val();
						var pass2 = $pass2.val();

						// Reset the form & meter
						$submitButton.attr('disabled', 'disabled');
						$strengthResult.removeClass('short bad good strong');

						// Extend our blacklist array with those from the inputs & site data
						blacklistArray = blacklistArray.concat(wp.passwordStrength.userInputBlacklist())

						// Get the password strength
						var strength = wp.passwordStrength.meter(pass1, blacklistArray, pass2);
						// Add the strength meter results
						switch (strength) {

								case 2:
										$strengthResult.addClass('bad').html(pwsL10n.bad);
										$('.pw-weak').css('display', 'block');
										$submitButton.attr('disabled', 'disabled');
										break;

								case 3:
										$strengthResult.addClass('good').html(pwsL10n.good);
										$('.pw-weak').css('display', 'none');
										$submitButton.removeAttr('disabled');
										break;

								case 4:
										$strengthResult.addClass('strong').html(pwsL10n.strong);
										$('.pw-weak').css('display', 'none');
										$submitButton.removeAttr('disabled');
										break;

								case 5:
										$strengthResult.addClass('short').html(pwsL10n.mismatch);
										$('.pw-weak').css('display', 'block');
										$submitButton.attr('disabled', 'disabled');
										break;

								default:
										$strengthResult.addClass('short').html(pwsL10n.short);
						}

						// The meter function returns a result even if pass2 is empty,
						// enable only the submit button if the password is strong and
						// both passwords are filled up

						return strength;
				}

				jQuery(document).ready(function($) {
						$('#customer_login .register').on('keyup', 'input[name=password], input[name=password_retyped]', function(event) {
								checkPasswordStrength(
										$('#reg_password'), // First password field
										$('input[name=password_retyped]'), // Second password field
										$('#password-strength'), // Strength meter
										$('input[type=submit]'), // Submit button
										['black', 'listed', 'word'] // Blacklisted words
								);
						});
				});

				$('.pw-weak input').click(function() {
						if (this.checked === true) {
								$('.register input[type=submit]').removeAttr('disabled');
						} else {
								$('.register input[type=submit]').attr('disabled', 'disabled');
						}
				});

				/*  ********************************************************
				 *   Search Show : Hide
				 *  ********************************************************
				 */
				function searach_toggle() {
						$('.search-toggle').click(function() {
								$(".search-section").stop().slideToggle("fast");
						});
				}
				searach_toggle();
				/*  ********************************************************
				 *   Match height general class
				 *  ********************************************************
				 */
				$('.mh').matchHeight();
				/*  ********************************************************
				 *   Sticky Navigation
				 *  ********************************************************
				 */
				// var stickyNavTop = $('#main-nav-container').offset().top;
				// var stickyNavheight = $('#main-nav-container').outerHeight();
				// var stickyNav = function() {
				// 		var scrollTop = $(window).scrollTop();
				// 		if (scrollTop > stickyNavTop) {
				// 				$('#main-nav-container').addClass('sticky-main-nav');
				// 				$('#header-middle').css('margin-bottom', stickyNavheight);
				// 		} else {
				// 				$('#main-nav-container').removeClass('sticky-main-nav');
				// 				$('#header-middle').css('margin-bottom', 0);
				// 		}
				// };
				// stickyNav();
				// $(window).scroll(function() {
				// 		stickyNav();
				// });
				/*  ********************************************************
				 *   LIttle function which will add class 'responsive to the footer'
				 *  ********************************************************
				 */
				// var footerHider = function() {
				// 		var windowWidth = $(window).width();
				// 		var ResponsiveState = $('#footer').data('responsive');
				// 		$('#footer .footer-item').not(':nth-child(3)').each(function() {
				// 				if (windowWidth < ResponsiveState) {
				// 						$(this).addClass('responsive');
				// 						$(this).find('h3').on('click', function() {
				// 								$('#footer .footer-item').find('ul').stop().slideUp();
				// 								$(this).next('ul').stop().slideToggle();
				// 						});
				// 				} else {
				// 						$(this).removeClass('responsive');
				// 						$('#footer .footer-item').find('ul').show();
				// 				}
				// 		});
				// };
				// footerHider();
				// $(window).resize(function() {
				// 		footerHider();
				// });
				/*  ********************************************************
				 *   Cookie Bar
				 *   https://github.com/kiuz/jquery-cookie-bar/blob/master/index.html
				 *  ********************************************************
				 */
				$.cookieBar({});
				/*  ********************************************************
				 *   Slick Carousel settings
				 *  ********************************************************
				 */
				$('.home-products-container #products-list').slick({
						dots: false,
						infinite: true,
						speed: 300,
						arrows: true,
						slidesToShow: 4,
						slidesToScroll: 4,
						prevArrow: '<i class="icon-arrow-thin-left"></i>',
						nextArrow: '<i class="icon-arrow-thin-right"></i>',
						responsive: [{
										breakpoint: 1024,
										settings: {
												slidesToShow: 3,
												slidesToScroll: 3,
												infinite: true,
												dots: true
										}
								},
								{
										breakpoint: 600,
										settings: {
												slidesToShow: 2,
												slidesToScroll: 2
										}
								},
								{
										breakpoint: 480,
										settings: {
												slidesToShow: 1,
												slidesToScroll: 1
										}
								}
						]
				});
				$('#home-slider-ul').slick({
						dots: false,
						infinite: true,
						speed: 300,
						arrows: true,
						slidesToShow: 1,
						slidesToScroll: 1,
						prevArrow: '<i class="icon-arrow-thin-left"></i>',
						nextArrow: '<i class="icon-arrow-thin-right"></i>',
				});
				$('#carousel').slick({
						dots: true,
						infinite: false,
						speed: 300,
						arrows: true,
						slidesToShow: 4,
						slidesToScroll: 4,
						prevArrow: '<i class="icon-arrow-thin-left"></i>',
						nextArrow: '<i class="icon-arrow-thin-right"></i>',
						responsive: [{
										breakpoint: 1024,
										settings: {
												slidesToShow: 3,
												slidesToScroll: 3,
												infinite: true,
												dots: true
										}
								}, {
										breakpoint: 600,
										settings: {
												slidesToShow: 2,
												slidesToScroll: 2
										}
								}, {
										breakpoint: 480,
										settings: {
												slidesToShow: 1,
												slidesToScroll: 1
										}
								}
								// You can unslick at a given breakpoint now by adding:
								// settings: "unslick"
								// instead of a settings object
						]
				});
		});
}(jQuery));
