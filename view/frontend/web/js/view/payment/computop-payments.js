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
            },
            {
                type: 'computop_directdebit',
                component: 'Fatchip_Computop/js/view/payment/method-renderer/directdebit-method'
            },
            {
                type: 'computop_paypal',
                component: 'Fatchip_Computop/js/view/payment/method-renderer/paypal-method'
            },
            {
                type: 'computop_klarna',
                component: 'Fatchip_Computop/js/view/payment/method-renderer/klarna-method'
            },
            {
                type: 'computop_ideal',
                component: 'Fatchip_Computop/js/view/payment/method-renderer/ideal-method'
            },
            {
                type: 'computop_easycredit',
                component: 'Fatchip_Computop/js/view/payment/method-renderer/easycredit-method'
            },
            {
                type: 'computop_amazonpay',
                component: 'Fatchip_Computop/js/view/payment/method-renderer/amazonpay-method'
            },
            {
                type: 'computop_ratepay_directdebit',
                component: 'Fatchip_Computop/js/view/payment/method-renderer/ratepay_directdebit-method'
            },
            {
                type: 'computop_ratepay_invoice',
                component: 'Fatchip_Computop/js/view/payment/method-renderer/ratepay_invoice-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
