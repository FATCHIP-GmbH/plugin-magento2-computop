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
     * @inheritdoc
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
        }

        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false); // dont send email now, will be sent on appointed

        $request = $this->authRequest->generateRequest($order, $payment, $amount, true, true, true);
        $url = $this->getApiEndpoint()."?".http_build_query($request);

        $this->checkoutSession->setComputopRedirectUrl($url);



        return $this;
    }
}
