define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'computop_creditcard',
                component: 'Fatchip_Computop/js/view/payment/method-renderer/creditcard-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
