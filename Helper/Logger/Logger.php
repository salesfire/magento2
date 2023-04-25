<?php

namespace Salesfire\Salesfire\Helper\Logger;
 
/**
 * Using Magento's native logger class as a replacement for Zend which is not included with the Magento framework anymore.
 * https://community.magento.com/t5/Magento-DevBlog/Migration-of-Zend-Framework-to-the-Laminas-Project/ba-p/443251
 */
class Logger extends \Monolog\Logger
{
}
