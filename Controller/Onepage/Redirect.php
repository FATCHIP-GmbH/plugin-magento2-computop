<?php

namespace Fatchip\Computop\Controller\Onepage;

class Redirect extends \Magento\Framework\App\Action\Action
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session       $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Redirect to payment-provider or to success page
     *
     * @return void
     */
    public function execute()
    {
        $redirectUrl = $this->checkoutSession->getComputopRedirectUrl();
        if (!empty($redirectUrl)) {
            $this->checkoutSession->setComputopCustomerIsRedirected(true);
            $this->getResponse()->setRedirect($redirectUrl);
            return;
        }
        $this->_redirect($this->_url->getUrl('checkout/onepage/success'));
    }
}
