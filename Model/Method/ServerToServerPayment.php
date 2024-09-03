<?php

namespace Fatchip\Computop\Model\Method;

use Magento\Payment\Model\InfoInterface;

abstract class ServerToServerPayment extends BaseMethod
{
    /**
     * Determine if order will be finished directly after auth call or later
     *
     * @var bool
     */
    protected $finishOrderAfterAuth = true;

    /**
     * Determine if order will be finished directly after auth call or later
     *
     * @var bool
     */
    protected $handleSpecificAfterAuth = false;

    /**
     * @inheritdoc
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
        }

        $response = $this->authRequest->sendCurlRequest($payment->getOrder(), $payment, $amount);

        $this->handleResponse($payment, $response, $this->finishOrderAfterAuth);

        if ($this->handleSpecificAfterAuth === true) {
            $this->handleResponseSpecific($payment, $response);
        }
        return $this;
    }
}
