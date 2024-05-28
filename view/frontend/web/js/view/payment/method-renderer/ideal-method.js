define(
    [
        'Fatchip_Computop/js/view/payment/method-renderer/base'
    ],
    function (Component) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Fatchip_Computop/payment/ideal',
                idealIssuer: '',
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'idealIssuer'
                    ]);
                return this;
            },
            getData: function () {
                var parentReturn = this._super();
                if (parentReturn.additional_data === null) {
                    parentReturn.additional_data = {};
                }
                parentReturn.additional_data.issuer = this.idealIssuer();
                return parentReturn;
            },
            showIssuerList: function () {
                if (window.checkoutConfig.payment.computop.computop_ideal.service === "direct") {
                    return true;
                }
                return false;
            },
            getIdealIssuers: function () {
                return window.checkoutConfig.payment.computop.computop_ideal.issuerList;
            }
        });
    }
);
