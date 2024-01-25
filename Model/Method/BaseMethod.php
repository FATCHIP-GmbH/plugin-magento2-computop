<?php

namespace Fatchip\Computop\Model\Method;

use Magento\Payment\Model\Method\Adapter;

class BaseMethod extends Adapter
{
    /**
     * Return configured instructions
     *
     * @param int|null $storeId
     * @return string
     */
    public function getInstructions($storeId = null)
    {
        return (bool)$this->getConfigData('instructions', $storeId);
    }
}