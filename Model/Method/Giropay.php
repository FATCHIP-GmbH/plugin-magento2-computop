<?php

namespace Fatchip\Computop\Model\Method;

use Fatchip\Computop\Model\ComputopConfig;
use Fatchip\Computop\Model\Source\CaptureMethods;
use Magento\Sales\Model\Order;

class Giropay extends RedirectPayment
{
    /**
     * Method identifier of this payment method
     *
     * @var string
     */
    protected $methodCode = ComputopConfig::METHOD_GIROPAY;

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint = "giropay.aspx";

    /**
     * @return string
     */
    public function getCaptureMode()
    {
        // Giropay has no capture mode, there orders are already paid when finished so it is always AUTO
        return CaptureMethods::CAPTURE_AUTO;
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order|null $order
     * @return array
     */
    public function getPaymentSpecificParameters(Order $order = null)
    {
        return [
            'Capture' => $this->getPaymentConfigParam('capture_method'),
        ];
    }
}
