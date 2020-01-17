jQuery(document).ready(function ($) {

        var timeout;

        /**
         * If the country selected by the customer is an EU country,
         * verify if the VAT number inserted is a valid EU VAT number and if
         * there is a mismatch between the country selected by the customer and the country detected from IP address
         */
        function verify_eu_vat_number() {
            doing_vat_check = true;
            var vat_number = $("input#billing_yweu_vat").val();
            console.log(vat_number);
            //  check VAT number and show a message
            var data = {
                'action'          : 'check_eu_vat_number',
                'vat_number'      : vat_number,
                'selected_country': $('select#billing_country').val(),
                'confirm'         : $("#eu_vat_confirm_country").attr('checked') ? true : false,
                'billing_company' : $("#billing_company").val(),
                'shipping_country': $("#shipping_country").val()
            };

            $("#billing_yweu_vat_field").block({
                message   : null,
                overlayCSS: {
                    background: "#fff url(" + ywev.loader + ") no-repeat center",
                    opacity   : .6
                }
            });

            $.post(woocommerce_params.ajax_url, data, function (response) {
                $('.eu-vat-message').remove();

                if (1 == response.code) {
                    $('#billing_yweu_vat_field').append('<p style="color:green;font-size: 12px" class="eu-vat-message">' + response.value + '</p>');
                } else if ((-1 == response.code) && vat_number.trim().length) {
                    $('#billing_yweu_vat_field').append('<p style="color:red;font-size: 12px" class="eu-vat-message">' + response.value + '</p>');
                }

                $("#billing_yweu_vat_field").unblock();
                $(document.body).trigger('update_checkout');
                $(document.body).trigger('yith_eu_vat_number_processed');
            })
        }


        $('input#billing_yweu_vat').on('keyup', function () {

            if (timeout) {
                clearTimeout(timeout);
            }
            if( disable_vat_exception_for_same_country() ){
                timeout = setTimeout(verify_eu_vat_number, 1000);
            }
        });

        //Check the EU VAT number if the field have value
        if ( $("input#billing_yweu_vat").val() != '' ){

            if (timeout) {
                clearTimeout(timeout);
            }
            if( disable_vat_exception_for_same_country() ){
                timeout = setTimeout(verify_eu_vat_number, 1000);
            }
        }


        /**
         * Check if the billing country and the geo localized country is EU and it's different
         */
        $('select#billing_country').on('change', function () {
            if (show_vat_number_field() && disable_vat_exception_for_same_country() ) {
                verify_eu_vat_number();
            }
        });


        function disable_vat_exception_for_same_country(){
            var response = true;
            var base_country = ywev.base_countries;
            var billing_country = $('select#billing_country').val();
            if( billing_country == base_country  && ywev.disable_vat_exception_same_country ){
                response = false;
                $('.eu-vat-message').remove();
            }
            return response;
        }


        /**
         * Verify if the geo localized country or the billing country is an EU country and if they don't match, show the field for country confirmation
         */
        function show_vat_number_field() {

            $('#billing_yweu_vat_field').hide();

            var selected_country_code = $('select#billing_country').val();
            if (selected_country_code) {

                if (ywev.eu_vat_field_enabled_countries.indexOf(selected_country_code) >= 0) {
                    $('#billing_yweu_vat_field').show();
                    return true;
                }
            }

            return false;
        }
    }
);