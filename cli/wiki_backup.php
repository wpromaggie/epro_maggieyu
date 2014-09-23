<?php
require_once('cli.php');

define('WIKI_MEDIA_PATH', \epro\CGI_PATH.'wiki/images/');

cli::run();

class wiki_backup
{
	const ADMIN_EMAIL = 'kevin@wpromote.com';
	const AUTH = '-i /home/tnegiusfe/.ssh/id_rsa';
	const USER_AND_REMOTE = 'kmoney@cynta.wpromote.com';
	const REMOTE_PATH = '/home/kmoney/bak/wiki/media/';

	const DEFAULT_CUTOFF = 86400;

	private $is_verbose;

	public function __construct()
	{
		$this->is_verbose = (array_key_exists('v', cli::$args));
	}

	public function all()
	{
		$this->recur(WIKI_MEDIA_PATH);
	}

	private function recur($dir, $mtime = false)
	{
		if ($this->is_verbose) echo "\nd=$dir\n";
		$fcount = 0;
		foreach (glob($dir.'*') as $path) {
			if (is_dir($path)) {
				$this->recur($path.'/', $mtime);
			}
			else {
				// first file we have seen in this dir, make remote directory
				$rel_dir = substr(dirname($path), strlen(WIKI_MEDIA_PATH)).'/';
				if ($fcount == 0) {
					$cmd = 'ssh '.self::AUTH.' '.self::USER_AND_REMOTE.' "mkdir -p '.self::REMOTE_PATH.$rel_dir.'"';
					if ($this->is_verbose) echo "making dir: $cmd\n";
					exec($cmd);
				}
				$fcount++;

				if ($this->is_verbose && $mtime) {
					echo "testing mtime: ".filemtime($path)." > $mtime\n";
				}
				if (!$mtime || filemtime($path) > $mtime) {
					// zip it
					$zipped_basename = basename($path).'.gz';
					$zipped_path = sys_get_temp_dir().'/'.$zipped_basename;
					$cmd = 'gzip -c '.$path.' > '.$zipped_path;
					if ($this->is_verbose) echo "zipping: $cmd\n";
					exec($cmd);

					// transfer it
					$cmd = 'scp '.self::AUTH.' '.$zipped_path.' '.self::USER_AND_REMOTE.':'.self::REMOTE_PATH.$rel_dir.$zipped_basename;
					if ($this->is_verbose) echo "sending file: $cmd\n";
					exec($cmd);

					// delete it
					unlink($zipped_path);
				}
			}
		}
	}

	public function recent()
	{
		$cutoff_ago = (array_key_exists('m', cli::$args)) ? cli::$args['m'] : self::DEFAULT_CUTOFF;
		$cutoff_utime = time() - $cutoff_ago;
		$this->recur(WIKI_MEDIA_PATH, $cutoff_utime);
	}
}

?>