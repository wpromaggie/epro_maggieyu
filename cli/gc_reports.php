<?php
require('cli.php');

cli::run();

class gc_reports
{
	// for our daily cron jobs
	// 4 days
	const CRON_MAX_LIFETIME = 345600;
	
	public static function cron()
	{
		$now = time();
		$markets = util::get_ppc_markets();
		foreach ($markets as $market)
		{
			$reports_path = \epro\REPORTS_PATH.$market.'/';
			foreach (glob($reports_path.'*') as $file_path)
			{
				$mtime = filemtime($file_path);
				if (($now - $mtime) > self::CRON_MAX_LIFETIME)
				{
					unlink($file_path);
				}
				// nothing to do
				else
				{
				}
			}
		}
	}
}

?>