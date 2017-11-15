/*  ********************************************************
 *   Main Navigation settings
 *  ********************************************************
 */
;(function($) {
    $(function() {
        $("#navigation").navigation({
            responsive: true,
            mobileBreakpoint: $('#navigation').data('breakpoint'),
            showDuration: 100,
            hideDuration: 300,
            showDelayDuration: 0,
            hideDelayDuration: 100,
            submenuTrigger: "hover",
            effect: "fade",
            submenuIndicator: true,
            hideSubWhenGoOut: true,
            visibleSubmenusOnMobile: false,
            fixed: false,
            overlay: true,
            overlayColor: "rgba(0, 0, 0, 0.5)",
            hidden: false,
            hiddenOnMobile: false,
            offCanvasSide: "left",
            offCanvasCloseButton: true,
            animationOnShow: "",
            animationOnHide: "",
            onInit: function() {},
            onLandscape: function() {},
            onPortrait: function() {},
            onShowOffCanvas: function() {},
            onHideOffCanvas: function() {}
        });
    });
}(jQuery));
