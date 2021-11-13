#!/usr/bin/env php
<?php
/**
 * Restore .htaccess file if USVN already installed
 **/

require_once realpath(dirname(__FILE__) . '/install.includes.php');

function display_help($options)
{
    $basename = basename(__FILE__);
    echo <<<EOF
Usage: ./{$basename} [OPTIONS]

{$basename} allows to restore .htaccess file in public folder. For example, after Docker container update.

EOF;
    exit(0);
}

try
{
    if (!Install::installPossible(USVN_CONFIG_FILE))
    {
        $config = new USVN_Config_Ini(USVN_CONFIG_FILE, USVN_CONFIG_SECTION, array('create' => false));
		installation_configure('subversion', function()
		{
            Install::installUrl(USVN_CONFIG_FILE, USVN_HTACCESS_FILE, $config->url->usvn, $config->server->host, $config->url->isHttps);
        });
    }
    else
    {
        echo 'Error: USVN is not installed.' . "\n";
        echo 'Aborting.' . "\n";
        exit(1);
    }
}
catch (USVN_Exception $e)
{
    echo $e->getMessage();
    exit(1);
}

?>