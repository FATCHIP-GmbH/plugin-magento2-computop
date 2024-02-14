define(
    [
        'Fatchip_Computop/js/view/payment/method-renderer/base',
        'mage/translate'
    ],
    function (Component, $t) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Fatchip_Computop/payment/directdebit',
                bank: '',
                iban: '',
                bic: ''
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'bank',
                        'iban',
                        'bic'
                    ]);
                return this;
            },
            validate: function () {
                if (this.bank()  == '') {
                    this.messageContainer.addErrorMessage({'message': $t('Please enter a valid bank.')});
                    return false;
                }
                if (this.iban() == '') {
                    this.messageContainer.addErrorMessage({'message': $t('Please enter a valid IBAN.')});
                    return false;
                }
                if (this.requestBic() == 1 && this.bic() == '') {
                    this.messageContainer.addErrorMessage({'message': $t('Please enter a valid BIC.')});
                    return false;
                }
                return true;
            },
            getCleanedNumber: function (dirtyNumber) {
                var cleanedNumber = '';
                var tmpChar;
                for (var i = 0; i < dirtyNumber.length; i++) {
                    tmpChar = dirtyNumber.charAt(i);
                    if (tmpChar != ' ' && (!isNaN(tmpChar) || /^[A-Za-z]/.test(tmpChar))) {
                        if (/^[a-z]/.test(tmpChar)) {
                            tmpChar = tmpChar.toUpperCase();
                        }
                        cleanedNumber = cleanedNumber + tmpChar;
                    }
                }
                return cleanedNumber;
            },
            getData: function () {
                document.getElementById(this.getCode() + '_iban').value = this.getCleanedNumber(this.iban());
                if (this.requestBic() == 1) {
                    document.getElementById(this.getCode() + '_bic').value = this.getCleanedNumber(this.bic());
                }

                var parentReturn = this._super();
                if (parentReturn.additional_data === null) {
                    parentReturn.additional_data = {};
                }
                parentReturn.additional_data.bank = this.bank();
                parentReturn.additional_data.iban = this.getCleanedNumber(this.iban());
                parentReturn.additional_data.bic = this.getCleanedNumber(this.bic());
                return parentReturn;
            },
            requestBic: function() {
                return false; ///@TODO: Add config option
            }
        });
    }
);
