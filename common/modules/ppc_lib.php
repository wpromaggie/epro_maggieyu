<?php
util::load_lib('as');

class ppc_lib
{
	const MAX_ALL_DATA_DAYS = 100;
	
	public static $markets = array('g', 'm', 'y', 'f');
	
	public static $market_account_objects = array('all_data', 'campaign_data', 'extension_data', 'campaign', 'ad_group', 'ad', 'keyword','shopping','offline_conversion');

	public static function calculate_cdl_vals($aid, $prev_bill_date = null, $next_bill_date = null)
	{
		$obj_key = util::get_account_object_key($aid);
		if ($obj_key === false || self::is_ql_account($obj_key)) {
			return false;
		}
		if (!$prev_bill_date) {
			list($prev_bill_date, $next_bill_date) = db::select_row("
				select prev_bill_date, next_bill_date
				from eac.account
				where id = :aid
			", array("aid" => $aid));
		}

		// we do not include next bill date in monthly calculations!
		// 1. in for loops below, < and not <=
		// 2. in query, end_date is day before next_bill_date
		
		if (!util::is_valid_date_range($prev_bill_date, $next_bill_date)) {
			return;
		}
		
		$today = date(util::DATE);
		$yesterday = date(util::DATE, time() - 86400);
		for (
			$days_to_date = 0, $date = date(util::DATE, strtotime("$prev_bill_date"));
			$date < $today && $date < $next_bill_date;
			++$days_to_date, $date = date(util::DATE, strtotime("$date +1 day"))
		);
		for (
			$days_remaining = 0;
			$date < $next_bill_date;
			++$days_remaining, $date = date(util::DATE, strtotime("$date +1 day"))
		);
		$days_in_month = $days_to_date + $days_remaining;
		
		$month_spend = $yesterday_spend = 0;
		foreach (self::$markets as $market) {
			$month_spend += db::select_one("
				select sum(cost)
				from {$market}_objects.campaign_data_{$obj_key}
				where data_date between :start_date and :end_date
			", array(
				'start_date' => $prev_bill_date,
				'end_date' => date(util::DATE, strtotime("$next_bill_date -1 day"))
			));
			
			$yesterday_spend += db::select_one("
				select sum(cost)
				from {$market}_objects.campaign_data_{$obj_key}
				where data_date = :date
			", array('date' => $yesterday));
		}
		
		db::update(
			"eppctwo.ppc_cdl",
			array(
				'mo_spend' => $month_spend,
				'yd_spend' => $yesterday_spend,
				'days_to_date' => $days_to_date,
				'days_remaining' => $days_remaining,
				'days_in_month' => $days_in_month
			),
			"account_id = :aid",
			array("aid" => $aid)
		);
	}
	
	public static function create_market_object_tables($market, $aid, $check_all = false)
	{
		self::set_market_object_tables($market, $aid);
		$do_tables_exist = null;
		foreach (self::$market_account_objects as $i => $obj_name) {
			if ($i == 0 || $check_all) {
				$do_tables_exist = $obj_name::table_exists();
			}
			if (!$do_tables_exist) {
				// let's not create a million QL extension data tables that we don't need
				if ($obj_name == 'extension_data' && self::is_ql_account($aid)) {
					continue;
				}
				$r = $obj_name::create_table("{$market}_objects", "{$obj_name}_{$aid}");
				if ($r === false) {
					return false;
				}
			}
			// one of the tables existed, we're not checking all, gtg, return true
			else if (!$check_all) {
				return true;
			}
		}
		return true;
	}

	public static function drop_market_object_tables($market, $aid)
	{
		foreach (self::$market_account_objects as $i => $obj_name) {
			db::exec("drop table {$market}_objects.{$obj_name}_{$aid}");
		}
		return true;
	}

	public static function set_market_object_tables($market, $aid)
	{
		all_data::set_object_key($aid);
		// check that not already set
		$target_db = "{$market}_objects";
		list($db_test, $table_test) = all_data::attrs('db', 'table');
		if ($db_test == $target_db && $table_test == "all_data_{$aid}") {
			return;
		}
		foreach (self::$market_account_objects as $obj_name) {
			$obj_name::set_location($target_db, "{$obj_name}_{$aid}");
		}
	}

	public static function get_company_data_pull_jobs()
	{
		return cron_job::get_all(array(
			'select' => array(
				"cron_job" => array("id as cid", "args"),
				"job" => array("id as jid", "status")
			),
			'left_join' => array(
				"job" => "
					job.fid = cron_job.id &&
					job.started between :today_start and :today_end
				"
			),
			'where' => "
				cron_job.worker = 'COMPANY MARKET DATA PULL' &&
				cron_job.status = 'Active'
			",
			'data' => array(
				"today_start" => \epro\TODAY." 00:00:00",
				"today_end" => \epro\TODAY." 23:59:59"
			)
		));
	}

	public static function is_ql_account($a)
	{
		if (is_string($a)) {
			$id = $a;
		}
		else {
			$a = (object) $a;
			$id = (isset($a->naid)) ? $a->naid : ((isset($a->id)) ? $a->id : '');
		}
		return (strlen($id) > 0 && $id[0] == 'Q');
	}
}

?>