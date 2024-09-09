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
 * @version    1.4.15
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
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Logger
     */
    protected $_logger;

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
        $lines = implode('', $this->_logger->getLastLines(500));

        return <<<EOD
        <td class="value">
            This shows the last 500 lines of /var/www/salesfire.log.
            <a style="cursor: pointer" onclick="javascript:document.getElementById('field_log').focus(),document.getElementById('field_log').setSelectionRange(0, document.getElementById('field_log').value.length)">
                Select all.
            </a>

            <textarea id="field_log" readonly onclick="this.setSelectionRange(0, this.value.length)" style="background: #fff; border: 1px solid #eee; border-radius: 10px; cursor: grab; color: #000; font-family: Consolas, monospace; height: 600px; opacity: 1; overflow-x: scroll; line-height: 18px; margin-top: 10px; padding: 10px; white-space: pre; width: 100%%;">$lines</textarea>
        </td>
        EOD;
    }
}
