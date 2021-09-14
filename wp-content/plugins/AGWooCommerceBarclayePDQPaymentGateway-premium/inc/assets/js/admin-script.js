// JS to show hide refund settings.
jQuery(document).ready(function() {
    jQuery(".form-table tbody tr:nth-child(17)").addClass("hide");
    jQuery(".form-table tbody tr:nth-child(18)").addClass("hide");
    jQuery(".form-table tbody tr:nth-child(19)").addClass("hide");

    jQuery('#woocommerce_epdq_checkout_refund').change(function() {

        if (jQuery('#woocommerce_epdq_checkout_refund').is(':checked')) {
            jQuery(".form-table tbody tr:nth-child(17)").removeClass("hide");
            jQuery(".form-table tbody tr:nth-child(18)").removeClass("hide");
            jQuery(".form-table tbody tr:nth-child(19)").removeClass("hide");
        } else {
            jQuery(".form-table tbody tr:nth-child(17)").addClass("hide");
            jQuery(".form-table tbody tr:nth-child(18)").addClass("hide");
            jQuery(".form-table tbody tr:nth-child(19)").addClass("hide");
        }

    });
});
