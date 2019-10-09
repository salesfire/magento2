<?php
require '../app/bootstrap.php';
if (php_sapi_name() !== 'cli' && isset($_GET['job'])) {
    define('CRONJOBCLASS', $_GET['job']);
} elseif (php_sapi_name() !== 'cli') {
    die('Please add the class of the cron job you want to execute as a job parameter (?job=Vendor\Module\Class)');
} elseif (!isset($argv[1])) {
    die('Please add the class of the cron job you want to execute enclosed IN DOUBLE QUOTES as a parameter.' . PHP_EOL);
} else {
    define('CRONJOBCLASS', $argv[1]);
}

class CronRunner extends \Magento\Framework\App\Http
    implements \Magento\Framework\AppInterface
{

    public function __construct(
        \Magento\Framework\App\State $state,\Magento\Framework\App\Response\Http $response)
    {
        $this->_response = $response;
        $state->setAreaCode('adminhtml');
    }

    function launch()
    {
        $cron = \Magento\Framework\App\ObjectManager::getInstance()
            ->create(CRONJOBCLASS);

        $cron->execute();
        return $this->_response;
    }
}

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$app = $bootstrap->createApplication('CronRunner');
$bootstrap->run($app);
