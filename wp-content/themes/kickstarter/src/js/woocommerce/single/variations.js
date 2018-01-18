// ==== Single product JS ==== //
;
(function($) {
		$(function() {

				var FullProd_Description = $('.product-description-content').html();
				$('#mobile_full').html(FullProd_Description);

				/* Product Manipulation */

				$(".variations_form").on("woocommerce_variation_select_change", function() {
						$('#var_sku').text('Per Variation');
						$('p.price').html($('div.hidden-variable-price').html());
						$('#product-option').text('');
						var_wrapper = $('#variation-wrapper');

						// Remove wrapper classes
						var var_wrapper_classes = [
								'has-variation',
								'has-description',
								'has-image',
								'off-stock'
						];
						// Remove Variation Name
						$.each(var_wrapper_classes, function(i, v) {
								var_wrapper.removeClass(v);
						});
						var remove_var_el = [
								'#oos',
								'#variation-stock',
								'.variation-title',
								'.variation-image',
								'#variation-stock',
								'p.availability'
						];
						$.each(remove_var_el, function(i, v) {
								if ($(v)) $(v).remove();
						});
				});

				/*  ********************************************************
				 *   comment
				 *  ********************************************************
				 */
				$('.variations_form select').on('change', function(){
					var val = $(this).val();

					if (val === '') {
						$('#ats-product-images .single-image').css('display', 'block');
						$('#ats-product-images .additional-single-image').html('');
						$('#product__slider').css('display', 'block');
						$('#ats-product-images .additional-single-image').remove();

						global_variation_image_id = starting_variation_image_id;
					}
				})

				// change single gallery
				function changeImage(variation, isSingle) {

					$('#ats-product-images .additional-single-image').remove();

					if (isSingle === 'single') {
						$('#ats-product-images .single-image').css('display', 'none');
					} else {
						$('#product__slider').css('display', 'none');
					}

					var single_image_html =
					'<div class="additional-single-image">' +
					'<a href="' + variation.image.url + '" data-lightbox="image-1" data-title="' + variation.image.alt + '">' +
					'<figure>' +
					'<img src="' + variation.image.src + '" alt="' + variation.image.alt + '">' +
					'</figure>' +
					'</a>' +
					'</div>';

					$('#ats-product-images').append(single_image_html);
				}

				var starting_variation_image_id = String($('.prod-main-image').data('image-id'));
				var global_variation_image_id = String($('.prod-main-image').data('image-id'));
				var starting_single_variation = String($('#ats-product-images .single-image').data('image-id'));

				$('.variations_form').each(function() {

						$(this).on('found_variation', function(event, variation) {
								var change;
								if (global_variation_image_id === variation.image_id || starting_variation_image_id === 'undefined') {
									change = false;
								} else {
									change = true;
								}

								// For single
								if (starting_single_variation !== variation.image_id && starting_single_variation !== 'undefined') {
									global_variation_image_id = variation.image_id;
									changeImage(variation, 'single');
								} else {
									$('#ats-product-images .single-image').css('display', 'block');
									$('#ats-product-images .additional-single-image').html('');
								}

								// For gallery
								if (global_variation_image_id !== variation.image_id && starting_single_variation === 'undefined') {
									global_variation_image_id = variation.image_id;
									changeImage(variation, 'gallery');
								}

								if(starting_variation_image_id === variation.image_id && change) {
									$('#product__slider').css('display', 'block');
									$('#ats-product-images .additional-single-image').remove();
								}

								var var_ID = variation.variation_id,
										var_Name = $(this).find('option:selected').text(), // Geet the variation name
										var_Array = $(this).data('product_variations'), // Get the varioation array
										prod_image_ID = $('.prod-main-image').data('image-id'), // Get main image ID
										prod_Name = $('.prod_Name').text(),
										var_wrapper = $('#variation-wrapper');

								var_wrapper.addClass('has-variation');

								// $('p.price').html($('div.woocommerce-variation-price > span.price').html());
								$('p.price').html(variation.price_html);

								/* Variation Name */
								$('#product-option').text(var_Name);

								/* Product image */
								if (prod_image_ID != variation.image_id) {

										var_wrapper.addClass('has-image');
										$('.variation-image').remove();

										var var_image_HTML =
												'<div class="variation-image">' +
												'<a href="' + variation.image.url + '" data-lightbox="variation-image-' + var_ID + '" data-title="' + $('.product-header h2').text() + ' - ' + var_Name + variation.sku + variation.image.caption + '">' +
												'<img src="' + variation.image.src + '">' +
												'</a>' +
												'</div>';

										// Add the html to the wrapper
										var_wrapper.append(var_image_HTML);

								} else {
										$('.variation-image').remove();
										// 1 var_wrapper.removeClass('has-image').removeClass('has-variation');
								}

								/* Productg description */
								if (variation.variation_description != '') {

										var_wrapper.addClass('has-variation').addClass('has-description');

										$('.variation-title').remove();

										var_description_HTML =
												'<div class="variation-title">' +
												'<h3>' + var_Name + '</h3>' +
												'<div class="variation-description">' + variation.variation_description + '</div>' +
												'</div>';

										// Append description to the wrapper
										var_wrapper.append(var_description_HTML);

								} else {

										var_wrapper.removeClass('has-description').removeClass('has-variation');
										$('.variation-title').remove();

								}

								/* Product SKU */
								var_sku_text = variation.sku != '' ? variation.sku : 'N/A';
								$('#var_sku').text(var_sku_text);

								/* Product out of stock */
								if (variation.is_in_stock == false || variation.display_regular_price == '') {

										var_wrapper.removeClass('off-stock');
										$('#variation-stock').remove();

										// sprintf is available in helpers.js. This is not a standard JS function!
										var var_out_of_stoch_HTML = sprintf(
												'<div id="variation-stock" class="not-in-stock clx"><span class="float">Sorry, %s - <strong>(%s)</strong> is currently out of stock.</span></div>',
												prod_Name,
												var_Name
										);
										$('#product-option').append('<span id="oos"> (OUT OF STOCK)</span>');

										var_wrapper.addClass('off-stock').append(var_out_of_stoch_HTML);

								} else {

										var_wrapper.removeClass('off-stock');
										$('#variation-stock').remove();
										$('#oos').remove();

								}
						});
				});
		});
}(jQuery));
