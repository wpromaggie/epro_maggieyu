<?php
require_once('cli.php');

// go back two months
define('ACTIVATE_START_DATE', date(util::DATE, time() - 5184000));

$markets = util::get_ppc_markets();

// update data speeds, randomize order we process
$data_ids = range(0, util::DATA_TABLE_COUNT - 1);
shuffle($data_ids);
for ($i = 0; $i < util::DATA_TABLE_COUNT; ++$i)
{
	util::data_speed_update($data_ids[$i]);
}

$active_no_id = db::select("
	select id, name
	from eppctwo.clients
	where status = 'On' && data_id = -1
");

for ($i = 0; list($cl_id, $cl_name) = $active_no_id[$i]; ++$i)
{
	list($data_id, $speed) = db::select_row("select id, speed from eppctwo.data_speeds order by speed asc limit 1");
	echo ($i+1).'/'.count($active_no_id).": $cl_id, $cl_name, $data_id, $speed\n";
	
	db::exec("update eppctwo.clients set data_id=$data_id where id='$cl_id'");
	$num_active_markets = 0;
	foreach ($markets as $market)
	{
		echo "market: $market\n";
		if (!util::set_client_data_query($data_query, $market, $cl_id)) continue;
		
		echo "$market: checking for past data in raw tables\n";
		$date_ranges = array();
		for ($date = date(util::DATE, time() - 86400), $end_date = ''; $date >= ACTIVATE_START_DATE; $date = date(util::DATE, strtotime("$date -1 day")))
		{
			if (preg_match("/-01$/", $date)) echo "$market, $date\n";
			$table_name = str_replace('-', '_', $date);
			$count = db::select_one("select count(*) from {$market}_data_tmp.{$table_name} where $data_query");
			if ($count == 0)
			{
				if (!empty($end_date))
				{
					$date_ranges[] = array(date(util::DATE, strtotime("$date +1 day")), $end_date);
					$end_date = '';
				}
			}
			else if (empty($end_date))
			{
				$end_date = $date;
			}
		}
		// add on final range
		if (!empty($end_date)) $date_ranges[] = array(ACTIVATE_START_DATE, $end_date);
		
		// check that we actually found some data
		if (empty($date_ranges)) continue;
		
		$num_active_markets++;
		
		echo "$market: active, setting old data\n";
		for ($j = 0; list($start_date, $end_date) = $date_ranges[$j]; ++$j)
		{
			util::refresh_client_data($cl_id, $market, $start_date, $end_date, util::REFRESH_CLIENT_DATA_PROGRESS_DAILY);
		}
		
		// update info tables
		util::update_client_info_tables($market, $cl_id, $data_id, $data_query, true);
	}
	// if client didn't actually have any active markets, reset data id
	if ($num_active_markets == 0)
	{
		db::exec("update eppctwo.clients set data_id=-1 where id='$cl_id'");
	}
	
	echo "\n\n";
}

?>