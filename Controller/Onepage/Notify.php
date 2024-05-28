<?php

namespace Fatchip\Computop\Controller\Onepage;

class Notify extends \Magento\Framework\App\Action\Action
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
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context           $context
     * @param \Fatchip\Computop\Model\ResourceModel\ApiLog    $apiLog
     * @param \Fatchip\Computop\Model\Api\Encryption\Blowfish $blowfish
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Fatchip\Computop\Model\ResourceModel\ApiLog $apiLog,
        \Fatchip\Computop\Model\Api\Encryption\Blowfish $blowfish,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
    ) {
        parent::__construct($context);
        $this->apiLog = $apiLog;
        $this->blowfish = $blowfish;
        $this->resultRawFactory = $resultRawFactory;
    }

    /**
     * Handles return to shop
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRaw = $this->resultRawFactory->create();
        if (!empty($this->getRequest()->getParam('Data') && !empty($this->getRequest()->getParam('Len')))) {
            $response = $this->blowfish->ctDecrypt($this->getRequest()->getParam('Data'), $this->getRequest()->getParam('Len'));
            $this->apiLog->addApiLogEntry('NOTIFY', $response);

            $resultRaw->setContents('OK');
        } else {
            $resultRaw->setContents('ERROR');
        }

        return $resultRaw;
    }
}
