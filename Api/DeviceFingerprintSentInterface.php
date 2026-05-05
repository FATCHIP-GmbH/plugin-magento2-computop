<?php

namespace Fatchip\Computop\Api;

interface DeviceFingerprintSentInterface
{
    /**
     * Set session flag to indicate that DFP was sent
     *
     * @return \Fatchip\Computop\Service\V1\Data\DeviceFingerprintSentResponse
     */
    public function markDfpAsSent();
}
