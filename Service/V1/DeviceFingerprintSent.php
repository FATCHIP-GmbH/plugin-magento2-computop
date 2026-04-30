<?php

namespace Fatchip\Computop\Service\V1;

use Fatchip\Computop\Api\Data\DeviceFingerprintSentResponseInterfaceFactory;
use Fatchip\Computop\Api\DeviceFingerprintSentInterface;

class DeviceFingerprintSent implements DeviceFingerprintSentInterface
{
    /**
     * Factory for the response object
     *
     * @var DeviceFingerprintSentResponseInterfaceFactory
     */
    protected $responseFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Constructor.
     *
     * @param DeviceFingerprintSentResponseInterfaceFactory $responseFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        DeviceFingerprintSentResponseInterfaceFactory $responseFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->responseFactory = $responseFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Mark DeviceFingerprint as sent
     *
     * @return \Fatchip\Computop\Service\V1\Data\DeviceFingerprintSentResponse
     */
    public function markDfpAsSent()
    {
        /** @var \Fatchip\Computop\Service\V1\Data\DeviceFingerprintSentResponse $response */
        $response = $this->responseFactory->create();
        $response->setData('success', true);

        $this->checkoutSession->setComputopRatepayDfpSent(true);
        return $response;
    }
}
