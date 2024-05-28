<?php

namespace Fatchip\Computop\Helper;

use Fatchip\Computop\Model\ComputopConfig;
use Locale;

class Api extends Base
{
    /**
     * @var \Magento\Store\Api\Data\StoreInterface
     */
    protected $store;

    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    protected $productMetadata;

    /**
     * @var string
     */
    protected $defaultLocale = 'EN';

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\State               $state
     * @param \Magento\Store\Api\Data\StoreInterface     $store
     * @param \Magento\Framework\App\ProductMetadata     $productMetadata
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\State $state,
        \Magento\Store\Api\Data\StoreInterface $store,
        \Magento\Framework\App\ProductMetadata $productMetadata
    ) {
        parent::__construct($context, $storeManager, $state);
        $this->store = $store;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Formats amount for API
     * Docs say: Amount in the smallest currency unit (e.g. EUR Cent)
     *
     * @param $amount
     * @return float|int
     */
    public function formatAmount($amount)
    {
        return number_format($amount * 100, 0, '.', '');
    }

    /**
     * Returns the current locale of the store
     *
     * @return false|string
     */
    public function getStoreLocale()
    {
        $locale = substr($this->store->getLocale() ?? '', 0, 2);
        if (empty($locale)) {
            $locale = substr($this->store->getDefaultLocale() ?? '', 0, 2);
        }
        if (empty($locale)) {
            $locale = Locale::getPrimaryLanguage($this->getConfigParam('code', 'locale', 'general'));
        }
        if (empty($locale)) {
            $locale = $this->defaultLocale;
        }
        return strtoupper($locale);
    }

    /**
     * Returns Magento version of current shop installtion
     *
     * @return mixed
     */
    public function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Get identification string for requests
     *
     * @return string
     */
    public function getIdentString()
    {
        return 'Magento '.$this->getMagentoVersion().', Module version: '.ComputopConfig::MODULE_VERSION;
    }

    /**
     * Encode array in json and then in base64 for api requests
     *
     * @param  array $array
     * @return string
     */
    public function encodeArray($array)
    {
        return base64_encode(json_encode($array));
    }

    /**
     * Returns reference number
     *
     * @param  string $incrementId
     * @return string
     */
    public function getReferenceNumber($incrementId)
    {
        return trim($this->getConfigParam('ordernr_prefix') ?? '').$incrementId.trim($this->getConfigParam('ordernr_suffix') ?? '');
    }
}
