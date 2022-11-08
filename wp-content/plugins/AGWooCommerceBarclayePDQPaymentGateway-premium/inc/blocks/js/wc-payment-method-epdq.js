(function () {

    var _settings$supports;
    // Imports
    const {__} = wp.i18n;
    const {decodeEntities} = wp.htmlEntities;
    const {getSetting} = wc.wcSettings;
    const {registerPaymentMethod} = wc.wcBlocksRegistry;
    const {createElement} = wp.element;

    // Data
    const settings = getSetting('epdq_checkout_data', {});
    const defaultLabel = __('AG ePDQ', 'ag_epdq_server');
    const label = decodeEntities(settings.title) || defaultLabel;

    // Main content
    const Content = () => {
        const isTestMode = settings.testmode;
        if (isTestMode === 'test') {
            return createElement("p", null, decodeEntities(settings.description), React.createElement("p", null, ""), React.createElement("strong", null, "TEST MODE ACTIVE"), React.createElement("p", null, "In test mode, you can use Visa number ", React.createElement("strong", null, "4444 3333 2222 1111"), " with any CVC and a valid expiration date or check the ", React.createElement("a", {href: "https://weareag.co.uk/docs/barclays-epdq-payment-gateway/setup-barclays-epdq-payment-gateway/testing-and-test-cards/"}, "documentation"), " for more card numbers, steps on setting up and troubleshooting."));
        } else {
            return createElement("p", null, decodeEntities(settings.description));
        }
    };

    // Sorting the selected card icons
    const getCardIcons = Object.entries(settings.cardIcons ?? {}).map(([id, {src, alt}]) => {
        return {
            id, src, alt,
        };
    });

    // Gateway title and card icons
    const Label = () => {
        return createElement("div", {className: "ag-epdq-checkout-title-wrapper"},
            createElement("span", {className: "ag-epdq-checkout-title"}, label),
            createElement("div", {className: "ag-epdq-checkout-icon-wrapper"},
                getCardIcons.map(icon => createElement("img", {src: icon.src, alt: decodeEntities(icon.alt)})))
        );
    };

    const canMakePayment = (args) => {
        return true;
    };

    const ePDQPaymentMethod = {
        name: 'epdq_checkout',
        label: React.createElement(Label, null),
        content: createElement(Content,null),
        edit: React.createElement(Content, null),
        placeOrderButtonLabel: __('Pay using ePDQ', 'ag_epdq_server'),
        icons: getCardIcons,
        canMakePayment: canMakePayment,
        ariaLabel: label,

    };

    registerPaymentMethod(ePDQPaymentMethod);

}());