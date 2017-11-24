// ==== Single product JS ==== //
;(function($) {
		$(function() {

				/* Prudyct Manipulation */
				$('.variations select').blur(function() {

						var var_ID = $('input.variation_id').val(), // Variation ID
								var_Name = $(this).find('option:selected').text(), // Geet the variation name
								var_Array = $('form.variations_form').data('product_variations'), // Get the varioation array
								prod_image_ID = $('.prod-main-image').data('image-id'), // Get main image ID
								prod_Name = $('.prod_Name').text(),
								var_wrapper = $('#variation-wrapper');

						// Checking is the variation is selected
						if ('' != var_ID) {

								$('p.price').html($('div.woocommerce-variation-price > span.price').html());

								/* Variation Name */
								$('#product-option').text(var_Name);

								// Loop through all the variations
								$.each(var_Array, function(n) {

										var a = var_Array[n]; // @returns Arrak of current variation

										// Selest array associated with selected variation
										if (a.variation_id == var_ID) {

												/* Product image */
												if (prod_image_ID != a.image_id) {

														var_wrapper.addClass('has-variation').addClass('has-image');
														$('.variation-image').remove();

														var var_image_HTML =
																'<div class="variation-image">' +
																'<a href="' + a.image.url + '" data-lightbox="variation-image-' + var_ID + '" data-title="' + $('.product-header h2').text() + ' - ' + var_Name + a.sku + a.image.caption + '">' +
																'<img src="' + a.image.src + '">' +
																'</a>' +
																'</div>';

														// Add the html to the wrapper
														var_wrapper.append(var_image_HTML);

												} else {
														$('.variation-image').remove();
														var_wrapper.removeClass('has-image').removeClass('has-variation');
												}

												/* Productg description */
												if (a.variation_description != '') {

														var_wrapper.addClass('has-variation').addClass('has-description');

														$('.variation-title').remove();

														var_description_HTML =
																'<div class="variation-title">' +
																'<h3>' + var_Name + '</h3>' +
																'<div class="variation-description">' + a.variation_description + '</div>' +
																'</div>';

														// Append description to the wrapper
														var_wrapper.append(var_description_HTML);

												} else {

														var_wrapper.removeClass('has-description').removeClass('has-variation');
														$('.variation-title').remove();

												}

												/* Product SKU */
												var_sku_text = a.sku != '' ? a.sku : 'N/A';
												$('#var_sku').text(var_sku_text);

												/* Product out of stock */
												if ( a.is_in_stock ==false || a.display_regular_price == '' ) {

														var_wrapper.removeClass('off-stock');
														$('#variation-stock').remove();
														// sprintf is available in helpers.js. This is not a standard JS function!
														var var_out_of_stoch_HTML = sprintf(
															'<div id="variation-stock" class="not-in-stock">Sorry, %s - <strong>(%s)</strong> is currently out of stock.</div>',
															prod_Name,
															var_Name
														);

														var_wrapper.addClass('off-stock').append(var_out_of_stoch_HTML);

												} else {
													var_wrapper.addClass('off-stock');
													$('#variation-stock').remove();

												}

										}
								});
						} else {

								// Remove wrapper classes
								var var_wrapper_classes = [
								'has-variation',
								'has-description',
								'has-image',
								'off-stock'
								];

								$.each( var_wrapper_classes, function(i , v) {
										 var_wrapper.removeClass(v);
								});

								$('p.price').html($('div.hidden-variable-price').html());
								$('#var_sku').text('Per Variation');

								// Remove all elements if there is no variation selected
								var  remove_var_el = [
								'#variation-stock',
								'.variation-title',
								'.variation-image',
								'#variation-stock',
								'p.availability'
								];

								$.each( remove_var_el, function(i , v) {
										if ($(v)) $(v).remove();
								});
						}
				});
		});
}(jQuery));
