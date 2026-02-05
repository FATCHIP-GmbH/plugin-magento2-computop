<?php

namespace Fatchip\Computop\Controller\Onepage;

use Fatchip\Computop\Model\Method\RedirectNoOrder;

class Redirect extends \Magento\Framework\App\Action\Action
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Checkout helper
     *
     * @var \Fatchip\Computop\Helper\Checkout
     */
    protected $checkoutHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session       $checkoutSession
     * @param \Fatchip\Computop\Helper\Checkout     $checkoutHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Fatchip\Computop\Helper\Checkout $checkoutHelper
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->checkoutHelper = $checkoutHelper;
    }

    /**
     * Redirect to payment-provider or to success page
     *
     * @return void
     */
    public function execute()
    {
        $redirectUrl = $this->checkoutSession->getComputopRedirectUrl();
        $quote = $this->checkoutSession->getQuote();

        if (!empty($quote) && !empty($quote->getPayment())) {
            try {
                $methodInstance = $quote->getPayment()->getMethodInstance();
                if ($methodInstance instanceof RedirectNoOrder) {
                    $this->checkoutSession->setComputopQuoteComparisonString($this->checkoutHelper->getQuoteComparisonString($quote));
                    $this->checkoutSession->setComputopRedirectNoOrder(true);
                    $this->checkoutSession->setComputopEasyCreditDob($this->getRequest()->getParam('dob'));

                    $redirectUrl = $methodInstance->getAuthRequestFromQuote();
                }
            } catch (\Exception $exc) {
                // do nothing
            }
        }

        if (!empty($redirectUrl)) {
            $this->checkoutSession->setComputopCustomerIsRedirected(true);
error_log(date("Y-m-d H:i:s - ")."---> SESSION - B - setComputopCustomerIsRedirected to true - SessionID: ".$this->checkoutSession->getSessionId().PHP_EOL, 3, BP."/pub/media/logs/computop_debug.log");
error_log(date("Y-m-d H:i:s - ")."------> REDIRECT USER TO ".$redirectUrl.PHP_EOL, 3, BP."/pub/media/logs/computop_debug.log");
            $this->getResponse()->setRedirect($redirectUrl);
            return;
        }

        $this->_redirect($this->_url->getUrl('checkout/onepage/success'));
    }
}
