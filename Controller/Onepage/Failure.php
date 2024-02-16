<?php

namespace Fatchip\Computop\Controller\Onepage;

use Magento\Framework\Controller\ResultFactory;

class Failure extends \Magento\Framework\App\Action\Action
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Order factory
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * Url builder object
     *
     * @var \Magento\Framework\Url
     */
    protected $urlBuilder;

    /**
     * @var \Fatchip\Computop\Model\Api\Encryption\Blowfish
     */
    protected $blowfish;

    /**
     * @var \Fatchip\Computop\Model\ResourceModel\ApiLog
     */
    protected $apiLog;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context           $context
     * @param \Magento\Checkout\Model\Session                 $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory               $orderFactory
     * @param \Magento\Framework\Url                          $urlBuilder
     * @param \Fatchip\Computop\Model\Api\Encryption\Blowfish $blowfish
     * @param \Fatchip\Computop\Model\ResourceModel\ApiLog    $apiLog
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Url $urlBuilder,
        \Fatchip\Computop\Model\Api\Encryption\Blowfish $blowfish,
        \Fatchip\Computop\Model\ResourceModel\ApiLog $apiLog
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->urlBuilder = $urlBuilder;
        $this->blowfish = $blowfish;
        $this->apiLog = $apiLog;
    }

    public function execute()
    {
        try {
            $this->checkoutSession->setIsComputopRedirectCancellation(true);

            $error = "";

            $error = "<pre>".print_r($_REQUEST, true)."</pre>";

            $response = $this->blowfish->ctDecrypt($this->getRequest()->getParam('Data'), $this->getRequest()->getParam('Len'));
            $this->apiLog->addApiLogResponse($response);

            $error = "<pre>".print_r($response, true)."</pre>";

            error_log(date('Y-m-d H:i:s - ')."Response: ".print_r($result, true).PHP_EOL, 3, __DIR__."/../../api.log");

            /*
            $sPaymentMethod = $this->checkoutSession->getRedirectedPaymentMethod();
            if ($sPaymentMethod) {
                $this->checkoutSession->setCanceledPaymentMethod($sPaymentMethod);
            }*/

            if ($this->getRequest()->getParam('error')) {
                $this->checkoutSession->setComputopIsError(true);
            }

            $orderId = $this->checkoutSession->getLastOrderId();
            $order = $orderId ? $this->orderFactory->create()->load($orderId) : false;
            if ($order) {
                $order->cancel()->save();
                $this->checkoutSession->restoreQuote();
                $this->checkoutSession
                    ->unsLastQuoteId()
                    ->unsLastSuccessQuoteId()
                    ->unsLastOrderId();
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Error while canceling the payment'));
        }

        die("Fehler: ".$error."<br><br><a href='".$this->urlBuilder->getUrl('checkout/cart')."'>To Shop</a>").

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        #$redirectUrl = $this->urlBuilder->getUrl('checkout').'#payment';
        return $resultRedirect->setUrl($this->urlBuilder->getUrl('checkout/cart'));
    }
}
