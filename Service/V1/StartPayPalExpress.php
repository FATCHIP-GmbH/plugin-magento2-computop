<?php

namespace Fatchip\Computop\Service\V1;

use Fatchip\Computop\Api\Data\StartPayPalExpressResponseInterfaceFactory;
use Fatchip\Computop\Api\StartPayPalExpressInterface;
use Fatchip\Computop\Helper\Checkout;
use Fatchip\Computop\Model\ComputopConfig;
use Fatchip\Computop\Model\ResourceModel\ApiLog;
use Magento\Checkout\Model\Session;
use Fatchip\Computop\Model\Api\Encryption\Blowfish;

class StartPayPalExpress implements StartPayPalExpressInterface
{
    /**
     * Factory for the response object
     *
     * @var StartPayPalExpressResponseInterfaceFactory
     */
    protected $responseFactory;

    /**
     * Checkout session object
     *
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var ApiLog
     */
    protected $apiLog;

    /**
     * @var Blowfish
     */
    protected $blowfish;

    /**
     * @var Checkout
     */
    protected $checkoutHelper;

    /**
     * Constructor.
     *
     * @param StartPayPalExpressResponseInterfaceFactory $responseFactory
     * @param Session                                    $checkoutSession
     * @param ApiLog                                     $apiLog
     * @param Blowfish                                   $blowfish
     * @param Checkout                                   $checkoutHelper
     */
    public function __construct(
        StartPayPalExpressResponseInterfaceFactory $responseFactory,
        Session $checkoutSession,
        ApiLog $apiLog,
        Blowfish $blowfish,
        Checkout $checkoutHelper
    ) {
        $this->responseFactory = $responseFactory;
        $this->checkoutSession = $checkoutSession;
        $this->apiLog = $apiLog;
        $this->blowfish = $blowfish;
        $this->checkoutHelper = $checkoutHelper;
    }

    /**
     * Logs the PPE auth request and set PPE as used payment method
     *
     * @param string $cartId
     * @param string $data
     * @param string $len
     * @return \Fatchip\Computop\Service\V1\Data\StartPayPalExpressResponse
     */
    public function start($cartId, $data, $len)
    {
        $response = $this->responseFactory->create();
        $response->setData('success', false); // set success to false as default, set to true later if true

        $quote = $this->checkoutSession->getQuote();

        $payment = $quote->getPayment();
        $payment->setMethod(ComputopConfig::METHOD_PAYPAL);

        $request = $this->blowfish->ctDecrypt($data, $len);
        if (!empty($request)) {
            $this->apiLog->addApiLogEntry('PAYPALEXPRESS', $request);
            #$payment->setTransactionId($request['TransID']);
            #$payment->setIsTransactionClosed(0);
            #$payment->save();
            $this->checkoutSession->setComputopTmpRefnr($request['RefNr']);
        }

        $quote->save();
        $this->checkoutSession->setComputopQuoteComparisonString($this->checkoutHelper->getQuoteComparisonString($quote));

        return $response;
    }
}
