<?php

namespace Fatchip\Computop\Controller\Onepage;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

class Failure extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
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
     * @var \Fatchip\Computop\Helper\Encryption
     */
    protected $encryptionHelper;

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
     * @param \Fatchip\Computop\Helper\Encryption             $encryptionHelper
     * @param \Fatchip\Computop\Model\ResourceModel\ApiLog    $apiLog
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Url $urlBuilder,
        \Fatchip\Computop\Helper\Encryption $encryptionHelper,
        \Fatchip\Computop\Model\ResourceModel\ApiLog $apiLog
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->urlBuilder = $urlBuilder;
        $this->encryptionHelper = $encryptionHelper;
        $this->apiLog = $apiLog;
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

    protected function clearSessionParams()
    {
        $this->checkoutSession->unsComputopTmpRefnr();
        $this->checkoutSession->unsComputopQuoteComparisonString();
        $this->checkoutSession->unsComputopRedirectNoOrder();
        $this->checkoutSession->unsComputopEasyCreditDob();
    }

    /**
     * @return string
     */
    protected function getRedirectUrl()
    {
        return $this->urlBuilder->getUrl('checkout/cart');
    }

    /**
     * @param $order
     * @return void
     */
    protected function handleOrder($order)
    {
        // hook to add custom order handling
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        try {
            $response = false;
            if (!empty($this->getRequest()->getParam('Data')) && $this->getRequest()->getParam('Len')) {
                $response = $this->encryptionHelper->decrypt($this->getRequest()->getParam('Data'), $this->getRequest()->getParam('Len'));
                $this->apiLog->addApiLogResponse($response);
            }

            if ($this->getRequest()->getParam('error')) {
                $this->checkoutSession->setComputopIsError(true);
            }

error_log(date("Y-m-d H:i:s - ")."Trigger Cancel/Failure PROCESS - 1 - PRE".PHP_EOL, 3, BP."/var/log/computop_debug.log");
            if (empty($this->checkoutSession->getComputopTmpRefnr())) { // no order created pre-redirect, therefor no order to cancel
error_log(date("Y-m-d H:i:s - ")."Trigger Cancel/Failure PROCESS - 2 - INSIDE".PHP_EOL, 3, BP."/var/log/computop_debug.log");
                $orderId = $this->checkoutSession->getLastOrderId();
                $order = $orderId ? $this->orderFactory->create()->load($orderId) : false;
                if ($order) {
error_log(date("Y-m-d H:i:s - ")."Trigger Cancel/Failure PROCESS - 3 - GOT ORDER".PHP_EOL, 3, BP."/var/log/computop_debug.log");
                    $this->handleOrder($order);

                    $order->cancel()->save();
error_log(date("Y-m-d H:i:s - ")."Trigger Cancel/Failure PROCESS - 4 - ORDER CANCELED".PHP_EOL, 3, BP."/var/log/computop_debug.log");
                    $this->checkoutSession->restoreQuote();
error_log(date("Y-m-d H:i:s - ")."Trigger Cancel/Failure PROCESS - 5 - OLD BASKET RESTORED".PHP_EOL, 3, BP."/var/log/computop_debug.log");
                    $this->checkoutSession
                        ->unsLastQuoteId()
                        ->unsLastSuccessQuoteId()
                        ->unsLastOrderId();
                }
            }
error_log(date("Y-m-d H:i:s - ")."Trigger Cancel/Failure PROCESS - 6 - POST".PHP_EOL, 3, BP."/var/log/computop_debug.log");

            $this->clearSessionParams();
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Error while canceling the payment'));
        }

        if (!empty($response['Description'])) {
            $this->messageManager->addErrorMessage('An error occured during the Checkout: '.$response['Description']);
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setUrl($this->getRedirectUrl());
    }
}
