<?php
namespace epro;

$sapi = strtolower(php_sapi_name());
define('ENDL', ($sapi == 'cgi' || $sapi == 'apache2handler') ? "<br />\n" : "\n");

define('epro\COMMON_PATH', __DIR__.'/');
define('epro\ROOT_PATH', preg_replace("/common\/$/", '', \epro\COMMON_PATH));
define('epro\CGI_PATH', \epro\ROOT_PATH.'cgi/');
define('epro\CLI_PATH', \epro\ROOT_PATH.'cli/');
define('epro\API_PATH', \epro\ROOT_PATH.'api/');
define('epro\LOCAL_PATH', \epro\ROOT_PATH.'local/');
define('epro\WPROPHP_PATH', \epro\ROOT_PATH.'wprophp/');

define('epro\REPORTS_PATH', \epro\LOCAL_PATH.'reports/');
define('epro\OFFLINE_CONVERSION_PATH',\epro\LOCAL_PATH.'offline_conversion_data/');
define('epro\NO_CACHE_FILEPATH', \epro\CGI_PATH.'core/no_cache_file_versions.php');

define('epro\NOW', (isset($_SERVER['REQUEST_TIME'])) ? $_SERVER['REQUEST_TIME'] : time());
define('epro\TODAY', date('Y-m-d', \epro\NOW));
define('epro\MNOW', microtime(true));

// local settings
require(\epro\COMMON_PATH.'env.local.php');

?>