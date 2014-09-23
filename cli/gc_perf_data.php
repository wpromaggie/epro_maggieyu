<?php
require('cli.php');

cli::run();

class gc_perf_data
{
	// for our daily cron jobs
	// 3 days
	const CRON_MAX_LIFETIME = 259200;
	
	public static function cron()
	{
		$now = time();
		foreach (glob('perf_*.csv.tgz') as $file)
		{
			$mtime = filemtime($file);
			$diff = $now - $mtime;
			echo "$file, $diff = $now - $mtime\n";
			if ($diff > self::CRON_MAX_LIFETIME)
			{
				echo "deleting: $file\n";
				unlink($file);
			}
		}
	}
}

?>