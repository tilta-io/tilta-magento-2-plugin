define([
    'Magento_Ui/js/form/element/abstract',
    'ko',
    'mage/translate'
], function (
    Component,
    ko,
    $t
) {
    'use strict';

    const todayYear = new Date().getFullYear();

    return Component.extend({
        valueDay: ko.observable(null),
        valueMonth: ko.observable(null),
        valueYear: ko.observable(null),

        optionsDay: [...Array(31).keys()].map(i => i + 1),
        optionsMonth: [...Array(12).keys()].map(i => i + 1),
        optionsYear: [...Array(100).keys()].map((i) => todayYear - i),

        initObservable: function () {
            this._super();

            this.valueDay.subscribe(() => this._setValue())
            this.valueMonth.subscribe(() => this._setValue())
            this.valueYear.subscribe(() => this._setValue())

            return this;
        },

        validate: function () {
            const value = this.value();
            const isValid = value ? value.match(/^\d{4}-\d{2}-\d{2}$/) : false

            const message = isValid ? null : $t('This is a required field.');

            this.error(message);
            this.error.valueHasMutated();
            this.bubble('error', message);

            if (this.source && !isValid) {
                this.source.set('params.invalid', true);
            }

            return {
                valid: isValid,
                target: this
            };
        },

        _setValue() {
            if (this.valueDay() && this.valueMonth() && this.valueYear()) {
                this.value([
                    this.valueYear(),
                    String(this.valueMonth()).padStart(2, '0'),
                    String(this.valueDay()).padStart(2, '0'),
                ].join('-'))
            } else {
                this.value('');
            }
        }
    })
})
