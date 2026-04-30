<?php

namespace Fatchip\Computop\Service\V1\Data;

use Fatchip\Computop\Api\Data\DeviceFingerprintSentResponseInterface;

class DeviceFingerprintSentResponse extends \Magento\Framework\Api\AbstractExtensibleObject implements DeviceFingerprintSentResponseInterface
{
    /**
     * Returns if the request was successful
     *
     * @return bool
     */
    public function getSuccess()
    {
        return $this->_get('success');
    }
}