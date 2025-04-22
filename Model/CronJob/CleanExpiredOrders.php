<?php

namespace Fatchip\Computop\Model\CronJob;

use Fatchip\Computop\Helper\Base;
use Fatchip\Computop\Model\Api\Request\Inquire;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\StoresConfig;
use Magento\Sales\Model\Order;

/**
 * Class that provides functionality of cleaning expired quotes by cron
 */
class CleanExpiredOrders
{
    /**
     * @var Base
     */
    protected $baseHelper;

    /**
     * @var CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var Inquire
     */
    protected $inquireRequest;

    /**
     * @param CollectionFactory             $collectionFactory
     * @param Base                          $baseHelper
     * @param Inquire                       $inquireRequest
     * @param OrderManagementInterface|null $orderManagement
     */
    public function __construct(
        CollectionFactory        $collectionFactory,
        Base                     $baseHelper,
        Inquire                  $inquireRequest,
        OrderManagementInterface $orderManagement = null
    )
    {
        $this->orderCollectionFactory = $collectionFactory;
        $this->baseHelper = $baseHelper;
        $this->inquireRequest = $inquireRequest;
        $this->orderManagement = $orderManagement ?: ObjectManager::getInstance()->get(OrderManagementInterface::class);
    }

    protected function isPaymentStatusFailed($inquireResponse)
    {
        if (!empty($inquireResponse)) {
            if (!empty($inquireResponse['LastStatus']) && $inquireResponse['LastStatus'] == 'FAILED') {
                return true;
            }

            if (!empty($inquireResponse['Status']) && !empty($inquireResponse['Description']) && $inquireResponse['Status'] == 'FAILED' && $inquireResponse['Description'] == 'PAYMENT NOT FOUND') {
                return true;
            }
        }
        return false;
    }

    /**
     * Clean expired quotes (cron process)
     *
     * @return void
     */
    public function execute()
    {
        $lifetime = $this->baseHelper->getConfigParam('cronjob_pending_lifetime');

        $orders = $this->orderCollectionFactory->create();
        $orders->addFieldToFilter('status', 'pending');
        $orders->getSelect()->where(
            new \Zend_Db_Expr('TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP, `updated_at`)) >= ' . $lifetime * 60)
        );

        foreach ($orders->getAllIds() as $entityId) {
            $order = $orders->fetchItem();
            $inquireResponse = $this->inquireRequest->getPaymentStatusByTransId($order->getIncrementId());
            if ($this->isPaymentStatusFailed($inquireResponse) === true) {
                $this->orderManagement->cancel((int)$entityId);
            }
        }
    }
}
