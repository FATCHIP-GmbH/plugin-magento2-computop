<?php

namespace Fatchip\Computop\Model\Method;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Payment\Model\Method\Adapter;
use Psr\Log\LoggerInterface;
use Magento\Payment\Gateway\Config\Config;
use Magento\Sales\Model\Order;

class BaseMethod extends Adapter
{
    /**
     * Method identifier of this payment method
     *
     * @var string
     */
    protected $methodCode;

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint;

    /**
     * Url builder object
     *
     * @var \Magento\Framework\Url
     */
    protected $urlBuilder;

    /**
     * @param ManagerInterface $eventManager
     * @param ValueHandlerPoolInterface $valueHandlerPool
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param string $code
     * @param string $formBlockType
     * @param string $infoBlockType
     * @param \Magento\Framework\Url $urlBuilder
     * @param CommandPoolInterface|null $commandPool
     * @param ValidatorPoolInterface|null $validatorPool
     * @param CommandManagerInterface|null $commandExecutor
     * @param LoggerInterface|null $logger
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ManagerInterface $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        $code,
        $formBlockType,
        $infoBlockType,
        \Magento\Framework\Url $urlBuilder,
        CommandPoolInterface $commandPool = null,
        ValidatorPoolInterface $validatorPool = null,
        CommandManagerInterface $commandExecutor = null,
        LoggerInterface $logger = null
    ) {
        if (empty($this->methodCode)) {
            throw new \Exception("MethodCode is empty!");
        }
        $code = $this->methodCode;

        parent::__construct($eventManager, $valueHandlerPool, $paymentDataObjectFactory, $code, $formBlockType, $infoBlockType, $commandPool, $validatorPool, $commandExecutor, $logger);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Returns the API endpoint
     *
     * @return string
     */
    public function getApiEndpoint()
    {
        return $this->apiEndpoint;
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order $order
     * @return array
     */
    public function getPaymentSpecificParameters(Order $order)
    {
        return []; // filled in child classes
    }

    /**
     * Returns redirect url for success case
     *
     * @param  Order $order
     * @return string|null
     */
    public function getSuccessUrl(Order $order)
    {
        return $this->urlBuilder->getUrl('computop/onepage/returned');
    }

    /**
     * Returns redirect url for failure case
     *
     * @return string|null
     */
    public function getFailureUrl()
    {
        return $this->urlBuilder->getUrl('computop/onepage/failure');
    }

    /**
     * Returns URL for notify controller
     *
     * @return string|null
     */
    public function getNotifyUrl()
    {
        return $this->urlBuilder->getUrl('computop/notify/index');
    }
}
