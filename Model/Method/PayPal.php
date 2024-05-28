<?php

namespace Fatchip\Computop\Model\Method;

use Fatchip\Computop\Model\ComputopConfig;
use Fatchip\Computop\Model\Source\CaptureMethods;
use Magento\Sales\Model\Order;

class PayPal extends RedirectPayment
{
    /**
     * Method identifier of this payment method
     *
     * @var string
     */
    protected $methodCode = ComputopConfig::METHOD_PAYPAL;

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint = "ExternalServices/paypalorders.aspx";

    /**
     * @var bool
     */
    protected $isExpressOrder = false;

    /**
     * @param  bool $isExpressOrder
     * @return void
     */
    public function setIsExpressOrder($isExpressOrder)
    {
        $this->isExpressOrder = $isExpressOrder;
    }

    /**
     * Returns if current PayPal order process is in PayPal Express mode
     *
     * @return bool
     */
    public function isExpressOrder()
    {
        return $this->isExpressOrder;
    }

    /**
     * Returns redirect url for success case
     *
     * @return string|null
     */
    public function getSuccessUrl()
    {
        if ($this->isExpressOrder() === true) {
            return $this->urlBuilder->getUrl('computop/onepage/review');
        }
        return parent::getSuccessUrl();
    }

    /**
     * @param Order $order
     * @return array
     */
    protected function getPayPalAddressData(Order $order)
    {
        $address = $order->getShippingAddress();
        $street = $address->getStreet();
        $street = is_array($street) ? implode(' ', $street) : $street; // street may be an array
        return [
            'FirstName' => $address->getFirstname(),
            'LastName' => $address->getLastname(),
            'AddrStreet' => $street,
            'AddrCity' => $address->getCity(),
            'AddrZip' => $address->getPostcode(),
            'AddrCountryCode' => $address->getCountryId(),
        ];
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order|null $order
     * @return array
     */
    public function getPaymentSpecificParameters(Order $order = null)
    {
        $params = [
            'Capture' => $this->getPaymentConfigParam('capture_method'),
            #'NoShipping' => '1',
        ];

        if ($params['Capture'] == CaptureMethods::CAPTURE_MANUAL) {
            $params['TxType'] = 'Auth';
        }
        if ($this->isExpressOrder() === true) {
            $params['PayPalMethod'] = 'shortcut';
        } elseif (!empty($order)) {
            $params['mode'] = 'redirect';
            $params = array_merge($params, $this->getPayPalAddressData($order));
        }
        return $params;
    }
}
