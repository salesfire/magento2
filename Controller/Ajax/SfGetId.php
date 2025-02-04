<?php

namespace Salesfire\Salesfire\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Salesfire\Salesfire\Helper\CookieHelper;

class SfGetId extends Action
{
    protected $resultJsonFactory;
    protected $cookieHelper;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CookieHelper $cookieHelper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->cookieHelper = $cookieHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        return $result->setData($this->cookieHelper->setCuidCookie());
    }
}
