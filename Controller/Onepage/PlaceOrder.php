<?php

namespace Fatchip\Computop\Controller\Onepage;

use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Model\Group;

/**
 * Controller for creating the PaypalExpress orders
 */
class PlaceOrder extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Api\AgreementsValidatorInterface
     */
    protected $agreementsValidator;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $cartManagement;

    /**
     * @var \Fatchip\Computop\Helper\Checkout
     */
    protected $checkoutHelper;

    /**
     * @var \Fatchip\Computop\Helper\Api
     */
    protected $apiHelper;

    /**
     * Order repository
     *
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param \Magento\Framework\App\Action\Context              $context
     * @param \Magento\Checkout\Api\AgreementsValidatorInterface $agreementValidator
     * @param \Magento\Checkout\Model\Session                    $checkoutSession
     * @param \Magento\Quote\Api\CartManagementInterface         $cartManagement
     * @param \Fatchip\Computop\Helper\Checkout                  $checkoutHelper
     * @param \Fatchip\Computop\Helper\Api                       $apiHelper
     * @param \Magento\Sales\Api\OrderRepositoryInterface        $orderRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Api\AgreementsValidatorInterface $agreementValidator,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Fatchip\Computop\Helper\Checkout $checkoutHelper,
        \Fatchip\Computop\Helper\Api $apiHelper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->agreementsValidator = $agreementValidator;
        $this->checkoutSession = $checkoutSession;
        $this->cartManagement = $cartManagement;
        $this->checkoutHelper = $checkoutHelper;
        $this->apiHelper = $apiHelper;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    /**
     * Submit the order
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        if ($this->isValidationRequired() &&
            !$this->agreementsValidator->isValid(array_keys($this->getRequest()->getPost('agreement', [])))
        ) {
            $e = new \Magento\Framework\Exception\LocalizedException(
                __('Please agree to all the terms and conditions before placing the order.')
            );
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
            $this->_redirect('*/*/review');
            return;
        }

        try {
            $quote = $this->checkoutSession->getQuote();

            $methodInstance = $quote->getPayment()->getMethodInstance();
            if ($this->checkoutHelper->getQuoteComparisonString($quote) != $this->checkoutSession->getComputopQuoteComparisonString()) {
                // The basket was changed - abort current checkout
                $this->messageManager->addErrorMessage('An error occured during the Checkout.');
                $this->_redirect('checkout/cart');
                return;
            }

            $methodInstance->preReviewPlaceOrder($quote);

            $orderId = $this->placeOrder($quote);

            $redirectTarget = $methodInstance->postReviewPlaceOrder();

            if (!empty($redirectTarget)) {
                $this->clearSessionParams();

                $this->_redirect($redirectTarget);
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t place the order.')
            );
            $this->_redirect('*/*/review');
        }
    }

    /**
     * @return void
     */
    protected function clearSessionParams()
    {
        $this->checkoutSession->unsComputopTmpRefnr();
        $this->checkoutSession->unsComputopPpeIsExpressOrder();
        $this->checkoutSession->unsComputopPpeIsExpressAuthStep();
        $this->checkoutSession->unsComputopPpePayId();
        $this->checkoutSession->unsComputopPpeCompleteResponse();
        $this->checkoutSession->unsComputopQuoteComparisonString();
    }

    /**
     * Place the order and put it in a finished state
     *
     * @param Magento\Quote\Model\Quote $quote
     * @return int
     */
    protected function placeOrder($quote)
    {
        $quote->getBillingAddress()->setShouldIgnoreValidation(true);
        if (!$quote->getIsVirtual()) {
            $quote->getShippingAddress()->setShouldIgnoreValidation(true);
        }

        if ($this->checkoutHelper->getCurrentCheckoutMethod($quote) == Onepage::METHOD_GUEST) {
            $quote->setCustomerId(null)
                ->setCustomerEmail($quote->getBillingAddress()->getEmail())
                ->setCustomerIsGuest(true)
                ->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
        }

        $quote->setInventoryProcessed(false);
        $quote->collectTotals()->save();

        $orderId = $this->cartManagement->placeOrder($quote->getId());
        return $orderId;
    }

    /**
     * Return true if agreements validation required
     *
     * @return bool
     */
    protected function isValidationRequired()
    {
        return is_array($this->getRequest()->getBeforeForwardInfo()) && empty($this->getRequest()->getBeforeForwardInfo());
    }
}
