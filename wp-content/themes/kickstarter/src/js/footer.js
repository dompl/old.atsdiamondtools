// ==== FOOTER ==== //
(function ($) {
	$(function () {
		if ($.fn.remodal && $("[data-remodal-id='modal']").length > 0) {
			var inst = $("[data-remodal-id='modal']").remodal();
			inst.open();
		}
		// Newsletter signup
		$("#ats-newsletter form").submit(function (e) {
			e.preventDefault(); // Prevent the form from submitting the traditional way
			$("#ats-newsletter").removeClass("error").addClass("active");
			var formData = {
				action: "subscribe_to_newsletter", // The action hook to run on the server-side
				email: $('input[name="ats-nl-email"]').val(), // The email address from the form
				nonce: AtsNewsletter.nonce, // Security nonce
			};

			$.ajax({
				type: "POST",
				url: AtsNewsletter.ajax_url, // URL to WordPress AJAX handling file
				data: formData,
				success: function (response) {
					$("#ats-newsletter")
						.removeClass("error")
						.removeClass("active")
						.addClass("success")
						.html(
							"<h4>Welcome aboard! We're delighted to count you among our valued subscribers.</h4><p>Thank you for choosing ATS Diamond Tools.</p>"
						); // Display thank you message
				},
				error: function () {
					$("#ats-newsletter")
						.removeClass("active")
						.addClass("error") // Error handling
						.html("<p>There was an error. Please try again later.</p>");
				},
			});
		});

		function checkPasswordStrength(
			$pass1,
			$pass2,
			$strengthResult,
			$submitButton,
			blacklistArray
		) {
			var pass1 = $pass1.val();
			var pass2 = $pass2.val();
			// Reset the form & meter
			$submitButton.attr("disabled", "disabled");
			$strengthResult.removeClass("short bad good strong");
			// Extend our blacklist array with those from the inputs & site data
			blacklistArray = blacklistArray.concat(
				wp.passwordStrength.userInputBlacklist()
			);
			// Get the password strength
			var strength = wp.passwordStrength.meter(pass1, blacklistArray, pass2);
			// Add the strength meter results
			if (strength < 3) {
				$strengthResult.addClass("bad").html(pwsL10n.bad);
				$(".pw-weak").css("display", "block");
				$submitButton.attr("disabled", "disabled");
			} else {
				$strengthResult.addClass("strong").html(pwsL10n.strong);
				$(".pw-weak").css("display", "none");
				$submitButton.removeAttr("disabled");
			}
			// The meter function returns a result even if pass2 is empty,
			// enable only the submit button if the password is strong and
			// both passwords are filled up
			return strength;
		}
		jQuery(document).ready(function ($) {
			$("#customer_login .register").on(
				"keyup",
				"input[name=password], input[name=password_retyped]",
				function (event) {
					checkPasswordStrength(
						$("#reg_password"), // First password field
						$("input[name=password_retyped]"), // Second password field
						$("#password-strength"), // Strength meter
						$("input[type=submit]"), // Submit button
						["black", "listed", "word"] // Blacklisted words
					);
				}
			);
		});
		$(".pw-checkbox").on("click", function () {
			$('.woocommerce-FormRow input[type="submit"]').removeAttr("disabled");
		});
		$(".pw-weak input").click(function () {
			if (this.checked === true) {
				$(".register input[type=submit]").removeAttr("disabled");
			} else {
				$(".register input[type=submit]").attr("disabled", "disabled");
			}
		});
		/*  ********************************************************
		 *   Search Show : Hide
		 *  ********************************************************
		 */
		$("#reg_password").on("keyup", function () {
			console.log(pwsL10n);
		});

		function searach_toggle() {
			$(".search-toggle").click(function () {
				$(".search-section").stop().slideToggle("fast");
			});
		}
		searach_toggle();
		/*  ********************************************************
		 *   Match height general class
		 *  ********************************************************
		 */
		$(".mh").matchHeight();
		/*  ********************************************************
		 *   Cookie Bar
		 *   https://github.com/kiuz/jquery-cookie-bar/blob/master/index.html
		 *  ********************************************************
		 */
		$.cookieBar({
			message:
				'Please note our website uses cookies to improve your experience. <a class="cb-enable">I understand</a>. For more information see our ',
			policyText: "Privacy Statement & Cookie Notice",
			policyURL: "/privacy-statement", //URL of Privacy Policy
			policyButton: true, //Set to true to show Privacy Policy button
			acceptButton: false, //Set to true to show accept/enable button
			autoEnable: false, //Set to true for cookies to be accepted automatically. Banner still shows
			effect: "slide", //Options: slide, fade, hide
			declineButton: false, //Set to true to show decline/disable button
			declineText: "Disable Cookies", //Text on decline/disable button
			element: "body", //Element to append/prepend cookieBar to. Remember "." for class or "#" for id.
		});
		/*  ********************************************************
		 *   Slick Carousel settings
		 *  ********************************************************
		 */
		$(".home-products-container #products-list").slick({
			dots: false,
			infinite: true,
			speed: 300,
			arrows: true,
			slidesToShow: 4,
			slidesToScroll: 4,
			prevArrow: '<i class="icon-arrow-thin-left"></i>',
			nextArrow: '<i class="icon-arrow-thin-right"></i>',
			responsive: [
				{
					breakpoint: 1024,
					settings: {
						slidesToShow: 3,
						slidesToScroll: 3,
						infinite: true,
						dots: true,
					},
				},
				{
					breakpoint: 600,
					settings: {
						slidesToShow: 2,
						slidesToScroll: 2,
					},
				},
				{
					breakpoint: 480,
					settings: {
						slidesToShow: 1,
						slidesToScroll: 1,
					},
				},
			],
		});
		$("#home-slider-ul").slick({
			dots: false,
			infinite: true,
			speed: 300,
			arrows: true,
			slidesToShow: 1,
			slidesToScroll: 1,
			prevArrow: '<i class="icon-arrow-thin-left"></i>',
			nextArrow: '<i class="icon-arrow-thin-right"></i>',
		});
		$("#carousel").slick({
			dots: true,
			infinite: false,
			speed: 300,
			arrows: true,
			slidesToShow: 4,
			slidesToScroll: 4,
			prevArrow: '<i class="icon-arrow-thin-left"></i>',
			nextArrow: '<i class="icon-arrow-thin-right"></i>',
			responsive: [
				{
					breakpoint: 1024,
					settings: {
						slidesToShow: 3,
						slidesToScroll: 3,
						infinite: true,
						dots: true,
					},
				},
				{
					breakpoint: 600,
					settings: {
						slidesToShow: 2,
						slidesToScroll: 2,
					},
				},
				{
					breakpoint: 480,
					settings: {
						slidesToShow: 1,
						slidesToScroll: 1,
					},
				},
			],
		});
	});
})(jQuery);
