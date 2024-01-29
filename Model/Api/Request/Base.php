<?php

namespace Fatchip\Computop\Model\Api\Request;

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
     * Computop base helper
     *
     * @var \Fatchip\Computop\Helper\Base
     */
    protected $baseHelper;

    /**
     * Constructor
     *
     * @param \Fatchip\Computop\Helper\Base $baseHelper
     */
    public function __construct(
        \Fatchip\Computop\Helper\Base $baseHelper
    ) {
        $this->baseHelper = $baseHelper;
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
        $this->addParameter('MerchantID', $this->shopHelper->getConfigParam('merchantid', 'global', 'payone_general', $this->storeCode));
    }

    /**
     * Returns all parameters
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
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
     * Generates Hmac string and returns it
     *
     * @return string
     */
    protected function getHmac()
    {
        $hashParts = [
            $this->getParameter("PayID", ""),
            $this->getParameter("TransID", ""),
            $this->getParameter("MerchantID", ""),
            $this->getParameter("Amount", ""),
            $this->getParameter("Currency", ""),
        ];
        $hashString = implode("*", $hashParts);
        $secret = $this->baseHelper->getConfigParam('mac', 'global', 'computop_general', $this->storeCode);

        return hash_hmac('sha256', $hashString, $secret);
    }
}
