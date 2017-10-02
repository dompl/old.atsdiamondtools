// ==== FOOTER ==== //
;(function($) {
    $(function() {

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
