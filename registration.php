<?php

class Salesfire_Autoload
{

    public function register()
    {
        spl_autoload_register(array($this, 'salesfire_sdk_load'), true, true);
    }

    public static function salesfire_sdk_load($class)
    {
        if (preg_match('#^(Salesfire\\\\)\b#', $class)) {
            $phpFile = __DIR__ . DIRECTORY_SEPARATOR;
            $phpFile .= implode(DIRECTORY_SEPARATOR, array('lib', 'Salesfire', 'Salesfire', 'src')) . DIRECTORY_SEPARATOR;
            $phpFile .= str_replace('\\', DIRECTORY_SEPARATOR, preg_replace( '/^Salesfire\\\\/i', '', $class));
            $phpFile .= '.php';

            if (file_exists($phpFile)) {
                require_once($phpFile);
            }
        }
    }

}

$autoload = new Salesfire_Autoload;
$autoload->register();

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Salesfire_Salesfire',
    __DIR__
);
