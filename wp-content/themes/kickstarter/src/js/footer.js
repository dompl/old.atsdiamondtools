// ==== FOOTER ==== //
;(function($) {
		$(function() {
				/*  ********************************************************
				 *   Search Show : Hide
				 *  ********************************************************
				 */


				$('#reg_password').on('keyup', function() {
						console.log(pwsL10n);
				});


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
						}]
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
						}]
				});
		});
}(jQuery));
