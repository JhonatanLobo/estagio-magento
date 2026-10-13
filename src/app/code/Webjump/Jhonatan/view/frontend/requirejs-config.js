var config = {
    deps: [
        'Webjump_Jhonatan/js/modo-assombrado',
        'Webjump_Jhonatan/js/bindings/tremor'
    ],
    config: {
        mixins: {
            'Magento_Checkout/js/view/minicart': {
                'Webjump_Jhonatan/js/view/minicart-mixin': true
            }
        }
    }
};
