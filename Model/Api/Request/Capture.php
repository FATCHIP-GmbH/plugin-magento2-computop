<?php

namespace Fatchip\Computop\Model\Api\Request;

use Fatchip\Computop\Model\ComputopConfig;
use Fatchip\Computop\Model\Method\BaseMethod;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;

class Capture extends Base
{
    /**
     * Defines request type to be seen in API Log
     *
     * @var string
     */
    protected $requestType = "CAPTURE";

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint = "capture.aspx";

    public function generateRequest(InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();

        $this->setStoreCode($order->getStore()->getCode());

        /** @var BaseMethod $methodInstance */
        $methodInstance = $payment->getMethodInstance();

        $this->addParameter('Currency', $order->getOrderCurrencyCode());
        $this->addParameter('Amount', $this->apiHelper->formatAmount($amount));
        $this->addParameter('PayID', $order->getComputopPayid());

        $this->addParameter('TransID', $this->apiHelper->getTruncatedTransactionId($payment->getTransactionId())); // Generate new TransID for further use with this transaction
        $this->addParameter('ReqId', $this->paymentHelper->getRequestId());
        $this->addParameter('EtiID', $this->apiHelper->getIdentString());
        $this->addParameter('RefNr', $this->apiHelper->getReferenceNumber($order->getIncrementId()));

        $params = $this->getParameters();

        return $params;
    }

    /**
     * Send capture request to Computop API
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
        if ($this->apiHelper->isSuccessStatus($response) === false) {
            if ($response['Status'] == 'FAILED' && $response['Description'] == 'DISABLED') {
                throw new LocalizedException(__('Capture failed. Capture is not available for this payment method if this is a test order.'));
            }
            throw new \Exception("An error occured: ".strtolower($response['Description']));
        }
        return $response;
    }
}
