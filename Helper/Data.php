<?php
namespace Salesfire\Salesfire\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Salesfire Data Helper
 *
 * @category   Salesfire
 * @package    Salesfire_Salesfire
 * @version.   1.2.12
 */
class Data extends AbstractHelper
{
    /**
     * Config paths for using throughout the code
     */
    const XML_PATH_GENERAL_ENABLED      = 'salesfire/general/is_enabled';
    const XML_PATH_GENERAL_SITE_ID      = 'salesfire/general/site_id';
    const XML_PATH_FEED_ENABLED         = 'salesfire/feed/is_enabled';
    const XML_PATH_FEED_DEFAULT_BRAND   = 'salesfire/feed/default_brand';
    const XML_PATH_FEED_BRAND_CODE      = 'salesfire/feed/brand_code';
    const XML_PATH_FEED_GENDER_CODE     = 'salesfire/feed/gender_code';
    const XML_PATH_FEED_COLOUR_CODE     = 'salesfire/feed/colour_code';
    const XML_PATH_FEED_AGE_GROUP_CODE  = 'salesfire/feed/age_group_code';
    const XML_PATH_FEED_ATTRIBUTE_CODES = 'salesfire/feed/attribute_codes';

    /**
     * What version of salesfire are we using
     *
     * @return string
     */
    public function getVersion()
    {
        return '1.2.12';
    }

    /**
     * Strip the code of anything not normally in an attribute code
     *
     * @param mixed $code
     * @return string
     */
    public function stripCode($code)
    {
        return trim(preg_replace('/[^a-z0-9_]+/', '', strtolower($code)));
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
        return !! $this->scopeConfig->getValue(
            self::XML_PATH_GENERAL_ENABLED,
            ScopeInterface::SCOPE_STORE,
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
        return trim($this->scopeConfig->getValue(
            self::XML_PATH_GENERAL_SITE_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Get salesfire feed enabled flag
     *
     * @param string $storeId
     * @return string
     */
    public function isFeedEnabled($storeId = null)
    {
        return !! $this->scopeConfig->getValue(
            self::XML_PATH_FEED_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get the default brand
     *
     * @param string $storeId
     * @return string
     */
    public function getDefaultBrand($storeId = null)
    {
        return trim($this->scopeConfig->getValue(
            self::XML_PATH_FEED_DEFAULT_BRAND,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Get the product brand attribute code
     *
     * @param string $storeId
     * @return string
     */
    public function getBrandCode($storeId = null)
    {
        return $this->stripCode($this->scopeConfig->getValue(
            self::XML_PATH_FEED_BRAND_CODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Get the product gender attribute code
     *
     * @param string $storeId
     * @return string
     */
    public function getGenderCode($storeId = null)
    {
        return $this->stripCode($this->scopeConfig->getValue(
            self::XML_PATH_FEED_GENDER_CODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Get the product age group attribute code
     *
     * @param string $storeId
     * @return string
     */
    public function getAgeGroupCode($storeId = null)
    {
        return $this->stripCode($this->scopeConfig->getValue(
            self::XML_PATH_FEED_AGE_GROUP_CODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Get the product colour attribute code
     *
     * @param string $storeId
     * @return string
     */
    public function getColourCode($storeId = null)
    {
        return $this->stripCode($this->scopeConfig->getValue(
            self::XML_PATH_FEED_COLOUR_CODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Get a list of additional codes
     *
     * @param string $storeId
     * @return string
     */
    public function getAttributeCodes($storeId = null)
    {
        return array_map(
            array($this, 'stripCode'),
            explode(',', trim($this->scopeConfig->getValue(
                self::XML_PATH_FEED_ATTRIBUTE_CODES,
                ScopeInterface::SCOPE_STORE,
                $storeId
            )))
        );
    }
}
