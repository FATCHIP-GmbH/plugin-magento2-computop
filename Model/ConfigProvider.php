<?php

namespace Fatchip\Computop\Model;

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
     * Returns Computop config needed for payments in the frontend
     *
     * @return array
     */
    protected function getComputopConfig()
    {
        return [];
    }

    /**
     * Get the payment instruction text
     *
     * @param  string $code
     * @return string
     */
    protected function getInstructionByCode($code)
    {
        $methodInstance = $this->dataHelper->getMethodInstance($code);
        if ($methodInstance) {
            return nl2br($this->escaper->escapeHtml($methodInstance->getInstructions()));
        }
        return '';
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $config = ['payment' => [
            'computop' => $this->getComputopConfig(),
        ]];

        foreach ($this->paymentHelper->getAvailablePaymentTypes() as $methodCode) {
            $config['payment']['instructions'][$methodCode] = $this->getInstructionByCode($methodCode);
        }

        return $config;
    }
}
