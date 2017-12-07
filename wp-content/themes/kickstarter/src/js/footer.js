// ==== FOOTER ==== //
;(function($) {
    $(function() {
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
