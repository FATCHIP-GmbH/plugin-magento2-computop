<?php

namespace Fatchip\Computop\Model\Method;

use Fatchip\Computop\Model\ComputopConfig;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;

class DirectDebit extends ServerToServerPayment
{
    /**
     * Method identifier of this payment method
     *
     * @var string
     */
    protected $methodCode = ComputopConfig::METHOD_DIRECTDEBIT;

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint = "edddirect.aspx";

    /**
     * Can be used to assign data from frontend to info instance
     *
     * @var array
     */
    protected $assignKeys = [
        'bank',
        'iban',
        'bic',
    ];

    /**
     * Each ELV payment needs a unique mandateID.
     * For now, it is the ordernumber plus date
     *
     * @param  string $orderID
     * @return string
     */
    public function createMandateId($orderId)
    {
        return $orderId.date('yzGis');
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order $order
     * @return array
     */
    public function getPaymentSpecificParameters(Order $order)
    {
        $oInfoInstance = $this->getInfoInstance();
        $billing = $order->getBillingAddress();

        return [
            'AccBank' => $oInfoInstance->getAdditionalInformation('bank'),
            'AccOwner' => $billing->getFirstname().' '.$billing->getLastname(),
            'IBAN' => $oInfoInstance->getAdditionalInformation('iban'),
            'BIC' => $oInfoInstance->getAdditionalInformation('bic'),
            'MandateID' => $this->createMandateId($order->getIncrementId()),
            'DtOfSgntr' => date('d-m-Y'),
        ];
    }
}
