<?php
require_once('cli.php');

$markets = util::get_ppc_markets();

$clients = db::select("
	select id, name, data_id
	from clients
	where data_id <> -1
");

// for now we are just looking for clients who somehow had no info set
// (ie not really a refresh, just a fresh)
for ($i = 0; list($cl_id, $cl_name, $data_id) = $clients[$i]; ++$i)
{
	foreach ($markets as $market)
	{
		// see if client actually has data sources for this market
		if (!set_client_data_query($data_query, $market, $cl_id)) continue;
		
		$count = db::select_one("select count(*) from {$market}_objects.campaign_{$cl_id} where client='$cl_id'");
		
		if ($count == 0)
		{
			$actually_has_data = false;
			for ($date = date(DATE, time() - 86400), $end_date = ''; $date >= DATA_START_DATE; $date = date(DATE, strtotime("$date -1 day")))
			{
				if (preg_match("/-01$/", $date)) echo "$market, $date\n";
				$table_name = str_replace('-', '_', $date);
				$count = db::select_one("select count(*) from {$market}_data_tmp.{$table_name} where $data_query");
				if ($count > 0)
				{
					$actually_has_data = true;
					break;
				}
			}
			
			echo "$market, $cl_name, $actually_has_data\n";
			if ($actually_has_data)
			{
				db::dbg();
				update_client_info_tables($market, $cl_id, $data_id, $data_query, true);
				db::dbg_off();
			}
		}
	}
}

?>