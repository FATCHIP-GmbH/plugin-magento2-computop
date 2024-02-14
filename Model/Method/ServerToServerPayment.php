<?php

namespace Fatchip\Computop\Model\Method;

use Magento\Payment\Model\InfoInterface;

abstract class ServerToServerPayment extends BaseMethod
{
    /**
     * @inheritdoc
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
        }

        $response = $this->authRequest->sendCurlRequest($payment->getOrder(), $payment, $amount);
        ///@TODO Add status check for FAILED

        return $this;
    }
}
