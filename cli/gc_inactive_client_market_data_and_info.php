<?php
require('cli.php');

cli::run();

class gc_inactive_client_market_data_and_info
{
	// before initial gc: used = 114181576
	//                           114181584
	
	// for our daily cron jobs
	// 70 days
	const CRON_MAX_LIFETIME = 6048000;
	
	// for cron and manual use
	public static function cron()
	{
		$inactive_clients = db::select("
			select distinct p.client
			from (
				select client_id, max(date_attributed) max_date
				from eppctwo.client_payment
				group by client_id
			)
			max_dates, eppctwo.clients_ppc p
			where
				max_dates.max_date <= '".date('Y-m-d', time() - self::CRON_MAX_LIFETIME)."' &&
				(p.status = 'Off' || p.status = 'Cancelled') &&
				p.client = max_dates.client_id
		");
		
		$cl_info = db::select("select id, name, data_id from eppctwo.clients where id in (".implode(',', $inactive_clients).")", 'NUM', 0);
		
		$markets = util::get_ppc_markets();
		
		$info_tables = array('campaigns', 'ad_groups', 'ads', 'keywords');
		$data_tables = array_merge(array('clients'), $info_tables);
		
		$j = 0;
		$total_deleted = 0;
		for ($i = 0, $ci = count($inactive_clients); $i < $ci; ++$i)
		{
			$cl_id = $inactive_clients[$i];
			list($cl_name, $data_id) = $cl_info[$cl_id];
			$cl_deleted = 0;
			if ($data_id != -1)
			{
				echo "\n----------\n($i,$j/$ci) $cl_id, $cl_name, $data_id (http://e2.wpromote.com/ppc/billing?cl_id={$cl_id})(http://e2.wpromote.com/ppc?cl_id={$cl_id})\n----------\n";
				foreach ($markets as $market)
				{
					$cl_deleted += self::clear_client_rows($cl_id, $data_id, $market, 'info', 'campaigns', $info_tables);
					$cl_deleted += self::clear_client_rows($cl_id, $data_id, $market, 'data', 'clients', $data_tables);
				}
				$total_deleted += $cl_deleted;
				echo "cl_del=$cl_deleted\nt_del=$total_deleted\n";
			}
		}
	}
	
	private static function clear_client_rows($cl_id, $data_id, $market, $i_or_d, $count_table, &$tables)
	{
		$count = db::select_one("
			select count(*)
			from {$market}_{$i_or_d}.{$count_table}_{$data_id}
			where client = '$cl_id'
		");
		$num_deleted = 0;
		if ($count)
		{
			foreach ($tables as $table)
			{
				echo "$cl_id, $data_id, $market, $i_or_d, $table: ".db::select_one("
					select count(*)
					from {$market}_{$i_or_d}.{$table}_{$data_id}
					where client = '$cl_id'
				")."\n";
				$num_deleted += db::delete("{$market}_{$i_or_d}.{$table}_{$data_id}", "client = '$cl_id'");
			}
			echo "\n";
		}
		return $num_deleted;
	}
}
	
?>