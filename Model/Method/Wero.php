<?php

namespace Fatchip\Computop\Model\Method;

use Fatchip\Computop\Model\ComputopConfig;
use Fatchip\Computop\Model\Source\CaptureMethods;
use Fatchip\Computop\Model\Source\Service;
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

class Wero extends RedirectPayment
{
    /**
     * Method identifier of this payment method
     *
     * @var string
     */
    protected $methodCode = ComputopConfig::METHOD_WERO;

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint = "WERO.aspx";

    /**
     * Determines if auth requests adds billing address parameters to the request
     *
     * @var bool
     */
    #protected $addBillingAddressData = true;

    /**
     * Determines if auth requests adds shipping address parameters to the request
     *
     * @var bool
     */
    #protected $addShippingAddressData = true;

    /**
     * @return string
     */
    public function getCaptureMode()
    {
        return CaptureMethods::CAPTURE_AUTO;
    }

    /**
     * @param Order|null $order
     * @return false|string
     */
    public function getSoftDescriptor(?Order $order = null)
    {
        $shopName = $this->apiHelper->getConfigParamByPath('general/store_information/name');
        if (empty($shopName)) {
            $shopName = 'ONLINESHOP';
        }

        $incrementId = md5(time());
        if ($order !== null) {
            $incrementId = $order->getIncrementId();
        }

        $descriptor = $shopName.'-'.$incrementId;

        // Replace umlauts
        $search  = ['Ä', 'Ö', 'Ü', 'ä', 'ö', 'ü', 'ß'];
        $replace = ['Ae', 'Oe', 'Ue', 'ae', 'oe', 'ue', 'ss'];
        $descriptor = str_replace($search, $replace, $descriptor);

        // Remove all non-allowed special characters (Regex)
        $descriptor = preg_replace('/[^A-Za-z0-9\/\-\?\:\(\)\.\,\'\+ ]/', '', $descriptor);

        // Limit to 35 characters
        return substr($descriptor, 0, 35);
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param Order|null $order
     * @return array
     */
    public function getPaymentSpecificParameters(?Order $order = null)
    {
        $params = [
            'ChDesc' => $this->getSoftDescriptor($order),
        ];

        return $params;
    }
}
