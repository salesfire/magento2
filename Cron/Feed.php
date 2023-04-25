<?php

namespace Salesfire\Salesfire\Cron;

use Salesfire\Salesfire\Helper\Feed\Generator;

/**
 * Salesfire Feed Cron
 *
 * @category   Salesfire
 * @package    Salesfire_Salesfire
 * @version.   1.3.0
 */
class Feed
{
    private $_feedGenerator;

    public function __construct(
        Generator $feedGenerator
    ) {
        $this->_feedGenerator = $feedGenerator;
    }

    public function execute()
    {
        $this->_feedGenerator->execute();
    }
}
