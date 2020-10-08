// ==== HEADER ==== //
// http://hilios.github.io/jQuery.countdown/examples/show-total-hours.html
(function ($) {
	$(function () {
		$(".countdown-container .truck").on("click", function () {
			$(".countdown-container").toggleClass("hidden");
			$.cookie("ats_del", true, { expires: 1, path: "/" });
		});

		$("[data-countdown]").each(function () {
			var $this = $(this),
				finalDate = $(this).data("countdown");
			$this.countdown(finalDate, function (event) {
				$this.html(event.strftime("%D days %H:%M:%S"));
			});
		});
	});
})(jQuery);
