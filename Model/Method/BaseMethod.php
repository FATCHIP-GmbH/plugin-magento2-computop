<?php

namespace Fatchip\Computop\Model\Method;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Adapter;
use Psr\Log\LoggerInterface;
use Magento\Payment\Gateway\Config\Config;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;

abstract class BaseMethod extends Adapter
{
    /**
     * Method identifier of this payment method
     *
     * @var string
     */
    protected $methodCode;

    /**
     * URL to Computop API
     *
     * @var string
     */
    protected $apiBaseUrl = "https://www.computop-paygate.com/";

    /**
     * Can be used to assign data from frontend to info instance
     *
     * @var array
     */
    protected $assignKeys;

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
     * @var \Fatchip\Computop\Model\Api\Request\Authorization
     */
    protected $authRequest;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @param ManagerInterface $eventManager
     * @param ValueHandlerPoolInterface $valueHandlerPool
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param string $code
     * @param string $formBlockType
     * @param string $infoBlockType
     * @param \Magento\Framework\Url $urlBuilder
     * @param \Fatchip\Computop\Model\Api\Request\Authorization $authRequest
     * @param \Magento\Checkout\Model\Session $checkoutSession
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
        \Fatchip\Computop\Model\Api\Request\Authorization $authRequest,
        \Magento\Checkout\Model\Session $checkoutSession,
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
        $this->authRequest = $authRequest;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Returns the API endpoint
     *
     * @return string
     */
    public function getApiEndpoint()
    {
        return rtrim($this->apiBaseUrl, "/")."/".$this->apiEndpoint;
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
        return "https://robert.demoshop.fatchip.de/magento_computop246/pub/ctNotify.php"; //@TODO: remove
        return $this->urlBuilder->getUrl('computop/notify/index');
    }


    /**
     * Add the checkout-form-data to the checkout session
     *
     * @param  DataObject $data
     * @return $this
     */
    public function assignData(DataObject $data)
    {
        parent::assignData($data);

        if (!empty($this->assignKeys)) {
            $infoInstance = $this->getInfoInstance();
            $additionalData = $data->getAdditionalData();
            foreach ($this->assignKeys as $key) {
                if (!empty($additionalData[$key])) {
                    $infoInstance->setAdditionalInformation($key, $additionalData[$key]);
                }
            }
        }
        return $this;
    }
}
