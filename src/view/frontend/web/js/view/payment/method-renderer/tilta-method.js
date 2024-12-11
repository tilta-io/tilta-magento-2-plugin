define([
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
], function (
    Component,
    quote,
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Tilta_Payment/payment/tilta'
        },

        isAvailable: function () {
            return quote.totals()['grand_total'] >= 0;
        }
    });
});
