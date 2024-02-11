<?php

namespace Fatchip\Computop\Model\Api\Request;

use Fatchip\Computop\Model\Method\BaseMethod;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;

class Authorization extends Base
{
    /**
     * @param  Order   $order
     * @param  Payment $payment
     * @param  double  $amount
     * @return array
     */
    public function generateRequest(Order $order, Payment $payment, $amount)
    {
        $amount = $order->getTotalDue(); // given amount is in base-currency - order currency is needed for transfer to computop

        /** @var BaseMethod $methodInstance */
        $methodInstance = $payment->getMethodInstance();

        /**
        {
            "transactionId": "6B29FC40-1067-B31D-00DD010662DA",
            "amount": {
                "currency": "EUR",
                "value": 50000
            },
            "language": "de",
            "urls": {
                "failure": "https://my.callback.url.com/failure",
                "success": "https://my.callback.url.com/success",
                "notify": "https://my.callback.url.com/notify"
            },
            "order": {
                "id": "6B29FC40-1067-B31D-00DD010A1122",
                "description": [
                    "description of purchased goods",
                    "unit prices",
                    "etc"
                ]
            },
            "expirationTime": "2023-05-01T13:50:49.112Z",
            "capture": {
                "hours": {
                    "delayed": 20
                }
            },
            "channel": {
                "code": "configurationChannel"
            },
            "metadata": {
                "userData": "my user data",
                "k1": "v1",
                "plain": "plain text",
                "k2": "v2"
            },
            "payment": {
                "method": "giropay",
                "giropay": {
                    "sellingPoint": "sp",
                    "service": "products",
                    "scheme": "gir",
                    "account": {
                        "number": "12345",
                        "code": "RABONL2U",
                        "accountHolder": "John Doe "
                    }
                }
            }
        }
         */



        $this->addParameter('Currency', $order->getOrderCurrencyCode());
        $this->addParameter('Amount', $this->formatAmount($amount)); ///@TODO: Check if amount is base-currency or order-currency

        $this->addParameter('TransID', $this->paymentHelper->getTransactionID()); // Generate new TransID for further use with this transaction @TODO: Extend order table and save TransactionID
        $this->addParameter('RefNr', $order->getIncrementId()); // Generate new TransID for further use with this transaction @TODO: Extend order table and save TransactionID

        $this->addParameter('URLSuccess', $methodInstance->getSuccessUrl($order));
        $this->addParameter('URLFailure', $methodInstance->getFailureUrl());
        $this->addParameter('URLNotify', $methodInstance->getNotifyUrl());
        $this->addParameter('Response', 'encrypt');

        $this->addParameters($methodInstance->getPaymentSpecificParameters($order));
        /* ///@TODO
        $parameters = [
            'OrderDesc' => 'TODO',
            'UserData' => 'TODO',
            'ReqId' => 'TODO', // RequestId um Request unique zu machen
        ];
        */

        return $this->getEncryptedParameters();
    }
}
