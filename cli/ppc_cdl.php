<?php
require_once('cli.php');
util::load_lib('ppc');

cli::run();

class ppc_cdl
{
	public static function cron_cdl()
	{
		// check for [d]on't wait flag
		if (!array_key_exists('d', cli::$args))
		{
			cli::wait_for_data();
		}
		
		$clients = db::select("
			select c.id, p.prev_bill_date, p.next_bill_date
			from eppctwo.clients c, eppctwo.clients_ppc p
			where
				c.data_id <> -1 &&
				p.prev_bill_date <> '0000-00-00' &&
				p.next_bill_date <> '0000-00-00' &&
				c.id = p.client
		");
		
		for ($i = 0, $ci = count($clients); list($cl_id, $prev_bill_date, $next_bill_date) = $clients[$i]; ++$i)
		{
			// [v]erbose
			if (array_key_exists('v', cli::$args))
			{
				echo ($i+1)." / $ci: $cl_id\n";
			}
			ppc_lib::calculate_cdl_vals($cl_id, $prev_bill_date, $next_bill_date);
		}
	}
}

?>