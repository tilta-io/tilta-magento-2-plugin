define([
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'ko',
    'mage/translate'
], function (
    Component,
    quote,
    urlBuilder,
    storage,
    ko,
    $t,
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Tilta_Payment/payment/tilta'
        },

        selectedPaymentTerm: ko.observable(null),
        availablePaymentTerms: ko.observable([]),
        persistentErrorMessage: ko.observable(null),

        initObservable: function () {
            this._super();

            quote.billingAddress.subscribe((value, oldValue) => {
                if (value && oldValue
                    && value.customerAddressId === oldValue.customerAddressId
                    && this.selectedPaymentTerm() !== null
                ) {
                    return;
                }
                this._reset();
                value && this._loadPaymentTerms(value);
            });

            return this;
        },

        getData: function () {
            const selected = this.selectedPaymentTerm();

            return {
                'method': this.item.method,
                'additional_data': selected ? {
                    'tilta_payment_term': selected.payment_term,
                    'tilta_payment_method': selected.payment_method,
                } : {},
            };
        },

        validate: function () {
            const selected = this.selectedPaymentTerm();

            return selected
                && 'payment_term' in selected
                && 'payment_method' in selected;
        },

        _loadPaymentTerms(billingAddress) {
            billingAddress = billingAddress ? billingAddress : quote.billingAddress();
            const customerAddressId = billingAddress ? billingAddress.customerAddressId : null;
            if (!customerAddressId) {
                return;
            }
            storage.get(urlBuilder.createUrl('/carts/mine/tilta/payment-terms/:addressId', {
                addressId: customerAddressId
            })).done((result) => {
                if (result.payment_terms.length === 1) {
                    this.selectedPaymentTerm(result.payment_terms[0]);
                } else if (result.payment_terms.length > 0) {
                    this.availablePaymentTerms(result.payment_terms);
                }

                if (result.error_message) {
                    this.persistentErrorMessage(result.error_message);
                }
            }).fail((error) => {
                this.messageContainer.addErrorMessage({message: $t('Unfortunately, you cannot use this payment method. Please contact customer service.')});
                console.error(error);
            });
        },

        isPlaceOrderActionAllowed() {
            return this.selectedPaymentTerm() !== null;
        },

        selectPaymentTerm(term) {
            this.selectedPaymentTerm(term);
        },

        getDaysToDueDate(dueDate) {
            let Difference_In_Time = new Date(dueDate).getTime() - new Date().getTime();
            return Math.round(Difference_In_Time / (1000 * 3600 * 24));
        },

        getPaymentInformation() {
            const term = this.selectedPaymentTerm();

            return term ? $t('You have %1 days to pay after your order is shipped. We will send you a reminder when the payment date is close with all important details.').replace('%1', this.getDaysToDueDate(term.due_date)) : null
        },

        isAvailable: function () {
            return quote.totals()['grand_total'] >= 0;
        },

        _reset() {
            this.selectedPaymentTerm(null);
            this.availablePaymentTerms([]);
            this.persistentErrorMessage(null);
        }
    });
});
