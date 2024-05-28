<?php

namespace Fatchip\Computop\Block\Onepage;

use Fatchip\Computop\Helper\Base;
use Fatchip\Computop\Model\ComputopConfig;
use Fatchip\Computop\Model\Source\CreditcardModes;

class Breakout extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Url
     */
    protected $urlBuilder;

    /**
     * @var Base
     */
    protected $baseHelper;

    /**
     * Constructor
     *
     * @param  \Magento\Framework\Url                           $urlBuilder
     * @param  \Magento\Framework\View\Element\Template\Context $context
     * @param  Base                                             $baseHelper
     * @param  array                                            $data
     */
    public function __construct(
        \Magento\Framework\Url $urlBuilder,
        \Magento\Framework\View\Element\Template\Context $context,
        Base $baseHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->urlBuilder = $urlBuilder;
        $this->baseHelper = $baseHelper;
    }

    /**
     * Returns breakout url
     *
     * @return string
     */
    public function getBreakoutUrl()
    {
        $path = 'checkout/cart'; // error fallback
        if ($this->getRequest()->getParam('status') == 'success') {
            $path = 'computop/onepage/returned';
        }
        if ($this->getRequest()->getParam('status') == 'failure') {
            $path = 'computop/onepage/failure';
        }
        return $this->urlBuilder->getUrl($path);
    }

    /**
     * Returns if creditcard is configured in iframe mode
     *
     * @return bool
     */
    public function isIframeMode()
    {
        if ($this->baseHelper->getConfigParam("mode", ComputopConfig::METHOD_CREDITCARD, "computop_payment") == CreditcardModes::CC_MODE_IFRAME) {
            return true;
        }
        return false;
    }

    /**
     * Returns data param
     *
     * @return string
     */
    public function getDataParam()
    {
        return $this->getRequest()->getParam('Data');
    }

    /**
     * Returns len param
     *
     * @return string
     */
    public function getLenParam()
    {
        return $this->getRequest()->getParam('Len');
    }
}
