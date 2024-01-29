define(
    [
        'Fatchip_Computop/js/view/payment/method-renderer/base'
    ],
    function (Component) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Fatchip_Computop/payment/creditcard'
            }
        });
    }
);
