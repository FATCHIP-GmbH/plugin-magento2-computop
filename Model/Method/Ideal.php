<?php

namespace Fatchip\Computop\Model\Method;

use Fatchip\Computop\Helper\Payment;
use Fatchip\Computop\Model\Api\Request\Capture;
use Fatchip\Computop\Model\Api\Request\Credit;
use Fatchip\Computop\Model\ResourceModel\IdealIssuerList;
use Fatchip\Computop\Model\ComputopConfig;
use Fatchip\Computop\Model\Source\CaptureMethods;
use Fatchip\Computop\Model\Source\IdealService;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;

class Ideal extends RedirectPayment
{
    /**
     * @var IdealIssuerList
     */
    protected $idealIssuerResource;

    /**
     * Can be used to assign data from frontend to info instance
     *
     * @var array
     */
    protected $assignKeys = [
        'issuer',
    ];

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
     * @param Payment $paymentHelper
     * @param Capture $captureRequest
     * @param Credit $creditRequest
     * @param InvoiceService $invoiceService
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     * @param IdealIssuerList $idealIssuerResource
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
        Payment $paymentHelper,
        Capture $captureRequest,
        Credit $creditRequest,
        InvoiceService $invoiceService,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        IdealIssuerList $idealIssuerResource,
        CommandPoolInterface $commandPool = null,
        ValidatorPoolInterface $validatorPool = null,
        CommandManagerInterface $commandExecutor = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct($eventManager, $valueHandlerPool, $paymentDataObjectFactory, $code, $formBlockType, $infoBlockType, $urlBuilder, $authRequest, $checkoutSession, $paymentHelper, $captureRequest, $creditRequest, $invoiceService, $orderSender, $invoiceSender, $commandPool, $validatorPool, $commandExecutor, $logger);
        $this->idealIssuerResource = $idealIssuerResource;
    }

    /**
     * Method identifier of this payment method
     *
     * @var string
     */
    protected $methodCode = ComputopConfig::METHOD_IDEAL;

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint = "ideal.aspx";

    /**
     * @return string
     */
    public function getCaptureMode()
    {
        // Ideal has no capture mode, there orders are already paid when finished so it is always AUTO
        return CaptureMethods::CAPTURE_AUTO;
    }

    /**
     * Returns is PPRO service is configured
     *
     * @return bool
     */
    protected function isPproMode()
    {
        if ($this->getPaymentConfigParam('service') == IdealService::SERVICE_PPRO) {
            return true;
        }
        return false;
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order|null $order
     * @return array
     */
    public function getPaymentSpecificParameters(Order $order = null)
    {
        $infoInstance = $this->getInfoInstance();

        $params = [
            'OrderDesc' => $order->getIncrementId(), // Not sending the OrderDesc parameter can result in "Message format error" errors!
        ];
        if ($this->isPproMode() === false) {
            $params['IssuerID'] = $infoInstance->getAdditionalInformation('issuer');
        }
        return $params;
    }

    /**
     * Hook for extension by the real payment method classes
     *
     * @return array
     */
    public function getFrontendConfig()
    {
        $config = [
            'service' => $this->getPaymentConfigParam('service'),
        ];
        if ($this->isPproMode() === false) {
            $config['issuerList'] = $this->idealIssuerResource->getIssuerList();
        }
        return $config;
    }
}
