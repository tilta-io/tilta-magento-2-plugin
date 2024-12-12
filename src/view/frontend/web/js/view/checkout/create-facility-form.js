define([
    'jquery',
    'Magento_Ui/js/form/form',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'uiRegistry',
    'Magento_Ui/js/model/messages',
    'uiLayout',
], function (
    $,
    Component,
    quote,
    urlBuilder,
    api,
    registry,
    Messages,
    layout,
) {
    'use strict';

    const SOURCE_NAME = 'tiltaCreateFacilityForm';

    return Component.extend({
        defaults: {
            template: 'Tilta_Payment/checkout/create-facility-form',
            tiltaPaymentMethodComponent: 'checkout.steps.billing-step.payment.payments-list.tilta'
        },
        messageContainer: null,

        initialize: function () {
            this._super();

            this.messageContainer = new Messages();
            layout([{
                parent: this.name,
                name: this.name + '.messages',
                displayArea: 'messages',
                component: 'Magento_Ui/js/view/messages',
                config: {
                    messageContainer: this.messageContainer
                }
            }]);

            return this;
        },

        initObservable: function () {
            this._super();

            quote.billingAddress.subscribe((value, oldValue) => {
                if (!value || !value.telephone) {
                    return;
                }

                this.source.set(SOURCE_NAME + '.telephone', value.telephone);
            });

            return this;
        },

        onSubmit: function () {
            this.source.set('params.invalid', false);
            this.source.trigger(SOURCE_NAME + '.data.validate');

            if (this.source.get('params.invalid')) {
                return;
            }

            $("body").trigger('processStart');

            const additionalData = {...this.source.get(SOURCE_NAME)};
            delete additionalData.toc;
            api.post(urlBuilder.createUrl('/tilta/credit-facility/request', {}), JSON.stringify({
                additionalData: additionalData,
                customerAddressId: quote.billingAddress().customerAddressId
            })).done(() => {
                registry.get(this.tiltaPaymentMethodComponent)._loadPaymentTerms().then(() => {
                    $("body").trigger('processStop');
                });

            }).fail((error) => {
                $("body").trigger('processStop');
                const message = error && error.responseJSON && error.responseJSON.message;
                this.messageContainer.addErrorMessage({message: message ? message : $t('Unknown error during creating facility')});
                if (!message) {
                    console.error(error)
                }
            });
        }
    });
});
