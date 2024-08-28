define(
    [
        'Fatchip_Computop/js/view/payment/method-renderer/base',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Customer/js/model/customer',
        'mage/translate'
    ],
    function (Component, additionalValidators, customer, $t) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Fatchip_Computop/payment/easycredit',
                birthday: '',
                birthmonth: '',
                birthyear: ''
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'birthday',
                        'birthmonth',
                        'birthyear'
                    ]);
                return this;
            },
            getData: function () {
                var parentReturn = this._super();
                if (parentReturn.additional_data === null) {
                    parentReturn.additional_data = {};
                }
                if (this.requestBirthday()) {
                    parentReturn.additional_data.dateofbirth = this.birthyear() + '-' + this.birthmonth() + '-' + this.birthday();
                }
                return parentReturn;
            },
            validate: function () {
                if (this.requestBirthday() === true && !this.isDateValid(this.birthyear(), this.birthmonth(), this.birthday())) {
                    this.messageContainer.addErrorMessage({'message': $t('Please enter a valid date.')});
                    return false;
                }
                if (this.requestBirthday() === true && !this.isBirthdayValid(this.birthyear(), this.birthmonth(), this.birthday())) {
                    this.messageContainer.addErrorMessage({'message': $t('You have to be at least 18 years old to use this payment type!')});
                    return false;
                }
                return true;
            },
            requestBirthday: function () {
                if (customer.customerData.dob == undefined || customer.customerData.dob === null) {
                    return true;
                }
                return false;
            },
            continueToComputop: function () {
                if (this.validate() && additionalValidators.validate()) {
                    let data = this.getData();
                    let addParam = '';
                    if (data.additional_data !== undefined && data.additional_data.dateofbirth !== undefined) {
                        addParam = '?dob=' + data.additional_data.dateofbirth;
                    }
                    this.redirect('computop/onepage/redirect/' + addParam);
                    return false;
                }
            }
        });
    }
);
