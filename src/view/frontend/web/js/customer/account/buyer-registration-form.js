define([
    'jquery'
], function (
    $
) {

    $.widget('tilta.buyerRegistrationForm', {
        legalFormElement: null,
        _create() {
            this.legalFormElement = this.element.get(0).querySelector('[name="legal_form"]');
            this.legalFormElement.addEventListener('change', this._onChangeLegalForm.bind(this));
            this.incorporatedAtWrapper = document.getElementById('tilta-incorporated-at-wrapper');
            this._onChangeLegalForm();
        },

        _onChangeLegalForm() {
            if (this.legalFormElement.value === 'SOLE_TRADER') {
                this.incorporatedAtWrapper.style.display = '';
                this.incorporatedAtWrapper.querySelectorAll('select').forEach(e => {
                    e.required = true;
                    e.setAttribute('aria-required', 'true');
                });
                this.incorporatedAtWrapper.classList.add('required');
            } else {
                this.incorporatedAtWrapper.style.display = 'none';
                this.incorporatedAtWrapper.querySelectorAll('select').forEach(e => {
                    e.required = false;
                    e.setAttribute('aria-required', 'false');
                });
                this.incorporatedAtWrapper.classList.remove('required');
            }
        }
    });

    return $.tilta.buyerRegistrationForm;
})
