define([
    'Magento_Ui/js/form/element/select',
    'Magento_Checkout/js/model/quote',
], function (
    Component,
    quote
) {
    'use strict';

    return Component.extend({
        currentBillingAddress: quote.billingAddress,
        defaults: {
            listens: {
                "${ $.provider }:${ $.parentScope }.legal_form": "updateLegalForm"
            }
        },

        updateLegalForm(value) {
            if (value === 'SOLE_TRADER') {
                this.show();
                this.required(true);
            } else {
                this.hide();
                this.required(false);
                this.value('');
            }
        },
    })
})
