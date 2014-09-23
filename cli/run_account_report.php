<?php
require_once('cli.php');

/*
 * get data for account
 * 
 * flags
 * -m
 *   [m]arket. required. the market to get data for
 * -s, -e
 *   [s]tart and [e]nd date. run company reports from begin date to end date
 * -a
 *   master [a]ccount. don't run for entire company, just the master account provided
 */

cli::run();

class run_account_report
{
	public static function go()
	{
		list($market, $ac_id, $start_date, $end_date, $date, $report_filename, $debug) = util::list_assoc(cli::$args, 'm', 'a', 's', 'e', 'd', 'f', 'g');
		
		if (empty($start_date) || empty($end_date))
		{
			$start_date = $date;
			$end_date = $date;
		}

		if (empty($market) || empty($ac_id) || empty($start_date) || empty($end_date))
		{
			echo "Usage: get_data.php -m market -a ac_id (-s start_date -e end_date|-d date)\n";
			exit(1);
		}

		$company = db::select_one("select company from eppctwo.{$market}_accounts where id='$ac_id'");

		// init api
		$api_name = $market.'_api';
		$api = new $api_name($company, $ac_id);

		if ($debug)
		{
			db::dbg();
			$api->debug();
		}

		if (!empty($report_filename)) $report_path = \epro\REPORTS_PATH.$report_filename;
		else $report_path = $api->run_account_report($start_date, $end_date, REPORT_SHOW_STATUS);
		
		if ($report_path)
		{
			$api->process_account_report($report_path);
			
			$cl_id = db::select_one("select client from data_sources where market='$market' && account='$ac_id'");
			util::refresh_client_data($cl_id, $market, $start_date, $end_date);
		}
		else
		{
			print_r($api->get_error());
		}
	}
}

?>