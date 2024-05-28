<?php

namespace Fatchip\Computop\Model\Method;

use Fatchip\Computop\Model\ComputopConfig;
use Fatchip\Computop\Model\Source\CreditcardModes;
use Fatchip\Computop\Model\Source\CreditcardTypes;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;

class Creditcard extends RedirectPayment
{
    /**
     * Method identifier of this payment method
     *
     * @var string
     */
    protected $methodCode = ComputopConfig::METHOD_CREDITCARD;

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint = "payssl.aspx"; // endpoint for iframe and payment page mode

    /**
     * Defines if transaction id is set pre or post authorization
     * True = pre auth
     * False = post auth with response
     *
     * @var bool
     */
    protected $setTransactionPreAuthorization = true;

    /**
     * Determines if auth requests adds address parameters to the request
     *
     * @var bool
     */
    protected $sendAddressData = true;

    /**
     * Returns the API endpoint
     *
     * @return string
     */
    public function getApiEndpoint()
    {
        if ($this->getPaymentConfigParam('mode') == CreditcardModes::CC_MODE_SILENT) {
            $this->apiEndpoint = "paynow.aspx"; // endpoint for silent mode
        }
        return parent::getApiEndpoint();
    }

    /**
     * Get all activated creditcard types
     *
     * @return array
     */
    protected function getAvailableCreditcardTypes()
    {
        $types = [];

        $typesConfig = $this->getPaymentConfigParam('types');
        if ($typesConfig) {
            $allTypes = CreditcardTypes::getCreditcardTypes();

            $configuredTypes = explode(',', $typesConfig);
            foreach ($configuredTypes as $typeId) {
                $types[] = [
                    'id' => $allTypes[$typeId]['cardtype'],
                    'title' => $allTypes[$typeId]['name'],
                ];
            }
        }
        return $types;
    }

    /**
     * Hook for extension by the real payment method classes
     *
     * @return array
     */
    public function getFrontendConfig()
    {
        $config = [
            'mode' => $this->getPaymentConfigParam('mode'),
        ];
        if ($this->isSilentMode() === true) {
            $config = array_merge($config, $this->getSilentModeFrontendConfig());
        }
        return $config;
    }

    /**
     * Get frontend config for silent mode
     *
     * @return array[]
     */
    protected function getSilentModeFrontendConfig()
    {
        return [
            'types' => $this->getAvailableCreditcardTypes(),
        ];
    }

    /**
     * Returns whether silent mode is configured currently
     *
     * @return bool
     */
    protected function isSilentMode()
    {
        if ($this->getPaymentConfigParam('mode') == CreditcardModes::CC_MODE_SILENT) {
            return true;
        }
        return false;
    }

    /**
     * Returns redirect url for success case
     *
     * @return string|null
     */
    public function getSuccessUrl()
    {
        return $this->urlBuilder->getUrl('computop/onepage/ccReturn', ['status' => 'success']);
    }

    /**
     * Returns redirect url for failure case
     *
     * @return string|null
     */
    public function getFailureUrl()
    {
        return $this->urlBuilder->getUrl('computop/onepage/ccReturn', ['status' => 'failure']);
    }

    /**
     * Returns if auth request is needed
     * Can be overloaded by other classes
     *
     * @return bool
     */
    protected function isAuthRequestNeeded()
    {
        if ($this->isSilentMode() === true) {
            return false;
        }
        return parent::isAuthRequestNeeded();
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param  Order|null $order
     * @return array
     */
    public function getPaymentSpecificParameters(Order $order = null)
    {
        $params = [
            'msgVer' => '2.0',
            'Capture' => $this->getPaymentConfigParam('capture_method'),
            'credentialOnFile' => $this->authRequest->getApiHelper()->encodeArray(['type' => ['unscheduled' => 'CIT'], 'initialPayment' => true]),
        ];
        if ((bool)$this->getPaymentConfigParam('test_mode') === true) {
            $params['orderDesc'] = 'Test:0000';
        }
        if (!empty($this->getPaymentConfigParam('template'))) {
            $params['template'] = $this->getPaymentConfigParam('template');
        }
        return $params;
    }
}

/*
 * SILENT MODE
!capture	MANUAL

 */
