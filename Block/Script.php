<?php
namespace Salesfire\Core\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Salesfire\Core\Helper\Data as HelperData;
use Magento\Framework\ObjectManagerInterface;

class Script extends Template
{
    protected $helperData;
    protected $objectFactory;

    public function __construct(
        Context $context,
        HelperData $helperData,
        array $data = []
    )
    {
        $this->helperData       = $helperData;
        parent::__construct($context, $data);
    }

    public function getHelper()
    {
        return $this->helperData;
    }
}
