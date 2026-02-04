<?php

namespace Fatchip\Computop\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Exception\LocalizedException;
use \Magento\Quote\Api\CartRepositoryInterface as QuoteRepo;

class CancelOrderProcess implements ObserverInterface
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var QuoteRepo
     */
    protected $quoteRepository;

    /**
     * Constructor
     *
     * @param Session $checkoutSession
     * @param OrderFactory $orderFactory
     * @param QuoteRepo $quoteRepository
     */
    public function __construct(
        Session $checkoutSession,
        OrderFactory $orderFactory,
        QuoteRepo $quoteRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Determines if order should be canceled
     *
     * @param  Order $order
     * @return bool
     */
    protected function canCancelOrder(Order $order)
    {
        if ($order->hasInvoices() === true) {
            return false;
        }

        return true;
    }

    /**
     * @param  Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
error_log(date("Y-m-d H:i:s - ")."Trigger CancelOrderProcess Observer - 1 - PRE".PHP_EOL, 3, BP."/pub/computop_debug.log");
error_log(date("Y-m-d H:i:s - ")."---> SESSION - getComputopCustomerIsRedirected: ".$this->checkoutSession->getComputopCustomerIsRedirected()." - SessionID: ".$this->checkoutSession->getSessionId().PHP_EOL, 3, BP."/pub/computop_debug.log");
        if ($this->checkoutSession->getComputopCustomerIsRedirected()) {
error_log(date("Y-m-d H:i:s - ")."Trigger CancelOrderProcess Observer - 2 - INSIDE".PHP_EOL, 3, BP."/pub/computop_debug.log");
            try {
                $orderId = $this->checkoutSession->getLastOrderId();
                /** @var Order $order */
                $order = $orderId ? $this->orderFactory->create()->load($orderId) : false;
                if ($order) {
error_log(date("Y-m-d H:i:s - ")."Trigger CancelOrderProcess Observer - 3 - GOT ORDER".PHP_EOL, 3, BP."/pub/computop_debug.log");
                    if ($this->canCancelOrder($order)) {
error_log(date("Y-m-d H:i:s - ")."Trigger CancelOrderProcess Observer - 4 - CAN CANCEL".PHP_EOL, 3, BP."/pub/computop_debug.log");
                        $order->cancel();
                        $order->addStatusToHistory(Order::STATE_CANCELED, __('The transaction has been canceled.'), false);
                        $order->save();
error_log(date("Y-m-d H:i:s - ")."Trigger CancelOrderProcess Observer - 5 - ORDER CANCELED".PHP_EOL, 3, BP."/pub/computop_debug.log");

                        $currentQuote = $this->checkoutSession->getQuote();

                        $quoteId = $this->checkoutSession->getLastQuoteId();
                        $oldQuote = $this->quoteRepository->get($quoteId);
                        if ($oldQuote && $oldQuote->getId()) {
                            $currentQuote->merge($oldQuote);
                            $currentQuote->collectTotals();
                            $currentQuote->save();
error_log(date("Y-m-d H:i:s - ")."Trigger CancelOrderProcess Observer - 6 - OLD BASKET RESTORED".PHP_EOL, 3, BP."/pub/computop_debug.log");
                        }
                    }

                    $this->checkoutSession
                        ->unsLastQuoteId()
                        ->unsLastSuccessQuoteId()
                        ->unsLastOrderId()
                        ->unsLastRealOrderId();
                }
            } catch (LocalizedException $e) {
                // catch and continue - do something when needed
            } catch (\Exception $e) {
                // catch and continue - do something when needed
            }

            $this->checkoutSession->unsComputopCustomerIsRedirected();
error_log(date("Y-m-d H:i:s - ")."---> SESSION - C - unsComputopCustomerIsRedirected - SessionID: ".$this->checkoutSession->getSessionId().PHP_EOL, 3, BP."/pub/computop_debug.log");
        }
error_log(date("Y-m-d H:i:s - ")."Trigger CancelOrderProcess Observer - 7 - POST".PHP_EOL, 3, BP."/pub/computop_debug.log");
    }
}
