<?php

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Salesfire_Salesfire',
    __DIR__
);


function salesfire_sdk_load( $class )
{
    if ( preg_match( '#^(Salesfire\\\\)\b#', $class ) ) {
        $phpFile = __DIR__ . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array('lib', 'Salesfire', 'Salesfire', 'src')) . DIRECTORY_SEPARATOR . str_replace( '\\', DIRECTORY_SEPARATOR, preg_replace( '/^Salesfire\\\\/i', '', $class ) ) . '.php';

        if (file_exists($phpFile)) {
            require_once( $phpFile );
        }
    }
}

spl_autoload_register( 'salesfire_sdk_load', true, true );
