<?php

namespace Fatchip\Computop\Model;

use Fatchip\Computop\Model\Method\BaseMethod;
use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * Payment helper object
     *
     * @var \Magento\Payment\Helper\Data
     */
    protected $dataHelper;

    /**
     * Computop payment helper
     *
     * @var \Fatchip\Computop\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * Escaper object
     *
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * Constructor
     *
     * @param \Magento\Payment\Helper\Data     $dataHelper
     * @param \Fatchip\Computop\Helper\Payment $paymentHelper
     * @param \Magento\Framework\Escaper       $escaper
     */
    public function __construct(
        \Magento\Payment\Helper\Data $dataHelper,
        \Fatchip\Computop\Helper\Payment $paymentHelper,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->dataHelper = $dataHelper;
        $this->paymentHelper = $paymentHelper;
        $this->escaper = $escaper;
    }

    /**
     * Returns Computop custom config for config params not specifically tied to a payment method
     *
     * @return array
     */
    protected function getComputopCustomConfig()
    {
        $config = [];
        return $config;
    }

    /**
     * Get the payment instruction text
     *
     * @param  BaseMethod $methodInstance
     * @return string
     */
    protected function getInstructionByCode($methodInstance)
    {
        return nl2br($this->escaper->escapeHtml($methodInstance->getConfigData('instructions')));
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $config = ['payment' => [
            'computop' => $this->getComputopCustomConfig(),
        ]];

        foreach ($this->paymentHelper->getAvailablePaymentTypes() as $methodCode) {
            $methodInstance = $this->dataHelper->getMethodInstance($methodCode);
            if ($methodInstance instanceof BaseMethod && $methodInstance->isAvailable()) {
                $config['payment']['computop'][$methodCode] = $methodInstance->getFrontendConfig();
                $config['payment']['instructions'][$methodCode] = $this->getInstructionByCode($methodInstance);
            }
        }
        return $config;
    }
}
