<?php

function wpro_require($file)
{ 
	if (defined('WPRO_PATH'))
	{
		require_once(WPRO_PATH.$file);
	}
	else
	{
		require_once($file);
	}
} // wpro_require

// set some environment stuff
define('IS_CLI', (defined('PHP_SAPI') && PHP_SAPI == 'cli'));
define('IS_CGI', !IS_CLI);
define('ENDL', ((IS_CLI) ? "\n" : "<br />\n"));

require('functions.php');
require('ini.php');
$wpro = wpro::create_globals(array('version' => '1.0.5'));
if (IS_CGI)
{
	wpro::do_magic();
	wpro::get_paths();
	wpro::get_base_classes();
	wpro::get_requested_classes();
}
?>