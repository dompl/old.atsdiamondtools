jQuery(document).ready(function ($) {

    // Handle checkout update event
    $('body').on('updated_checkout', function () {
        // Set the default card to be checked and add the selected class
        $("#cards-1").prop("checked", true).parent().addClass("selected");

        // Handle click event on radio buttons
        $(".ag-select-cards .card-list li input:radio").click(function () {
            $(".ag-select-cards .card-list li input:radio").parent().removeClass("selected");
            $(this).parent().addClass("selected");
        });
    });

});