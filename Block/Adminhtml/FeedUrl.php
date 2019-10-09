<?php

namespace Salesfire\Salesfire\Block\Adminhtml;

use Magento\Framework\App\ObjectManager;

/**
 * Salesfire Feed Url for Admin
 *
 * @category   Salesfire
 * @package    Salesfire_Salesfire
 * @version.   1.2.1
 */
class FeedUrl extends \Magento\Config\Block\System\Config\Form\Field
{
    public $helperData;

    /**
     * Render element value
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _renderValue(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $storeManager = ObjectManager::getInstance()->create('Magento\Store\Model\StoreManagerInterface');
        $storeId = $storeManager->getStore()->getId();
        $helper = ObjectManager::getInstance()->get('Salesfire\Salesfire\Helper\Data');

        return '<td class="value">'.$storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'catalog/'.$helper->getSiteId($storeId).'.xml</td>';
    }
}
