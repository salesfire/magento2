<?php
namespace Salesfire\Salesfire\Controller\Index;

use Exception;

/**
 * Salesfire Controller
 *
 * @category   Salesfire
 * @package    Salesfire_Salesfire
 * @version.   1.2.1
 */
class Index extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    protected $_productMetadata;
    public $helperData;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Salesfire\Salesfire\Helper\Data $helperData,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    ) {
        $this->_pageFactory     = $pageFactory;
        $this->_productMetadata = $productMetadata;
        $this->helperData       = $helperData;
        return parent::__construct($context);
    }

    public function getHelper()
    {
         return $this->helperData;
    }

    public function execute()
    {
        echo implode(',', [
            $this->getHelper()->getVersion(),
            $this->getHelper()->isEnabled() ? '1' : '0',
            $this->getHelper()->getSiteId(),
            $this->getHelper()->isFeedEnabled() ? '1' : '0',
            $this->_productMetadata->getVersion(),
        ]);
        exit;
    }
}
