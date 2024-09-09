<?php
namespace Salesfire\Salesfire\Controller\Config;

/**
 * Salesfire Controller
 *
 * @category   Salesfire
 * @package    Salesfire_Salesfire
 * @version    1.4.15
 */
class Index extends \Magento\Framework\App\Action\Action
{
    protected $_jsonFactory;
    protected $_productMetadata;
    protected $_storeManager;
    public $helperData;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Salesfire\Salesfire\Helper\Data $helperData,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_jsonFactory      = $jsonFactory;
        $this->_productMetadata  = $productMetadata;
        $this->helperData        = $helperData;
        $this->_storeManager     = $storeManager;

        return parent::__construct($context);
    }

    public function getHelper()
    {
         return $this->helperData;
    }

    public function execute()
    {
        $result = $this->_jsonFactory->create();

        $data = [
            'version'         => $this->getHelper()->getVersion(),
            'is_enabled'      => $this->getHelper()->isEnabled() ? true : false,
            'site_id'         => $this->getHelper()->getSiteId(),
            'is_feed_enabled' => $this->getHelper()->isFeedEnabled() ? true : false,
            'magento_version' => $this->_productMetadata->getVersion(),
            'is_single_store' => $this->getHelper()->isSingleStoreMode(),
            'stores'          => $this->getHelper()->getStoreViews(),
        ];

        $result->setData($data);

        return $result;
    }
}
