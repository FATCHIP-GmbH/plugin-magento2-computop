<?php

namespace Fatchip\Computop\Model\Method;

use Magento\Payment\Model\InfoInterface;

abstract class RedirectPayment extends BaseMethod
{
    /**
     * @var string
     */
    protected $requestType = "REDIRECT";

    /**
     * @var bool
     */
    protected $addLanguageToUrl = false;

    /**
     * Returns if auth request is needed
     * Can be overloaded by other classes
     *
     * @return bool
     */
    protected function isAuthRequestNeeded()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
        }

        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        $transactionId = false;
        if ($this->isAuthRequestNeeded()) {
            $request = $this->authRequest->generateRequestFromOrder($order, $payment, $amount, true, true);
            if ($this->addLanguageToUrl === true) {
                $request['language'] = strtolower($this->apiHelper->getStoreLocale());
            }

            $url = $this->authRequest->getFullApiEndpoint($this->getApiEndpoint())."?".http_build_query($request);

            $this->checkoutSession->setComputopRedirectUrl($url);

            $params = $this->authRequest->getParameters();
            $transactionId = $params['TransID'];
        }

        if ($this->setTransactionPreAuthorization === true) {
            if ($transactionId === false) {
                // This is needed for CC Silent mode. TransactionId is generated before order creation and will later be used for auth request
                $transactionId = $this->paymentHelper->getTransactionId();
            }
            $this->setTransactionId($payment, $transactionId);
        }

        return $this;
    }
}
