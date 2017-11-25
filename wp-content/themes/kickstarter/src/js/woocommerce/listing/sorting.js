// ==== Woocommerce Listing product sorting ==== //
;(function($) {
		$(function() {
				/* Product sorting */
				var span = $('#product-sort span');
				span.on('click', function() {
						var sort = $(this).data('sort');
						$.cookie('sort', sort);
						span.removeClass('active');
						$(this).addClass('active');
						var activeLayout = $('#product-sort span.active').data('sort');
						$('#products-list').removeClass('list').removeClass('grid').addClass(activeLayout);
				});
		});
}(jQuery));
