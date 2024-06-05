<?php

namespace Fatchip\Computop\Model\Api\Request;

use Fatchip\Computop\Model\ComputopConfig;
use Fatchip\Computop\Model\Method\BaseMethod;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Address;
use Magento\Quote\Model\Quote;

class Authorization extends Base
{
    /**
     * @var \Fatchip\Computop\Helper\Country
     */
    protected $countryHelper;

    /**
     * Constructor
     *
     * @param \Fatchip\Computop\Helper\Payment $paymentHelper
     * @param \Fatchip\Computop\Helper\Api $apiHelper
     * @param \Fatchip\Computop\Model\Api\Encryption\Blowfish $blowfish
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Fatchip\Computop\Model\ResourceModel\ApiLog $apiLog
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Fatchip\Computop\Helper\Country $countryHelper
     */
    public function __construct(
        \Fatchip\Computop\Helper\Payment $paymentHelper,
        \Fatchip\Computop\Helper\Api $apiHelper,
        \Fatchip\Computop\Model\Api\Encryption\Blowfish $blowfish,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Fatchip\Computop\Model\ResourceModel\ApiLog $apiLog,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Fatchip\Computop\Helper\Country $countryHelper
    ) {
        parent::__construct($paymentHelper, $apiHelper, $blowfish, $curl, $apiLog, $checkoutSession);
        $this->countryHelper = $countryHelper;
    }

    /**
     * @param  BaseMethod $methodInstance
     * @param  double     $amount
     * @param  string     $currency
     * @param  string     $refNr
     * @param  Order|null $order
     * @param  bool       $log
     * @param  bool       $encrypt
     * @return array
     */
    public function generateRequest(BaseMethod $methodInstance, $amount, $currency, $refNr, Order $order = null, $encrypt = false, $log = false)
    {
        $this->addParameter('Currency', $currency);
        $this->addParameter('Amount', $this->apiHelper->formatAmount($amount));

        $this->addParameter('TransID', $this->getTransactionId());
        $this->addParameter('ReqId', $this->paymentHelper->getRequestId());
        $this->addParameter('EtiID', $this->apiHelper->getIdentString());

        $this->addParameter('RefNr', $this->apiHelper->getReferenceNumber($refNr));

        $this->addParameter('URLSuccess', $methodInstance->getSuccessUrl());
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

    /**
     * @param  Order   $order
     * @param  Payment $payment
     * @param  double  $amount
     * @param  bool    $log
     * @param  bool    $encrypt
     * @return array
     */
    public function generateRequestFromOrder(Order $order, Payment $payment, $amount, $encrypt = false, $log = false)
    {
        /** @var BaseMethod $methodInstance */
        $methodInstance = $payment->getMethodInstance();

        $amount = $order->getTotalDue(); // given amount is in base-currency - order currency is needed for transfer to computop
        $currency = $order->getOrderCurrencyCode();
        $refNr = $order->getIncrementId();

        if ($methodInstance->isAddressDataNeeded() === true) {
            $this->addParameter('billingAddress', $this->getAddressInfo($order->getBillingAddress()));
            $this->addParameter('shippingAddress', $this->getAddressInfo($order->getShippingAddress()));
        }

        return $this->generateRequest($methodInstance, $amount, $currency, $refNr, $order, $encrypt, $log);
    }

    public function generateRequestFromQuote(Quote $quote, BaseMethod $methodInstance, $encrypt = false, $log = false)
    {
        $amount = $quote->getGrandTotal();
        $currency = $quote->getQuoteCurrencyCode();
        $refNr = $methodInstance->getTemporaryRefNr($quote->getId());

        return $this->generateRequest($methodInstance, $amount, $currency, $refNr, null, $encrypt, $log);
    }

    /**
     * Returns address string (json and base64 encoded)
     *
     * @param  Address $address
     * @return string
     */
    protected function getAddressInfo(Address $address)
    {
        $street = $address->getStreet();
        $street = is_array($street) ? implode(' ', $street) : $street; // street may be an array
        $address = [
            'city' => $address->getCity(),
            'country' => [
                'countryA3' => $this->countryHelper->getIso3Code($address->getCountryId()),
            ],
            'addressLine1' => [
                'street' => trim($street ?? ''),
                #'streetNumber' => '', // do we have to split the address in street and number?
            ],
            'postalCode' => $address->getPostcode(),
        ];
        return base64_encode(json_encode($address));
    }

    public function sendCurlRequest(Order $order, Payment $payment, $amount)
    {
        /** @var BaseMethod $methodInstance */
        $methodInstance = $payment->getMethodInstance();

        $params = $this->generateRequestFromOrder($order, $payment, $amount);
        $response = $this->handlePaymentCurlRequest($methodInstance, $params, $order);

        return $response;
    }
}
