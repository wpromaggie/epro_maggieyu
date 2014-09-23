<?php
require_once('cli.php');
util::load_rs('ppc');
util::load_lib('jobs');

cli::run();

class cron_ppc_scheduled_refreshes
{
	// lower is higher precedence
	private static $frequency_precedence = array(
		'Monthly' => 0,
		'Weekly' => 1
	);
	public static function go()
	{
		$interval = 15;
		$per_60 = 4;
		
		$next_day_time = time() + 86400;
		$is_last_of_month = (date('j', $next_day_time) == 1);
		
		list($hour, $minute) = explode(':', date('H:i'));
		$time_for_day_calcs = time();
		
		$temp = round($minute / $interval);
		if ($temp == $per_60)
		{
			$hour = str_pad(($hour + 1) % 24, 2, '0', STR_PAD_LEFT);
			if ($hour == '00')
			{
				$time_for_day_calcs += 43200;
				#$day_of_week = date('l', $next_day_time);
				if ($is_last_of_month)
				{
					$is_last_of_month = false;
				}
			}
			else
			{
				#$day_of_week = date('l');
			}
			$boundary = $hour.':00:00';
		}
		else
		{
			$boundary = $hour.':'.str_pad(($temp * $interval), 2, '0', STR_PAD_LEFT).':00';
		}
		$day_of_week = date('l', $time_for_day_calcs);
		$day_of_month = date('j', $time_for_day_calcs);
		
		$weekly_where = "
			ppc_schedule_refresh.frequency = 'Weekly' &&
			ppc_schedule_refresh.day_of_week = '$day_of_week'
		";
		
		$monthly_where = "
			ppc_schedule_refresh.frequency = 'Monthly' &&
			ppc_schedule_refresh.day_of_month ".(($is_last_of_month) ? ">=" : "=")." $day_of_month
		";
		
		$where = "
			ppc_schedule_refresh.time = '{$boundary}' &&
			(($weekly_where) || ($monthly_where))
		";
		$refreshes = ppc_schedule_refresh::get_all(array(
			'where' => $where
		));
		$refreshes->sort(array('cron_ppc_scheduled_refreshes', 'refresh_sort'));
		
		// track clients, don't need to run twice
		$clients = array();
		foreach ($refreshes as $refresh)
		{
			if (!array_key_exists($refresh->client_id, $clients))
			{
				$clients[$refresh->client_id] = 1;
				
				$end_date = date(util::DATE, $time_for_day_calcs);
				$start_date = date(util::DATE, strtotime("$end_date -".($refresh->num_days - 0)." days")); // -1: inclusive
				$dsr = new ppc_data_source_refresh(array(
					'client_id' => $refresh->client_id,
					'refresh_type' => 'remote',
					'market' => 'g',
					'start_date' => $start_date,
					'end_date' => $end_date
				));
				$dsr->put();
				jobs::schedule($dsr->id, 'PPC DATA SOURCE REFRESH', $refresh->user_id, $refresh->client_id);
			}
		}
	}
	
	public static function refresh_sort(&$a, &$b)
	{
		return (self::$frequency_precedence[$a->frequency] - self::$frequency_precedence[$b->frequency]);
	}
}

?>