(function () { 

    // Imports
    const { __ }  = wp.i18n;
    const { decodeEntities }  = wp.htmlEntities;
    const { getSetting }  = wc.wcSettings;
    const { registerPaymentMethod }  = wc.wcBlocksRegistry;
    const { applyFilters } = wp.hooks;  
    
    // Data
    const settings = getSetting('epdq_checkout_data', {});
    const defaultLabel = __('AG ePDQ', 'ag_epdq_server');
    const label = decodeEntities(settings.title) || defaultLabel;
    const iconsrc = settings.iconsrc;
    
    
    const Content = () => {
        return React.createElement(
            'div',
            null,
            decodeEntities(settings.description || '')
        );
    };
    
    const Label = props => {
        var label = null;
        if (iconsrc != '') {

            const icon = React.createElement('img', { 
                alt: decodeEntities(settings.title), 
                title: decodeEntities(settings.title), 
                className: 'epdq-payment-logo', 
                src:iconsrc
            });

            const { PaymentMethodLabel } = props.components;
            label = React.createElement(PaymentMethodLabel, { text: settings.title, icon: icon });

        } else {
            // Just do a text label if no icon is passed
	        const { PaymentMethodLabel } = props.components;
            label = React.createElement(PaymentMethodLabel, { text: settings.title });

        }
        return applyFilters('woo_ag_epdq_checkout_label', label, settings);
    };
    
    const canMakePayment = (args) => {
        return true;
    };
    
    const ePDQPaymentMethod = {
        name: 'epdq_checkout',
        label: React.createElement(Label, null),
        content: React.createElement(Content, null),
        edit: React.createElement(Content, null),
        placeOrderButtonLabel: __('Pay using ePDQ', 'ag_epdq_server'),
        icons: null,
        canMakePayment: canMakePayment,
        ariaLabel: label
    };
    
    
    registerPaymentMethod(Config => new Config(ePDQPaymentMethod));
    
    
}());