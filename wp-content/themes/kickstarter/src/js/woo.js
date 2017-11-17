// ==== Woocommerce ==== //
;(function($) {
		$(function() {

				/* Product sorting */
				$('#product-sort span').on('click', function() {
						var sort = $(this).data('sort');
						$.cookie('sort', sort);

						$('#product-sort span').removeClass('active');
						$(this).addClass('active');

						var activeLayout = $('#product-sort span.active').data('sort');

						$('#products-list').removeClass('list').removeClass('grid').addClass(activeLayout);
				});

		});
}(jQuery));
