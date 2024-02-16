<?php

namespace Fatchip\Computop\Model\Api\Request;

use Fatchip\Computop\Model\Method\BaseMethod;
use Magento\Sales\Model\Order;

class Base
{
    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * Store code for the current context
     *
     * @var string
     */
    protected $storeCode = null;

    /**
     * Computop payment helper
     *
     * @var \Fatchip\Computop\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * Class for handling the encryption of the API communication
     *
     * @var \Fatchip\Computop\Model\Api\Encryption\Blowfish
     */
    protected $blowfish;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @var \Fatchip\Computop\Model\ResourceModel\ApiLog
     */
    protected $apiLog;

    /**
     * Constructor
     *
     * @param \Fatchip\Computop\Helper\Payment $paymentHelper
     * @param \Fatchip\Computop\Model\Api\Encryption\Blowfish $blowfish
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Fatchip\Computop\Model\ResourceModel\ApiLog $apiLog
     */
    public function __construct(
        \Fatchip\Computop\Helper\Payment $paymentHelper,
        \Fatchip\Computop\Model\Api\Encryption\Blowfish $blowfish,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Fatchip\Computop\Model\ResourceModel\ApiLog $apiLog
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->blowfish = $blowfish;
        $this->curl = $curl;
        $this->apiLog = $apiLog;
        $this->initRequest();
    }

    /**
     * Initialize request
     * Set all default parameters
     *
     * @return void
     */
    protected function initRequest()
    {
        $this->parameters = []; // clear parameters
        $this->addParameter('MerchantID', $this->paymentHelper->getConfigParam('merchantid', 'global', 'computop_general', $this->storeCode));
    }

    /**
     * Returns all parameters
     *
     * @return array
     */
    public function getParameters()
    {
        $return = $this->parameters;
        $return['MAC'] = $this->getHmac();
        error_log(date('Y-m-d H:i:s - ')."Request: ".print_r($return, true).PHP_EOL, 3, __DIR__."/../../../api.log");
        return $return;
    }

    /**
     * Returns a certain parameter if set
     *
     * @param  string $paramName
     * @param  mixed $defaultEmptyReturn
     * @return string|mixed
     */
    public function getParameter($paramName, $defaultEmptyReturn = null)
    {
        if (isset($this->parameters[$paramName])) {
            return $this->parameters[$paramName];
        }
        return $defaultEmptyReturn;
    }

    /**
     * Adds parameter to parameters array
     *
     * @param  string $paramName
     * @param  string $paramValue
     * @return void
     */
    public function addParameter($paramName, $paramValue)
    {
        $this->parameters[$paramName] = $paramValue;
    }

    /**
     * Adds multiple parameters to parameters array
     *
     * @param  array $parameters
     * @return void
     */
    public function addParameters($parameters)
    {
        $this->parameters = array_merge($this->parameters, $parameters);
    }

    /**
     * Removes certain parameter from parameters array
     *
     * @param  string $paramName
     * @return void
     */
    public function removeParameter($paramName)
    {
        if (isset($this->parameters[$paramName])) {
            unset($this->parameters[$paramName]);
        }
    }

    /**
     * Set current store code and reinit base parameters
     *
     * @param  string $storeCode
     * @return void
     */
    public function setStoreCode($storeCode)
    {
        if ($this->storeCode != $storeCode) {
            $this->storeCode = $storeCode;
            $this->initRequest(); //reinit base parameters
        }
    }


    /**
     * Formats amount for API
     * Docs say: Amount in the smallest currency unit (e.g. EUR Cent)
     *
     * @param $amount
     * @return float|int
     */
    protected function formatAmount($amount)
    {
        return number_format($amount * 100, 0, '.', '');
    }

    /**
     * Generates Hmac string and returns it
     *
     * @return string
     */
    protected function getHmac()
    {
        $hashParts = [
            $this->getParameter("PayID", ""), // may be empty, but that's ok - i.e. for Authorization
            $this->getParameter("TransID", ""),
            $this->getParameter("MerchantID", ""),
            $this->getParameter("Amount", ""),
            $this->getParameter("Currency", ""),
        ];
        $hashString = implode("*", $hashParts);
        $secret = $this->paymentHelper->getConfigParam('mac', 'global', 'computop_general', $this->storeCode);

        return hash_hmac('sha256', $hashString, $secret);
    }

    /**
     * Returns parameters in encrypted format
     *
     * @return array
     */
    public function getEncryptedParameters()
    {
        $dataQuery = urldecode(http_build_query($this->getParameters()));
        $length = mb_strlen($dataQuery);

        return [
            'MerchantID' => $this->getParameter('MerchantID'),
            'Len' => $length,
            'Data' => $this->blowfish->ctEncrypt($dataQuery, $length, $this->paymentHelper->getConfigParam('password', 'global', 'computop_general', $this->storeCode))
        ];
    }

    /**
     * Send request to given url and decode given response
     *
     * @param  string $url
     * @param  string $requestType
     * @param  array  $params
     * @param  Order  $order
     * @return array|null
     */
    protected function handleCurlRequest($url, $requestType, $params, Order $order = null)
    {
        $response = null;

        ///@TODO: Log Request
        $this->curl->post($url, $params);

        $responseBody = $this->curl->getBody();
        if (!empty($responseBody)) {
            parse_str($responseBody, $parsedResponse);
            if (isset($parsedResponse['Data']) && isset($parsedResponse['Len'])) {
                $decrypted = $this->blowfish->ctDecrypt($parsedResponse['Data'], $parsedResponse['Len'], $this->paymentHelper->getConfigParam('password', 'global', 'computop_general', $this->storeCode));
                ///@TODO: Log Response
                parse_str($decrypted, $decryptedArray);
                $response = $decryptedArray;
            }
        }
        $this->apiLog->addApiLogEntry($requestType, $params, $response, $order);
        return $response;
    }
}
