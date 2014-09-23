<?php
require_once('cli.php');

/*
 * 
 * flags
 * -m
 *   [m]arket. required.
 * -s, -e
 *   [s]tart and [e]nd date. required.
 * -c
 *   [c]lient id. required.
 */

#db::dbg();

// get the market
list($market, $cl_id, $start_date, $end_date) = util::list_assoc(cli::$args, 'm', 'c', 's', 'e');
if (empty($market) || empty($cl_id) || empty($start_date) || empty($end_date))
{
	echo "Usage: get_data.php -m [market] -c [cl_id] -s [start date] -e [end date]\n";
	exit(1);
}

// loop over dates, refresh data
util::refresh_client_data($cl_id, $market, $start_date, $end_date, util::REFRESH_CLIENT_DATA_DO_RESET_DATA | REFRESH_CLIENT_DATA_PROGRESS_DAILY);
?>