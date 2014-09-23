<?php
require('cli.php');

cli::run();

class gc_email_log
{
	// for our daily cron jobs
	// 10 days
	const CRON_MAX_LIFETIME = 864000;
	
	// for cron
	// drop data tables, we don't need to worry about clearing out report table
	public static function cron()
	{
		$exp_date = date(util::DATE_TIME, time() - self::CRON_MAX_LIFETIME);
		db::exec("
			delete from eppctwo.email_log_entry
			where created <= '$exp_date'
		");
	}
}
	
?>