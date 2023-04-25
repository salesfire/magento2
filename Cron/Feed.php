<?php

namespace Salesfire\Salesfire\Cron;

use Salesfire\Salesfire\Helper\Feed\Generator;

/**
 * Salesfire Feed Cron
 *
 * @version    1.3.3
 */
class Feed
{
    /**
     * @var \Salesfire\Salesfire\Helper\Feed\Generator
     */
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
