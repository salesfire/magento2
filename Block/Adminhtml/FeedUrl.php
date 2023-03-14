<?php

namespace Salesfire\Salesfire\Block\Adminhtml;

use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\UrlInterface;
use Salesfire\Salesfire\Helper\Data;

/**
 * Salesfire Feed Url for Admin
 *
 * @category   Salesfire
 * @package    Salesfire_Salesfire
 * @version.   1.3.0
 */
class FeedUrl extends \Magento\Config\Block\System\Config\Form\Field
{
    public $helperData;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Data $helperData,
    ) {
        $this->_storeManager = $storeManager;
        $this->_helperData = $helperData;
    }

    /**
     * Render element value
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _renderValue(AbstractElement $element)
    {
        $storeId = $this->_storeManager->hasSingleStore() ? null : $element->getScopeId();
        $base_url = $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        $feed_url = sprintf('%scatalog/%s.xml', $base_url, $this->_helperData->getSiteId($storeId));

        return '<td class="value">'. $feed_url .'</td>';
    }
}
