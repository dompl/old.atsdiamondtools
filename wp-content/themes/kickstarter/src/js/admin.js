// ==== ADMIN STYLES ==== //
;(function($) {
    $(function() {

        // Magick Checkbox
        $('input#rememberme').on('click', function() {
            $(this).parent().toggleClass("checked");
        });

        $('#loginform').on('click', function(){
        	$('.g-recaptcha').slideDown('fast');
        });

        $('#login').after('<div id="redfrog"><div class="image"></div><div class="redfrog"><a href="https://www.redfrogstudio.co.uk" target="_blank">Website by Red Frog Studio</a></div></div>');

    });
}(jQuery));
