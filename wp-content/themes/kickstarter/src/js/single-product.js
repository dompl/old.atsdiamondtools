// ==== Single product JS ==== //
;
(function($) {
		$(function() {
				/* Prudyct Manipulation */
				//  $('body').hide();
				$('.variations select').blur(function() {
						var VarioationID = $('input.variation_id').val();

						if ('' != VarioationID) {

								var variationName = $(this).find('option:selected').text();
								var variationsArray = $("form.variations_form").data("product_variations");

								$.each(variationsArray, function(n) {
										if (variationsArray[n].variation_id == VarioationID) {

												// console.log(variationsArray[n]);
												/* Product SKU */
												var atsSKU = variationsArray[n].sku;
												var atsVarDescription = variationsArray[n].variation_description;

												if ('' != atsSKU) {
														$('#var_sku').text(atsSKU);
														var atsCaptionSku = ' (SKU: ' + atsSKU + ') ';
												} else {
														$('#var_sku').text('N/A');
														var atsCaptionSku = '';
												}

												/* Variation image */

												var atsVarImage = variationsArray[n].image; // Returns array
												// console.log(atsVarImage);
												if ('' != atsVarImage && variationsArray[n].image_id != $('.prod-main-image').data('image-id')) {
														$('.variation-image').remove();
														// $('.variation - wrapper
														$('.variation-wrapper').addClass('has-variation');
														atsVarImageHTML = '<div class="variation-image"><a href="' + atsVarImage.url + '" data-lightbox="variation-image-' + VarioationID + '" data-title="' + $('.product-header h2').text() + ' - ' + variationName + atsCaptionSku + atsVarImage.caption + '"><img src="' + atsVarImage.src + '"></a></div>';
														$('#variation_short').before(atsVarImageHTML);
														$('.variation-wrapper').addClass('has-variation');
														$('.variation-image').addClass('has-description');
														if ('' != atsVarDescription) {
																$('.variation-image').addClass('has-description');
														} else {
																$('.variation-image').removeClass('has-description');

														}
														$('#variation_short').addClass('has-variation-image');
												} else {
														$('.variation-image').remove();
														$('#variation_short').removeClass('has-variation-image');
														$('.variation-image').removeClass('has-description');
												}
												/* Variation Desciption */
												if ('' != atsVarDescription) {
														$('.variation-wrapper').addClass('has-variation');
														var atsVarDescriptionHTML = '<div class="variation-title"><h3>' + variationName + '</h3><div class="variation-description">' + atsVarDescription + '</div></div>';
														$('#variation_short').addClass('has-variation-description').html(atsVarDescriptionHTML);
												} else {
														$('#variation_short').removeClass('has-variation-description').html('');
														$('.variation-wrapper').removeClass('has-variation');
												}

												var atsVarStock = variationsArray[n].is_in_stock;
												var atsVarRegPrice = variationsArray[n].display_regular_price;
												// console.log(atsVarRegPrice);
												if (false == atsVarStock || '' == atsVarRegPrice) {
														var OutOfStock = '<div class="not-in-stock">Sorry, ' + $('.product-header h2').text() + ' - <strong>(' + variationName + ')</strong> is currently out of stock</p>';
														$('#variation_stock').addClass('out-of-stcok').html(OutOfStock);
												} else {
														$('#variation_stock').removeClass('out-of-stcok').html('');
												}

										}
								});

								$('p.price').html($('div.woocommerce-variation-price > span.price').html());

								/* Variation Name */
								$('#product-option').text(variationName);

						} else {
								$('p.price').html($('div.hidden-variable-price').html());
								$('#var_sku').text('Per Variation');
								if ($('p.availability')) $('p.availability').remove();

								/* Variation Name */
								$('#product-option').text('');
								/* Variation description */
								$('#variation_short').empty();
								/* Variation Image */
								$('.variation-image').remove();
								$('#variation_short').removeClass('has-variation-image');
								$('.variation-wrapper').removeClass('has-variation');
						}
				});


				/* lightbox */
				lightbox.option({
						'resizeDuration': 200,
						'wrapAround': true
				});
				/* Product gallery */
				if ($('.product__slider-main').length) {
						var $slider = $('.product__slider-main').on('init', function(slick) {
								$('.product__slider-main').fadeIn(1000);
						}).slick({
								slidesToShow: 1,
								slidesToScroll: 1,
								arrows: true,
								autoplay: false,
								fade: true,
								lazyLoad: 'ondemand',
								autoplaySpeed: 3000,
								adaptiveHeight: true,
								prevArrow: '<i class="icon-arrow-thin-left"></i>',
								nextArrow: '<i class="icon-arrow-thin-right"></i>',
								asNavFor: '.product__slider-thmb'
						});
						var $slider2 = $('.product__slider-thmb').on('init', function(slick) {
								$('.product__slider-thmb').fadeIn(1000);
						}).slick({
								slidesToShow: 5,
								slidesToScroll: 1,
								lazyLoad: 'ondemand',
								asNavFor: '.product__slider-main',
								dots: false,
								centerMode: true,
								focusOnSelect: true,
								prevArrow: '<i class="icon-arrow-thin-left"></i>',
								nextArrow: '<i class="icon-arrow-thin-right"></i>',
						});
						//remove active class from all thumbnail slides
						$('.product__slider-thmb .slick-slide').removeClass('slick-active');
						//set active class to first thumbnail slides
						$('.product__slider-thmb .slick-slide').eq(0).addClass('slick-active');
						// On before slide change match active thumbnail to current slide
						$('.product__slider-main').on('beforeChange', function(event, slick, currentSlide, nextSlide) {
								var mySlideNumber = nextSlide;
								$('.product__slider-thmb .slick-slide').removeClass('slick-active');
								$('.product__slider-thmb .slick-slide').eq(mySlideNumber).addClass('slick-active');
						});
						// // init slider
						// require( function(slider) {
						//      $('.product__slider-main').each(function() {
						//              me.slider = new slider($(this), options, sliderOptions, previewSliderOptions);
						//              // stop slider
						//              //me.slider.stop();
						//              // start slider
						//              //me.slider.start(index);
						//              // get reference to slick slider
						//              //me.slider.getSlick();
						//      });
						// });
						var options = {
								progressbarSelector: '.bJS_progressbar',
								slideSelector: '.bJS_slider',
								previewSlideSelector: '.bJS_previewSlider',
								progressInterval: '',
								// add your own progressbar animation function to sync it i.e. with a video
								// function will be called if the current preview slider item (".b_previewItem") has the data-customprogressbar="true" property set
								onCustomProgressbar: function($slide, $progressbar) {}
						};
						// slick slider options
						// see: https://kenwheeler.github.io/slick/
						var sliderOptions = {
								slidesToShow: 1,
								slidesToScroll: 1,
								arrows: false,
								fade: true,
								autoplay: true
						};
						// slick slider options
						// see: https://kenwheeler.github.io/slick/
						var previewSliderOptions = {
								slidesToShow: 3,
								slidesToScroll: 1,
								dots: false,
								focusOnSelect: true,
								centerMode: true
						};
				}
		});
}(jQuery));
