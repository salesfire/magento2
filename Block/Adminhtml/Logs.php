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
use \Salesfire\Salesfire\Helper\Logger\Logger;

/**
 * Salesfire Recent Log entries for Admin
 *
 * @category   Salesfire
 * @package    Salesfire_Salesfire
 * @version    1.4.0
 */
class Logs extends Field
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
        Logger $logger,
        ?SecureHtmlRenderer $secureRenderer = null,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_storeManager = $storeManager;
        $this->_helperData = $helperData;
        $this->_logger = $logger;
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
        $lines = $this->_logger->getLastLines(100);

        return sprintf('<td class="value">This shows the last 100 lines of /var/www/salesfire.log.<br /><pre style="white-space: nowrap; max-width: 600px; overflow-x: scroll; padding: 10px; border: 1px solid #eee; border-radius: 10px;">%s</pre></td>', implode('<br />', $lines));
    }
}
