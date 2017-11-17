// ==== Woocommerce ==== //
;(function($) {
		$(function() {

				/* Product sorting */
				$('#product-sort span').on('click', function() {
						var sort = $(this).data('sort');
						$.cookie('sort', sort);

						if ($('#products-list').hasClass('list')) {
								$('#products-list').removeClass('list').addClass('grid');
						} else {
								$('#products-list').removeClass('grid').addClass('list');
						}
				});

		});
}(jQuery));
