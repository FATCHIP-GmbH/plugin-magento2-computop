<?php

namespace Fatchip\Computop\Api;

interface StartPayPalExpressInterface
{
    /**
     * Logs the PPE auth request and set PPE as used payment method
     *
     * @param  string $cartId
     * @param  string $data
     * @param  string $len
     * @return \Fatchip\Computop\Service\V1\Data\StartPayPalExpressResponse
     */
    public function start($cartId, $data, $len);
}
