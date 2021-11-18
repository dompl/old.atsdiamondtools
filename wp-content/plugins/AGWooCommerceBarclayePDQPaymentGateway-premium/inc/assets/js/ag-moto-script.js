(function ($) {
    'use strict';

    $('#ag-moto').on('click', function () {

        // get the order_id from the button tag
        var order_url = $(this).data('order_url');
        var order_id = $(this).data('order_id');
        var plugin = $(this).data('plugin');

        $('html,body').scrollTop(0);
        $('body').prepend('<div id="fade" style="position: absolute; top: 0%; left: 0%; width: 100%; height: 100vh; background-color: #fff; z-index: 1002; -moz-opacity: 0.8; opacity: .70; filter: alpha(opacity=80); "></div>');
        $('body').prepend('<div id="modal" style="position: absolute; top: 45%; left: 45%; padding:30px 15px 0px; z-index: 1003; text-align:center; overflow: auto;"><img id="loader" src="' + plugin + '/inc/assets/img/AG-ajax-beat.gif"><span style="display: inherit; font-size: 14px;">Building Moto Order...</span></div>');


        $.ajax({
            type: 'POST',
            url: ag_status_var.ajaxurl,
            dataType: 'json',
            data: {
                action: 'ag_pre_moto_call',
                nonce: ag_status_var.nonce,
                order_url: order_url,
                order_id: order_id,
            },
            success: function (data, textStatus, XMLHttpRequest) {

                window.location.href = order_url;

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {

                $('#fade').hide();
                $('#modal').hide();
                alert(ag_status_var.error);

            }
        });

    });


})(jQuery);