<?php

namespace Fatchip\Computop\Model\Method;

use Fatchip\Computop\Helper\Payment;
use Fatchip\Computop\Model\Api\Request\Capture;
use Fatchip\Computop\Model\Api\Request\Credit;
use Fatchip\Computop\Model\ComputopConfig;
use Fatchip\Computop\Model\Source\CaptureMethods;
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
use Magento\Quote\Model\Quote;
use Magento\Tax\Api\OrderTaxManagementInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Fatchip\Computop\Model\Api\Request\RefNrChange;

class Klarna extends RedirectPayment
{
    /**
     * Method identifier of this payment method
     *
     * @var string
     */
    protected $methodCode = ComputopConfig::METHOD_KLARNA;

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint = "KlarnaPaymentsHPP.aspx";

    /**
     * @var \Fatchip\Computop\Helper\Api
     */
    protected $apiHelper;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Tax\Item
     */
    protected $taxItem;

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
     * @param RefNrChange $refNrChange
     * @param \Fatchip\Computop\Helper\Api $apiHelper
     * @param \Magento\Sales\Model\ResourceModel\Order\Tax\Item $taxItem
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
        RefNrChange $refNrChange,
        \Fatchip\Computop\Helper\Api $apiHelper,
        \Magento\Sales\Model\ResourceModel\Order\Tax\Item $taxItem,
        CommandPoolInterface $commandPool = null,
        ValidatorPoolInterface $validatorPool = null,
        CommandManagerInterface $commandExecutor = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct($eventManager, $valueHandlerPool, $paymentDataObjectFactory, $code, $formBlockType, $infoBlockType, $urlBuilder, $authRequest, $checkoutSession, $paymentHelper, $captureRequest, $creditRequest, $invoiceService, $orderSender, $invoiceSender, $refNrChange, $commandPool, $validatorPool, $commandExecutor, $logger);
        $this->apiHelper = $apiHelper;
        $this->taxItem = $taxItem;
    }

    /**
     * @return string
     */
    public function getCaptureMode()
    {
        // Klarna has no capture mode, there orders always have to be captured so it is always MANUAL
        return CaptureMethods::CAPTURE_MANUAL;
    }

    /**
     * Create a ArticleList entry from given values
     *
     * @param  string $name
     * @param  double $qty
     * @param  double $taxRate
     * @param  double $total
     * @param  double $totalTax
     * @param  double $unitPrice
     * @return array
     */
    protected function getArticleListEntry($name, $qty, $taxRate, $total, $totalTax, $unitPrice)
    {
        return [
            'name' => $name,
            'quantity' => $qty,
            'tax_rate' => $this->apiHelper->formatAmount($taxRate),
            'total_amount' => $this->apiHelper->formatAmount($total),
            'total_tax_amount' => $this->apiHelper->formatAmount($totalTax),
            'unit_price' => $this->apiHelper->formatAmount($unitPrice),
        ];
    }

    /**
     * Create product ArticleList entry from order item
     *
     * @param $item
     * @return array
     */
    protected function getArticleListProductEntry($item)
    {
        return $this->getArticleListEntry(
            $item->getName(),
            $item->getQtyOrdered(),
            $item->getTaxPercent(),
            $item->getRowTotalInclTax(),
            $item->getTaxAmount(),
            $item->getPriceInclTax()
        );
    }

    /**
     * Calculates vat percent from brut and net values
     * Should not be used where possible
     * Is only used here because Magento hides the used vat percent values so damn well...
     *
     * @param float $brutAmount
     * @param float $netAmount
     * @return float
     */
    protected function calculateVatRate($brutAmount, $netAmount)
    {
        return round((($brutAmount / $netAmount) * 100 - 100), 0);
    }

    /**
     * Create shipping ArticleList entry from order
     *
     * @param Order $order
     * @return array
     */
    protected function getArticleListShippingEntry(Order $order)
    {
        return $this->getArticleListEntry(
            'shippingcosts',
            '1',
            $this->calculateVatRate($order->getShippingInclTax(), $order->getShippingAmount()), // @TODO: where does magento hide the shipping vat rate???...
            $order->getShippingInclTax(),
            $order->getShippingTaxAmount(),
            $order->getShippingInclTax()
        );
    }

    /**
     * Returns article list for current order
     *
     * @return array
     */
    protected function getArticleList(Order $order)
    {
        $list = [];
        foreach ($order->getAllItems() as $item) {
            if (($order instanceof Order && $item->isDummy() === false) || ($order instanceof Quote && $item->getParentItemId() === null)) { // prevent variant-products of adding 2 items
                $list[] = $this->getArticleListProductEntry($item);
            }
        }

        if ($order->getShippingInclTax()) {
            $list[] = $this->getArticleListShippingEntry($order);
        }
        //@TODO: Add discount
        return ['order_lines' => $list];
    }

    /**
     * Returns klarna account from admin config
     *
     * @return string
     */
    protected function getKlarnaAccount()
    {
        $klarnaAccount = $this->getPaymentConfigParam('klarna_account');
        if (!empty($klarnaAccount)) {
            return $klarnaAccount;
        }
        return "1"; // default
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order|null $order
     * @return array
     */
    public function getPaymentSpecificParameters(Order $order = null)
    {
        $methodInstance = $order->getPayment()->getMethodInstance();
        return [
            'TaxAmount' => $this->apiHelper->formatAmount($order->getTaxAmount()),
            'bdCountryCode' => $order->getBillingAddress()->getCountryId(),
            'Language' => $this->apiHelper->getStoreLocale(),
            'Account' => $this->getKlarnaAccount(),
            'ArticleList' => $this->apiHelper->encodeArray($this->getArticleList($order)),
            'URLConfirm' => $methodInstance->getSuccessUrl(),

            #'OrderDesc' => $order->getIncrementId(), // Not sending the OrderDesc parameter can result in "Message format error" errors!
            #'bdCompany' => '',
            #'sdCompany' => '',
            #'bdEmail' => 'robert.mueller@fatchip.de',
            #'sdEmail' => 'robert.mueller@fatchip.de',
            #'sdCountryCode' => 'DE',
            #'FirstName' => 'Paul',
            #'bdFirstName' => 'Paul',
            #'sdFirstName' => 'Paul',
            #'LastName' => 'Payer',
            #'bdLastName' => 'Payer',
            #'sdLastName' => 'Payer',
            #'bdStreet' => 'Teststr. 1',
            #'sdStreet' => 'Teststr. 1',
            #'bdZip' => '12345',
            #'sdZip' => '12345',
            #'bdCity' => 'Berlin',
            #'sdCity' => 'Berlin',
        ];
    }
}
