<?php

/*
 * problem: browser cache js and css (and other?) files
 * checks to see if any static files were updated and updates corresponding entry in
 * static file log
 */

require_once(__DIR__.'/../../core/env.php');
require_once(\epro\COMMON_PATH.'util.php');
util::load_lib('svn');

// no matter what we're doing, we want to do it from e2cgi home
chdir(\epro\CGI_PATH);

// hack so check_for_updates() can include current log
class cgi
{
	public static $file_versions;
}

class static_file_versioning
{
	private static function is_static_file($file_path)
	{
		return (preg_match("/(\.js|\.css)$/", $file_path));
	}
	
	public static function check_for_updates($prev_rev_number, $cur_rev_number)
	{
		if ($cur_rev_number < $prev_rev_number)
		{
			return;
		}
		require(\epro\NO_CACHE_FILEPATH);
		for ($rev_number = $prev_rev_number + 1; $rev_number <= $cur_rev_number; ++$rev_number)
		{
			$revisions = svn::get_revisions($rev_number);
			foreach ($revisions as $rev_info)
			{
				list($file_path, $action) = util::list_assoc($rev_info, 'path', 'action');
				$file_path = ltrim($file_path, '/');
				if (self::is_static_file($file_path))
				{
					if ($action == 'D')
					{
						echo "Deleted: $file_path\n";
						unset(cgi::$file_versions[$file_path]);
					}
					else
					{
						echo "Updated: $file_path -> $rev_number\n";
						cgi::$file_versions[$file_path] = $rev_number;
					}
				}
			}
		}
		self::write_log_file(cgi::$file_versions);
	}
	
	/*
	 * initialize the log with revision numbers of all curently svn'ed static files
	 */
	public static function init()
	{
		$all_files = svn::ls('.', '-R');
		
		// get the ones we want
		$static_files = array();
		foreach ($all_files as $file_path)
		{
			if (self::is_static_file($file_path))
			{
				list($last_rev) = svn::get_info($file_path, 'Last Changed Rev');
				$static_files[$file_path] = $last_rev;
			}
		}
		
		self::write_log_file($static_files);
	}
	
	private static function write_log_file($files)
	{
		// loop over static files
		// get last changed revision
		// add to log
		$log_str = '';
		foreach ($files as $file_path => $last_rev)
		{
			if ($log_str) $log_str .= ",\n";
			$log_str .= "'{$file_path}'=>{$last_rev}";
		}
		$code_str = "<?php
cgi::\$file_versions = array(
$log_str
);
?>";
		file_put_contents(\epro\NO_CACHE_FILEPATH, $code_str);
	}
}


if (strpos(__FILE__, $argv[0]) !== false)
{
	if (in_array('-i', $argv))
	{
		static_file_versioning::init();
	}
	// default action is to check for updates, expect rev number before after updates took place and current rev number
	else if (count($argv) > 2)
	{
		$prev_rev_number = $argv[1];
		$cur_rev_number = $argv[2];
		static_file_versioning::check_for_updates($prev_rev_number, $cur_rev_number);
	}
}

?>