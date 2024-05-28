<?php

namespace Fatchip\Computop\Plugin;

use Fatchip\Computop\Model\Api\Request\Credit;
use Magento\Sales\Model\Service\CreditmemoService as CreditmemoServiceOriginal;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Fatchip\Computop\Model\ResourceModel\ApiLog;

class CreditmemoService
{
    /**
     * Checkout session model
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var ApiLog
     */
    protected $apiLog;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param ApiLog $apiLog
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        ApiLog $apiLog
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->apiLog = $apiLog;
    }

    /**
     * @param  CreditmemoServiceOriginal $subject
     * @param  callable                  $proceed
     * @param  CreditmemoInterface       $creditmemo
     * @param  bool                      $offlineRequested
     * @return CreditmemoInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundRefund(
        CreditmemoServiceOriginal $subject,
        callable $proceed,
        CreditmemoInterface $creditmemo,
        $offlineRequested = false
    ) {
        try {
            $return = $proceed($creditmemo, $offlineRequested);
        } catch (\Exception $ex) {
            $apiLogData = $this->checkoutSession->getApiLogData();
            if (!empty($apiLogData['request'])) {
                // Rewrite the log-entry after it was rolled back in the db-transaction
                $this->apiLog->addApiLogEntry($apiLogData['type'], $apiLogData['request'], $apiLogData['response']);
            }
            $this->checkoutSession->getApiLogData();
            throw $ex;
        }
        $this->checkoutSession->getApiLogData();
        return $return;
    }
}