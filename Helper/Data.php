<?php
namespace Salesfire\Salesfire\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Salesfire Data Helper
 *
 * @category   Salesfire
 * @package    Salesfire_Salesfire
 * @version.   1.1.1
 */
class Data extends AbstractHelper
{
    const XML_PATH_GENERAL_ENABLED = 'salesfire/general/is_enabled';
    const XML_PATH_GENERAL_SITE_ID = 'salesfire/general/site_id';

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
}
