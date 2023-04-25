<?php

namespace Salesfire\Salesfire\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\UrlInterface;
use Salesfire\Salesfire\Helper\Data;

/**
 * Salesfire Feed Url for Admin
 *
 * @category   Salesfire
 * @package    Salesfire_Salesfire
 * @version.   1.3.1
 */
class FeedUrl extends Field
{
    /**
     * @var
     */
    public $helperData;

    /**
     * @var SecureHtmlRenderer
     */
    private $secureRenderer;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Data $helperData
     * @param SecureHtmlRenderer|null $secureRenderer
     * @param array $data
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Data $helperData,
        ?SecureHtmlRenderer $secureRenderer = null,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_storeManager = $storeManager;
        $this->_helperData = $helperData;
        $this->secureRenderer = $secureRenderer ?? ObjectManager::getInstance()->get(SecureHtmlRenderer::class);
    }

    /**
     * Render element value
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _renderValue(AbstractElement $element): string
    {
        $storeId = $this->_storeManager->hasSingleStore() ? null : $element->getScopeId();
        $base_url = $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        $feed_url = sprintf('%scatalog/%s.xml', $base_url, $this->_helperData->getSiteId($storeId));

        return '<td class="value">' . $feed_url . '</td>';
    }
}
