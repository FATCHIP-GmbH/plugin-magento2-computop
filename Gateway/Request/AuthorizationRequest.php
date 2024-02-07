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
     * Constructor
     *
     * @param \Fatchip\Computop\Model\Api\Request\Authorization $authRequest
     */
    public function __construct(
        \Fatchip\Computop\Model\Api\Request\Authorization $authRequest
    ) {
        $this->authRequest = $authRequest;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentData = SubjectReader::readPayment($buildSubject);
        $amount = SubjectReader::readAmount($buildSubject);

        return $this->authRequest->generateRequest($paymentData->getPayment()->getOrder(), $paymentData->getPayment(), $amount);
    }
}
