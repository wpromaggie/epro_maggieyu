<?php
namespace rs;

define('rs\DB_TYPE', 'mysql');

define('rs\DB_TYPE_HOST', '127.0.0.1');
define('rs\DB_TYPE_USER', 'root');
define('rs\DB_TYPE_PASS', '123');

define('rs\SYNC_LOG_PATH', '/path/to/sync/log/mylog.php');

class env
{
	public static $sync_dirs = array(
		'/path/to/sync/dir/',
		'/another/dir/with/rs/files/'
	);
}


?>