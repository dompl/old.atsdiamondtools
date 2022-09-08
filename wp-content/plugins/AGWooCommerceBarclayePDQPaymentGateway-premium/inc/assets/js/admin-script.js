// JS to show hide refund settings.
jQuery(document).ready(function () {

    jQuery('#woocommerce_epdq_checkout_status').change(function () {

        swal({
            title: "Store status change.",
            text: "Don't forget to use the correct ePDQ account for the store status you have selected, ePDQ emails the merchant two different accounts.",
            icon: "info",
            button: "Okay",
            timer: 5000,
        });

    });
});
