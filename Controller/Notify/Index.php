<?php

namespace Fatchip\Computop\Controller\Onepage;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Fatchip\Computop\Model\ResourceModel\ApiLog
     */
    protected $apiLog;

    /**
     * @var \Fatchip\Computop\Model\Api\Encryption\Blowfish
     */
    protected $blowfish;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context           $context
     * @param \Fatchip\Computop\Model\ResourceModel\ApiLog    $apiLog
     * @param \Fatchip\Computop\Model\Api\Encryption\Blowfish $blowfish
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Fatchip\Computop\Model\ResourceModel\ApiLog $apiLog,
        \Fatchip\Computop\Model\Api\Encryption\Blowfish $blowfish
    ) {
        parent::__construct($context);
        $this->apiLog = $apiLog;
        $this->blowfish = $blowfish;
    }

    /**
     * Handles return to shop
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->checkoutSession->unsComputopCustomerIsRedirected();

        $response = $this->blowfish->ctDecrypt($this->getRequest()->getParam('Data'), $this->getRequest()->getParam('Len'));
        $this->apiLog->addApiLogEntry('NOTIFY', $response);

        die('OK');
    }
}
