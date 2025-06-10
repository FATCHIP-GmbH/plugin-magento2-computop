<?php

namespace Fatchip\Computop\Model\Method;

use Fatchip\Computop\Helper\Api;
use Fatchip\Computop\Helper\Payment;
use Fatchip\Computop\Model\Api\Request\Capture;
use Fatchip\Computop\Model\Api\Request\Credit;
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
use Fatchip\Computop\Model\Api\Request\RefNrChange;

class Ideal extends RedirectPayment
{
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
    public function getPaymentSpecificParameters(?Order $order = null)
    {
        $infoInstance = $this->getInfoInstance();

        $params = [
            'OrderDesc' => $order->getIncrementId(), // Not sending the OrderDesc parameter can result in "Message format error" errors!
        ];
        return $params;
    }
}
