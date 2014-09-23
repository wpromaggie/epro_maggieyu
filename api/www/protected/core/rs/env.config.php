<?php
namespace rs;

define('rs\DB_TYPE', 'mysql');

define('rs\DB_TYPE_HOST', 'localhost');
define('rs\DB_TYPE_USER', 'root');
define('rs\DB_TYPE_PASS', 'elephant0');

define('rs\SYNC_LOG_PATH', '/path/to/sync/log/mylog.php');

class env
{
	public static $sync_dirs = array(
		'/path/to/sync/dir/',
		'/another/dir/with/rs/files/'
	);
}


?>
