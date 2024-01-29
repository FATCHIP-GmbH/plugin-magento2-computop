<?php

namespace Fatchip\Computop\Model\Method;

use Fatchip\Computop\Model\ComputopConfig;

class Creditcard extends BaseMethod
{
    /**
     * Method identifier of this payment method
     *
     * @var string
     */
    protected $methodCode = ComputopConfig::METHOD_CREDITCARD;
}