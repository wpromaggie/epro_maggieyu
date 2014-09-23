<?php
require_once('cli.php');
util::load_lib('ppc', 'account', 'sbs', 'ql');

/*
 * get all yesterday's data for all companies for the given market
 * 
 * flags
 * -m
 *   [m]arket. required. the market to get data for
 * -b, -e
 *   [b]egin and [e]nd date. run company reports from begin date to end date
 * -c
 *   [c]lient data only.
 * -d
 *   [d]ate. run company report for the specified date
 * -g
 *   debu[g]. turn on debugging for api
 * -p
 *   [p]rocess only. useful for when something went wrong between the retrieval
 *   of the raw data and its formatting
 * -a
 *   master [a]ccount. don't run for entire company, just the master account provided
 * -r, R
 *   [r]emove previous day's report. basically for cron job. -[R] = remove only
 * -o
 *   send report to e[o]ne
 * -q
 *   [q]l data
 */

#db::dbg();

// get the market
$market = cli::$args['m'];
if (empty($market))
{
	echo "Usage: get_data.php -m [market]\n";
	exit(1);
}

// check for begin and end dates
if (array_key_exists('b', cli::$args) && array_key_exists('e', cli::$args))
{
	$data_dates = array();
	for ($i = cli::$args['b'], $end = cli::$args['e']; $i <= $end; $i = date('Y-m-d', strtotime("$i +1 day")))
		$data_dates[] = $i;
}
// check command line for a single date. default is yesterday
else
{
	$tmp = (array_key_exists('d', cli::$args)) ? cli::$args['d'] : date('Y-m-d', time() - 86400);
	$data_dates = array($tmp);
}

// get companies
$companies = db::select("
	select distinct company
	from {$market}_api_accounts
");
$num_companies = count($companies);

// loop over dates and run reports
foreach ($data_dates as $data_date)
{
	// check for master account id - run report for just that account
	if (array_key_exists('a', cli::$args))
	{
		$ac_id = cli::$args['a'];
		$company = db::select_one("select company from {$market}_accounts where id='$ac_id'");

		// init api
		$api_name = $market.'_api';
		$api = new $api_name($company, $ac_id);

		#$api->set_debug(WPRO_SOAP_DEBUG_ALL);
		
		// run the company report
		$master_report = $api->run_master_report($data_date);
		if ($master_report) $api->process_account_report($master_report);
		else print_r($api->get_error());
		
		$cl_id = db::select_one("select client from data_sources where market='$market' && account='$ac_id'");
		util::refresh_client_data($cl_id, $market, $data_date, $data_date);
	}
	else
	{
		for ($i = 0; $i < $num_companies; ++$i)
		{
			$company = $companies[$i];
			
			// init api
			$api_name = $market.'_api';
			$api = new $api_name($company);
			
			if (array_key_exists('g', cli::$args)) $api->debug();
			
			// delete previous days reports
			if (array_key_exists('r', cli::$args) || array_key_exists('R', cli::$args))
			{
				$api->delete_report_files(date('Y-m-d', strtotime("$data_date -1 day")));
				
				// remove only, continue
				if (array_key_exists('R', cli::$args)) continue;
			}
			
			// just update ql
			else if (array_key_exists('q', cli::$args))
			{
				sbs_lib::update_all_active_urls_data('ql', $market, $data_date);
			}
			// just update client data tables
			else if (array_key_exists('c', cli::$args))
			{
				$api->update_all_client_data($data_date, array('do_update_status' => true));
				sbs_lib::update_all_active_urls_data('ql', $market, $data_date, array('do_delete' => false));
				
				$api->update_report_status($market, $data_date, 'Completed');
			}
			// default: run and process company report
			else
			{
				init_report_status($market, $data_date);
				$company_report = $api->run_company_report($data_date);
				if ($company_report)
				{
					$api->update_report_status($market, $data_date, 'Processing Report');
					$api->process_company_report($company_report, $data_date);
					
					$api->update_report_status($market, $data_date, 'Updating Client Data');
					$api->update_all_client_data($data_date, array('do_update_status' => true));
					sbs_lib::update_all_active_urls_data('ql', $market, $data_date, array('do_delete' => false));
					
					$api->update_report_status($market, $data_date, 'Completed');
				}
			}
		}
	}
}

function init_report_status($market, $date)
{
	db::insert("eppctwo.market_data_status", array(
		'market' => $market,
		'd' => $date,
		't' => date('H:i:s'),
		'status' => 'Running Report'
	));
}

?>