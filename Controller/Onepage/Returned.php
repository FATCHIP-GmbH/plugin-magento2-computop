<?php

namespace Fatchip\Computop\Controller\Onepage;

use Fatchip\Computop\Model\Method\RedirectNoOrder;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

class Returned extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Fatchip\Computop\Model\ResourceModel\ApiLog
     */
    protected $apiLog;


    /**
     * @var \Fatchip\Computop\Helper\Encryption
     */
    protected $encryptionHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context           $context
     * @param \Magento\Checkout\Model\Session                 $checkoutSession
     * @param \Fatchip\Computop\Model\ResourceModel\ApiLog    $apiLog
     * @param \Fatchip\Computop\Helper\Encryption             $encryptionHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Fatchip\Computop\Model\ResourceModel\ApiLog $apiLog,
        \Fatchip\Computop\Helper\Encryption $encryptionHelper
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->apiLog = $apiLog;
        $this->encryptionHelper = $encryptionHelper;
    }

    /**
     * @inheritdoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @param string $errorMessage
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    protected function redirectToCart($errorMessage = null)
    {
        $this->messageManager->addErrorMessage('An error occured during the Checkout'.(empty($errorMessage) ? '.' : ': '.$errorMessage));
        return $this->_redirect($this->_url->getUrl('checkout/cart'));
    }

    protected function getPayment()
    {
        $isNoOrderRedirect = $this->checkoutSession->getComputopRedirectNoOrder();

        $order = $this->checkoutSession->getLastRealOrder();
        if ($order->getId() && empty($isNoOrderRedirect)) {
            $payment = $order->getPayment();
        } else {
            $quote = $this->checkoutSession->getQuote();
            $payment = $quote->getPayment();
        }
        $this->checkoutSession->unsComputopRedirectNoOrder();
        return $payment;
    }

    /**
     * Handles return to shop
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->checkoutSession->unsComputopCustomerIsRedirected();
        $this->checkoutSession->unsComputopCancelledPaymentMethod();

        $response = $this->encryptionHelper->decrypt($this->getRequest()->getParam('Data'), $this->getRequest()->getParam('Len'));
        $this->apiLog->addApiLogResponse($response);

        $payment = $this->getPayment();
        if (!$payment->getMethod()) { // order process probably was cancelled because of fraud prevention in \Fatchip\Computop\Observer\CancelOrderProcess
            return $this->redirectToCart();
        }
        $methodInstance = $payment->getMethodInstance();
        
        try {
            $methodInstance->handleResponse($payment, $response);
            if ($methodInstance instanceof RedirectNoOrder) {
                $this->checkoutSession->setComputopNoOrderRedirectResponse($response);
                return $this->_redirect($methodInstance->getFinishUrl());
            }
        } catch(\Exception $e) {
            return $this->redirectToCart();
        }

        return $this->_redirect($this->_url->getUrl('checkout/onepage/success'));
    }
}
