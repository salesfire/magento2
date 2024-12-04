<?php
namespace Salesfire\Salesfire\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Salesfire Data Helper
 *
 * @category   Salesfire
 * @package    Salesfire_Salesfire
 * @version    1.4.18
 */
class Data extends AbstractHelper
{
    /**
     * Config paths for using throughout the code
     */
    public const XML_PATH_GENERAL_ENABLED      = 'salesfire/general/is_enabled';
    public const XML_PATH_GENERAL_SITE_ID      = 'salesfire/general/site_id';
    public const XML_PATH_FEED_ENABLED         = 'salesfire/feed/is_enabled';
    public const XML_PATH_FEED_TAX_ENABLED     = 'salesfire/feed/tax_enabled';
    public const XML_PATH_FEED_DEFAULT_BRAND   = 'salesfire/feed/default_brand';
    public const XML_PATH_FEED_BRAND_CODE      = 'salesfire/feed/brand_code';
    public const XML_PATH_FEED_GENDER_CODE     = 'salesfire/feed/gender_code';
    public const XML_PATH_FEED_COLOUR_CODE     = 'salesfire/feed/colour_code';
    public const XML_PATH_FEED_AGE_GROUP_CODE  = 'salesfire/feed/age_group_code';
    public const XML_PATH_FEED_ATTRIBUTE_CODES = 'salesfire/feed/attribute_codes';
    public const XML_PATH_LOG_MAX_SIZE         = 'salesfire/logging/max_size';

    protected $storeManager;
    protected $productMetadata;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    ) {
        $this->storeManager = $storeManager;
        $this->productMetadata = $productMetadata;

        return parent::__construct($context);
    }

    /**
     * What version of salesfire are we using
     *
     * @return string
     */
    public function getVersion()
    {
        return '1.4.18';
    }

    /**
     * What version of Magento are we using
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Is the current Magento version greater than or equal to the given version
     *
     * @param string $version
     * @return bool
     */
    public function isMinimumMagentoVersion($version)
    {
        return version_compare($this->getMagentoVersion(), $version, '>=');
    }

    /**
     * Strip the code of anything not normally in an attribute code
     *
     * @param mixed $code
     * @return string
     */
    public function stripCode($code)
    {
        return trim(preg_replace('/[^a-z0-9_]+/', '', strtolower($code ?? "")));
    }

    public function isSingleStoreMode()
    {
        return $this->storeManager->isSingleStoreMode();
    }

    public function getStoreViews()
    {
        if ($this->storeManager->isSingleStoreMode()) {
            $store = new \stdClass;
            $store->id = null;
            $store->site_uuid = $this->getSiteId(null);

            return [$store];
        }

        foreach ($this->storeManager->getStores() as $store) {
            $storeId = $store->getId();
            $store = new \stdClass;
            $store->id = $storeId;
            $store->site_uuid = $this->getSiteId($storeId);
            $store->is_enabled = $this->isEnabled($storeId);
            $stores[] = $store;
        }

        return $stores;
    }

    /**
     * Whether salesfire is ready to use
     *
     * @param mixed $storeId
     * @return bool
     */
    public function isAvailable($storeId = null)
    {
        $siteId = $this->getSiteId($storeId);
        return ! empty($siteId) && $this->isEnabled($storeId);
    }

    /**
     * Get salesfire enabled flag
     *
     * @param mixed $storeId
     * @return bool
     */
    public function isEnabled($storeId = null)
    {
        return !! $this->getScopeConfigValue(
            self::XML_PATH_GENERAL_ENABLED,
            $storeId
        );
    }

    /**
     * Get salesfire site id
     *
     * @param string $storeId
     * @return string
     */
    public function getSiteId($storeId = null)
    {
        return $this->getScopeConfigValue(
            self::XML_PATH_GENERAL_SITE_ID,
            $storeId
        );
    }

    /**
     * Get salesfire feed enabled flag
     *
     * @param string $storeId
     * @return string
     */
    public function isFeedEnabled($storeId = null)
    {
        return !! $this->getScopeConfigValue(
            self::XML_PATH_FEED_ENABLED,
            $storeId
        );
    }

    public function isTaxEnabled($storeId = null)
    {
        return $this->getScopeConfigValue(
            self::XML_PATH_FEED_TAX_ENABLED,
            $storeId
        ) === '1';
    }

    public function shouldUseStoreTaxSettings($storeId = null)
    {
        return $this->getScopeConfigValue(
            self::XML_PATH_FEED_TAX_ENABLED,
            $storeId
        ) === '2';
    }

    /**
     * Get the default brand
     *
     * @param string $storeId
     * @return string
     */
    public function getDefaultBrand($storeId = null)
    {
        $brand = $this->getScopeConfigValue(
            self::XML_PATH_FEED_DEFAULT_BRAND,
            $storeId
        );

        return trim($brand ?: '');
    }

    /**
     * Get the product brand attribute code
     *
     * @param string $storeId
     * @return string
     */
    public function getBrandCode($storeId = null)
    {
        $brand_code = $this->getScopeConfigValue(
            self::XML_PATH_FEED_BRAND_CODE,
            $storeId
        );

        return $this->stripCode($brand_code);
    }

    /**
     * Get the product gender attribute code
     *
     * @param string $storeId
     * @return string
     */
    public function getGenderCode($storeId = null)
    {
        $gender_code = $this->getScopeConfigValue(
            self::XML_PATH_FEED_GENDER_CODE,
            $storeId
        );

        return $this->stripCode($gender_code);
    }

    /**
     * Get the product age group attribute code
     *
     * @param string $storeId
     * @return string
     */
    public function getAgeGroupCode($storeId = null)
    {
        $age_group_code = $this->getScopeConfigValue(
            self::XML_PATH_FEED_AGE_GROUP_CODE,
            $storeId
        );

        return $this->stripCode($age_group_code);
    }

    /**
     * Get the product colour attribute code
     *
     * @param string $storeId
     * @return string
     */
    public function getColourCode($storeId = null)
    {
        $color_code = $this->getScopeConfigValue(
            self::XML_PATH_FEED_COLOUR_CODE,
            $storeId
        );

        return $this->stripCode($color_code);
    }

    /**
     * Get a list of additional codes
     *
     * @param string $storeId
     * @return string
     */
    public function getAttributeCodes($storeId = null)
    {
        $attribute_codes = $this->getScopeConfigValue(
            self::XML_PATH_FEED_ATTRIBUTE_CODES,
            $storeId
        );

        return array_map(
            [$this, 'stripCode'],
            explode(',', trim($attribute_codes ?: ''))
        );
    }

    /**
     * Get max log file size in megabytes.
     * @return int
     */
    public function maxLogSize()
    {
        $max_size = $this->getScopeConfigValue(self::XML_PATH_LOG_MAX_SIZE, null);

        return intval($max_size) * 1024 * 1024;
    }

    protected function getScopeConfigValue($setting, $storeId)
    {
        if ($storeId) {
            return trim($this->scopeConfig->getValue(
                $setting,
                ScopeInterface::SCOPE_STORE,
                $storeId
            ) ?: '');
        } else if ($this->isSingleStoreMode()) {
            return trim($this->scopeConfig->getValue(
                $setting
            ) ?: '');
        } else {
            return trim($this->scopeConfig->getValue(
                $setting,
                ScopeInterface::SCOPE_STORE
            ) ?: '');
        }
    }
}
