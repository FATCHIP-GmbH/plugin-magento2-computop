<?php

namespace Fatchip\Computop\Helper;

use Fatchip\Computop\Model\ComputopConfig;

class Payment extends Base
{
    /**
     * List of all currently available Computop payment methods
     *
     * @var array
     */
    protected $availablePayments = [
        ComputopConfig::METHOD_CREDITCARD,
    ];

    /**
     * Return all available payment types
     *
     * @return array
     */
    public function getAvailablePaymentTypes()
    {
        return $this->availablePayments;
    }
}