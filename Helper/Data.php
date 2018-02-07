<?php
namespace Salesfire\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_GENERAL_ENABLED = 'salesfire/general/is_enabled';
    const XML_PATH_GENERAL_SITE_ID = 'salesfire/general/site_id';

    public function isEnabled($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_GENERAL_SITE_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getSiteId($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_GENERAL_SITE_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
