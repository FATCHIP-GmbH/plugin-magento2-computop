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
