<?php

namespace Salesfire\Salesfire\Helper\Logger;
 
use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;
 
/**
 * Salesfire Logging Handler
 *
 * @category   Salesfire
 * @package    Salesfire_Salesfire
 * @version    1.3.3
 */
class Handler extends Base
{
    protected $loggerType = Logger::INFO;
 
    protected $fileName = '/var/log/salesfire.log';
}
