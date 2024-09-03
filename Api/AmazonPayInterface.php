<?php

namespace Fatchip\Computop\Api;

interface AmazonPayInterface
{
    /**
     * Get Amazon Pay button parameters
     *
     * @param  string $orderId
     * @return \Fatchip\Computop\Service\V1\Data\AmazonPayResponse
     */
    public function getAmazonPayApbSession($orderId);
}
