<?php

namespace Fatchip\Computop\Model\Api\Request;

use Fatchip\Computop\Model\ComputopConfig;
use Fatchip\Computop\Model\Method\BaseMethod;
use Magento\Payment\Model\InfoInterface;

class Credit extends Base
{
    const REQUEST_TYPE = "CREDIT";

    /**
     * Defines request type to be seen in API Log
     *
     * @var string
     */
    protected $requestType = self::REQUEST_TYPE;

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint = "credit.aspx";

    protected function getTruncatedTransactionId(InfoInterface $payment)
    {
        $transId = $payment->getTransactionId();
        if (strpos($transId, '-') !== false) {
            $split = explode('-', $transId);
            return $split[0];
        }
        return $transId;
    }

    public function generateRequest(InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();

        /** @var BaseMethod $methodInstance */
        $methodInstance = $payment->getMethodInstance();

        $this->addParameter('Currency', $order->getOrderCurrencyCode());
        $this->addParameter('Amount', $this->apiHelper->formatAmount($amount));
        $this->addParameter('PayID', $order->getComputopPayid());

        $this->addParameter('TransID', $this->getTruncatedTransactionId($payment)); // Generate new TransID for further use with this transaction
        $this->addParameter('ReqId', $this->paymentHelper->getRequestId());
        $this->addParameter('EtiID', $this->apiHelper->getIdentString());
        $this->addParameter('RefNr', $this->apiHelper->getReferenceNumber($order->getIncrementId()));

        $params = $this->getParameters();

        return $params;
    }

    /**
     * Send credit request to Computop API
     *
     * @param  InfoInterface $payment
     * @param  float         $amount
     * @return array
     */
    public function sendRequest(InfoInterface $payment, $amount)
    {
        $params = $this->generateRequest($payment, $amount);
        $response = $this->handleStandardCurlRequest($params, $payment->getOrder());
        if (empty($response)) {
            throw new \Exception("An unknown error occured.");
        }
        if ($response['Status'] != ComputopConfig::STATUS_CODE_SUCCESS) {
            throw new \Exception("An error occured: ".strtolower($response['Description']));
        }
        return $response;
    }
}
