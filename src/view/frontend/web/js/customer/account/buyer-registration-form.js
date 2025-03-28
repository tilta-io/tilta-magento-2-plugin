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
            this.salutationWrapper = document.getElementById('tilta-salutation-wrapper');
            this._onChangeLegalForm();
        },

        _onChangeLegalForm() {
            if (this.legalFormElement.value === 'SOLE_TRADER') {
                this._toggleIncorporatedAtWrapper(true);
                this._toggleSalutation(true);
            } else {
                this._toggleIncorporatedAtWrapper(false);
                this._toggleSalutation(false);
            }
        },

        _toggleIncorporatedAtWrapper(isRequired) {
            this.incorporatedAtWrapper.style.display = isRequired ? '' : 'none';
            this.incorporatedAtWrapper.querySelectorAll('select').forEach(e => {
                e.required = isRequired;
                e.setAttribute('aria-required', isRequired ? 'true' : 'false');
            });
            this.incorporatedAtWrapper.classList.toggle('required', isRequired);
        },

        _toggleSalutation(isRequired) {
            this.salutationWrapper.style.display = isRequired ? '' : 'none';
            const select = this.salutationWrapper.querySelector('select');
            select.required = isRequired;
            select.setAttribute('aria-required', isRequired ? 'true' : 'false');
            this.salutationWrapper.classList.toggle('required', isRequired);
        }
    });

    return $.tilta.buyerRegistrationForm;
})
