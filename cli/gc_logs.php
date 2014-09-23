<?php
require('cli.php');

cli::run();

class gc_logs
{
	// for our daily cron jobs
	// 10 days
	const CRON_MAX_LIFETIME = 864000;
	
	// for cron
	public static function cron()
	{
		db::dbg();
		$exp_date = date(util::DATE_TIME, time() - self::CRON_MAX_LIFETIME);
		db::delete("
			delete from log.network_log
			where dt <= '$exp_date'
		");
	}
}
	
?>