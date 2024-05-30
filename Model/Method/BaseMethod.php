<?php

namespace Fatchip\Computop\Model\Method;

use Fatchip\Computop\Helper\Payment;
use Fatchip\Computop\Model\Api\Request\Capture;
use Fatchip\Computop\Model\Api\Request\Credit;
use Fatchip\Computop\Model\ComputopConfig;
use Fatchip\Computop\Model\Source\CaptureMethods;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
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
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

abstract class BaseMethod extends Adapter
{
    /**
     * Method identifier of this payment method
     *
     * @var string
     */
    protected $methodCode;

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
     * @var string
     */
    protected $requestType = "";

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var Payment
     */
    protected $paymentHelper;

    /**
     * @var Capture
     */
    protected $captureRequest;

    /**
     * @var Credit
     */
    protected $creditRequest;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * OrderSender object
     *
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var LoggerInterface
     */
    protected $loggerObject;

    /**
     * InvoiceSender object
     *
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * Defines if transaction id is set pre or post authorization
     * True = pre auth
     * False = post auth with response
     *
     * @var bool
     */
    protected $setTransactionPreAuthorization = true;

    /**
     * Determines if auth requests adds address parameters to the request
     *
     * @var bool
     */
    protected $sendAddressData = false;

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
        $this->paymentHelper = $paymentHelper;
        $this->captureRequest = $captureRequest;
        $this->creditRequest = $creditRequest;
        $this->invoiceService = $invoiceService;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
        $this->loggerObject = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
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
     * Returns request type
     *
     * @return string
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * Returns if address parameters have to be added in auth request
     *
     * @return bool
     */
    public function isAddressDataNeeded()
    {
        return $this->sendAddressData;
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order|null $order
     * @return array
     */
    public function getPaymentSpecificParameters(Order $order = null)
    {
        return []; // filled in child classes
    }

    /**
     * Returns redirect url for success case
     *
     * @return string|null
     */
    public function getSuccessUrl()
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
        return $this->urlBuilder->getUrl('computop/notify');
    }

    /**
     * @return string
     */
    public function getCaptureMode()
    {
        return $this->getPaymentConfigParam('capture_method');
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

    public function handleResponse(InfoInterface $payment, $response)
    {
        if ($this->authRequest->getApiHelper()->isSuccessStatus($response) === false) {
            throw new LocalizedException(__($response['Description'] ?? 'Error'));
        }

        if ($this->setTransactionPreAuthorization === false) { // false = set POST auth
            $this->setTransactionId($payment, $response['TransID'], true);
        }

        $order = $payment->getOrder();
        $order->setComputopPayid($response['PayID']);
        $order->save();


        if (!$order->getEmailSent()) { // the email should not have been sent at this given moment, but some custom modules may have changed this behaviour
            try {
                $this->orderSender->send($order);
            } catch (\Exception $e) {
                $this->loggerObject->critical($e);
            }
        }

        if ($this->getCaptureMode() == CaptureMethods::CAPTURE_AUTO && in_array($response['Status'], [ComputopConfig::STATUS_AUTHORIZED, ComputopConfig::STATUS_OK])) {
            if ($order->getInvoiceCollection()->count() == 0) {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->setRequestedCaptureCase(Invoice::NOT_CAPTURE);
                $invoice->setTransactionId($order->getPayment()->getLastTransId());
                $invoice->register();
                $invoice->pay();
                $invoice->save();

                $order->save();

                $this->invoiceSender->send($invoice);
            }
        }
    }

    public function setTransactionId(InfoInterface $payment, $transactionId, $save = false)
    {
        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionClosed(0);
        if ($save === true) {
            $payment->save();
        }
    }

    /**
     * Trying to retrieve current storecode from various sources
     *
     * @return string|null
     */
    protected function getStoreCode()
    {
        try {
            $infoInstance = $this->getInfoInstance();
            if (empty($infoInstance)) {
                return null;
            }
        } catch (\Exception $exc) {
            return null;
        }

        $order = $infoInstance->getOrder();
        if (empty($order)) {
            $order = $infoInstance->getQuote();
            if (empty($order)) {
                return null;
            }
        }

        $store = $order->getStore();
        if (empty($store)) {
            return null;
        }
        return $store->getCode();
    }

    /**
     * Returns a config param for this payment type
     *
     * @param  string $param
     * @param  string $storeCode
     * @return string
     */
    public function getPaymentConfigParam($param, $storeCode = null)
    {
        if ($storeCode === null) {
            $storeCode = $this->getStoreCode();
        }
        return $this->paymentHelper->getConfigParam($param, $this->getCode(), 'computop_payment', $storeCode);
    }

    /**
     * Hook for extension by the real payment method classes
     *
     * @return array
     */
    public function getFrontendConfig()
    {
        return [
            'requestBic' => (bool)$this->getPaymentConfigParam('request_bic'),
        ];
    }

    /**
     * Capture payment abstract method
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function capture(InfoInterface $payment, $amount)
    {
        #$parentReturn = parent::capture($payment, $amount);
        $this->captureRequest->sendRequest($payment, $amount);
        return $this;
    }

    /**
     * Refund specified amount for payment
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function refund(InfoInterface $payment, $amount)
    {
        #$parentReturn = parent::refund($payment, $amount);
        $this->creditRequest->sendRequest($payment, $amount);
        return $this;
    }

    /**
     * Cancel payment abstract method
     *
     * @param InfoInterface $payment
     * @return $this
     */
    public function cancel(InfoInterface $payment)
    {
        #$parentReturn = parent::cancel($payment);
        // DO NOTHING FOR NOW

        return $this;
    }
}
