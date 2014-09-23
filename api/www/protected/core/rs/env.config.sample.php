<?php
namespace rs;

define('rs\DB_TYPE', 'db-type');

define('rs\DB_TYPE_HOST', 'host');
define('rs\DB_TYPE_USER', 'user');
define('rs\DB_TYPE_PASS', 'pass');

define('rs\SYNC_LOG_PATH', '/path/to/sync/log/mylog.php');

class env
{
	public static $sync_dirs = array(
		'/path/to/sync/dir/',
		'/another/dir/with/rs/files/'
	);
}


?>