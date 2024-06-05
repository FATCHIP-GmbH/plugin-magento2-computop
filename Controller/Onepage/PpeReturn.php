<?php

namespace Fatchip\Computop\Controller\Onepage;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote;

class PpeReturn extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
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
     * @var \Fatchip\Computop\Model\Api\Encryption\Blowfish
     */
    protected $blowfish;

    /**
     * @var \Fatchip\Computop\Helper\Checkout
     */
    protected $checkoutHelper;

    /**
     * Totals collector object
     *
     * @var \Magento\Quote\Model\Quote\TotalsCollector
     */
    protected $totalsCollector;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context           $context
     * @param \Magento\Checkout\Model\Session                 $checkoutSession
     * @param \Fatchip\Computop\Model\ResourceModel\ApiLog    $apiLog
     * @param \Fatchip\Computop\Model\Api\Encryption\Blowfish $blowfish
     * @param \Fatchip\Computop\Helper\Checkout               $checkoutHelper
     * @param \Magento\Quote\Model\Quote\TotalsCollector      $totalsCollector
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Fatchip\Computop\Model\ResourceModel\ApiLog $apiLog,
        \Fatchip\Computop\Model\Api\Encryption\Blowfish $blowfish,
        \Fatchip\Computop\Helper\Checkout $checkoutHelper,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->apiLog = $apiLog;
        $this->blowfish = $blowfish;
        $this->checkoutHelper = $checkoutHelper;
        $this->totalsCollector = $totalsCollector;
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
     * @param  array $response
     * @return bool
     */
    protected function isPayPalExpressSuccessReturn($response)
    {
        if (isset($response['Code']) && $response['Code'] == '21500985'&& isset($response['Status']) && $response['Status'] == 'AUTHORIZE_REQUEST') {
            return true;
        }
        return false;
    }

    protected function updateAddressFromResponse($address, $response)
    {
        $address->setFirstname($response['firstname']);
        $address->setLastname($response['lastname']);
        $address->setStreet($response['AddrStreet']);
        $address->setCountryId($response['AddrCountryCode']);
        $address->setCity($response['AddrCity']);
        $address->setPostcode($response['AddrZip']);
        if (empty($response['addrstate']) || $response['addrstate'] == 'Empty') {
            $address->setRegion('');
            $address->setRegionId(null);
        } else {
            $address->setRegion($response['addrstate']);
        }
        return $address;
    }

    protected function getShippingMethod(Quote $quote, Address $shippingAddress)
    {
        $rates = [];

        // Needed for getGroupedAllShippingRates, otherwise sometimes empty output
        $this->totalsCollector->collectAddressTotals($quote, $shippingAddress);
        $shippingRates = $shippingAddress->getGroupedAllShippingRates();
        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $rates[(string)$rate->getPrice()] = $rate->getCode();
            }
        }

        if (!empty($rates)) { // more than one shipping method existing?
            ksort($rates); // sort by price ascending
            return array_shift($rates); // return the cheapest shipping-method
        }
        return false;
    }

    /**
     * @param  Address $address
     * @return bool
     */
    protected function isAddressEmpty(Address $address)
    {
        if (empty($address->getFirstname()) && empty($address->getLastname()) && empty($address->getCountryId()) && empty($address->getPostcode())) {
            return true;
        }
        return false;
    }

    /**
     * @param  array $response
     * @return void
     */
    protected function updateQuoteWithPayPalAddress($response)
    {
        $quote = $this->checkoutSession->getQuote();

        $shippingAddress = $this->updateAddressFromResponse($quote->getShippingAddress(), $response);
        $shippingAddress->setCollectShippingRates(true);
        $shippingMethod = $this->getShippingMethod($quote, $shippingAddress);
        $shippingAddress->setShippingMethod($shippingMethod);
        $quote->setShippingAddress($shippingAddress);

        $billing = $quote->getBillingAddress();

        $checkoutMethod = $this->checkoutHelper->getCurrentCheckoutMethod($quote);
        if ($checkoutMethod == Onepage::METHOD_GUEST || $this->isAddressEmpty($quote->getBillingAddress())) {
            $billingAddress = $this->updateAddressFromResponse($quote->getBillingAddress(), $response);
            if ($checkoutMethod == Onepage::METHOD_GUEST) {
                $billingAddress->setEmail($response['e-mail']);
            }
            $quote->setBillingAddress($billingAddress);
        }

        $quote->collectTotals()->save();
    }

    /**
     * Handles return to shop
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->checkoutSession->unsComputopCustomerIsRedirected();

        $response = $this->blowfish->ctDecrypt($this->getRequest()->getParam('Data'), $this->getRequest()->getParam('Len'));
        $this->apiLog->addApiLogResponse($response);

        /*
         * Computop sends an ERROR Response for a SUCCESSFUL PayPal Express checkout....................
         * They also redirect to the failure URL in that case.............
         * This controller has to discern if it is a real failure or a successful payment process
         */
        if ($this->isPayPalExpressSuccessReturn($response) === true) {
            $this->updateQuoteWithPayPalAddress($response);

            $this->checkoutSession->setComputopPpePayId($response['PayID']);

            return $this->_redirect($this->_url->getUrl('computop/onepage/review', ['Data' => $this->getRequest()->getParam('Data'), 'Len' => $this->getRequest()->getParam('Len')]));
        }

        return $this->_redirect($this->_url->getUrl('computop/onepage/failure', ['Data' => $this->getRequest()->getParam('Data'), 'Len' => $this->getRequest()->getParam('Len')]));
    }
}
