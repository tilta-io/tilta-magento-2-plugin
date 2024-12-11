define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push({
        type: 'tilta',
        component: 'Tilta_Payment/js/view/payment/method-renderer/tilta-method'
    });

    return Component.extend({});
});
