<?php

namespace Fatchip\Computop\Model\Entities;

use Magento\Framework\Model\AbstractModel;

/**
 * ApiLog entity model
 */
class ApiLog extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Fatchip\Computop\Model\ResourceModel\ApiLog');
    }

    /**
     * Returns json decoded request
     *
     * @return array
     */
    public function getRequestDetails()
    {
        return json_decode($this->getData('request_details'), true);
    }

    /**
     * Returns json decoded response
     *
     * @return array
     */
    public function getResponseDetails()
    {
        return json_decode($this->getData('response_details'), true);
    }
}
