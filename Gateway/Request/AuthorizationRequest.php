<?php

namespace Fatchip\Computop\Gateway\Request;

use Fatchip\Computop\Model\Method\BaseMethod;
use Magento\Payment\Gateway\Request\BuilderInterface;

use Magento\Payment\Gateway\Helper\SubjectReader;

class AuthorizationRequest implements BuilderInterface
{
    /**
     * @var \Fatchip\Computop\Model\Api\Request\Authorization
     */
    protected $authRequest;

    /**
     * @var \Fatchip\Computop\Model\Api\OAuth
     */
    protected $oauth;

    /**
     * Constructor
     *
     * @param \Fatchip\Computop\Model\Api\Request\Authorization $authRequest
     * @param \Fatchip\Computop\Model\Api\OAuth $oauth
     */
    public function __construct(
        \Fatchip\Computop\Model\Api\Request\Authorization $authRequest,
        \Fatchip\Computop\Model\Api\OAuth $oauth
    ) {
        $this->authRequest = $authRequest;
        $this->oauth = $oauth;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $this->oauth->getOAuthToken();

        $paymentData = SubjectReader::readPayment($buildSubject);
        $amount = SubjectReader::readAmount($buildSubject);

        $payment = $paymentData->getPayment();
        $order = $payment->getOrder();

        $requestInfo = $this->authRequest->generateRequest($order, $payment, $amount);

        /** @var BaseMethod $methodInstance */
        $methodInstance = $payment->getMethodInstance();

        $requestInfo['uri'] = $methodInstance->getApiEndpoint();

        return $requestInfo;
    }
}
