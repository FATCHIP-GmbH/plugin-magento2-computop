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
     * @param  bool    $log
     * @param  bool    $encrypt
     * @return array
     */
    public function generateRequest(Order $order, Payment $payment, $amount, $encrypt = false, $log = false)
    {
        $amount = $order->getTotalDue(); // given amount is in base-currency - order currency is needed for transfer to computop

        /** @var BaseMethod $methodInstance */
        $methodInstance = $payment->getMethodInstance();

        $this->addParameter('Currency', $order->getOrderCurrencyCode());
        $this->addParameter('Amount', $this->formatAmount($amount)); ///@TODO: Check if amount is base-currency or order-currency

        $this->addParameter('TransID', $this->paymentHelper->getTransactionId()); // Generate new TransID for further use with this transaction @TODO: Extend order table and save TransactionID
        $this->addParameter('ReqId', $this->paymentHelper->getRequestId()); // @TODO: Does this need to be safed? Check

        #$this->addParameter('IPAddr', 'TODO'); // @TODO: IPaddress needed?
        #$this->addParameter('orderDesc', 'Demoshop'); // @TODO: Generate a orderDesc with Shopname? or product titles?
        #$this->addParameter('userData', 'Shopware Version: 5.7.19 Modul Version: 1.1.18'); // @TODO: Generate a shop-version + module-version string
        #$this->addParameter('EtiId', 'Shopware Version: 5.7.19 Modul Version: 1.1.18'); // @TODO: Generate a shop-version + module-version string
        #$this->addParameter('language', 'de'); // @TODO: Transmit used language?
        #$this->addParameter(''addrCountryCode', 'DE'); // @TODO: Add customers country code - billing I guess

        $this->addParameter('RefNr', $order->getIncrementId()); // Generate new TransID for further use with this transaction @TODO: Extend order table and save TransactionID

        $this->addParameter('URLSuccess', $methodInstance->getSuccessUrl($order));
        $this->addParameter('URLFailure', $methodInstance->getFailureUrl());
        $this->addParameter('URLNotify', $methodInstance->getNotifyUrl());
        $this->addParameter('Response', 'encrypt');

        $this->addParameters($methodInstance->getPaymentSpecificParameters($order));

        $params = $this->getParameters();

        if ($log === true) {
            $this->apiLog->addApiLogEntry($methodInstance->getRequestType(), $params, null, $order);
        }

        if ($encrypt === true) {
            $params = $this->getEncryptedParameters($params);
        }
        return $params;
    }

    public function sendCurlRequest(Order $order, Payment $payment, $amount)
    {
        /** @var BaseMethod $methodInstance */
        $methodInstance = $payment->getMethodInstance();

        $response = $this->handleCurlRequest($methodInstance->getApiEndpoint(), $methodInstance->getRequestType(), $this->generateRequest($order, $payment, $amount), $order);
        ///@TODO: Handle response - check for failed and throw exception
    }
}
