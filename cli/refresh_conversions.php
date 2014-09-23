<?php
require('cli.php');

$market = cli::$args['m'];
if (empty($market))
{
	echo "Usage: ".$argv[0]." -m market\n";
	exit(1);
}


if ($market == 'g') refresh_google_conversions();



// adwords cookie lasts 31 days, so only need to refresh that far back
function refresh_google_conversions()
{
	if (array_key_exists('s', cli::$args))
	{
		list($start_date, $end_date) = util::list_assoc(cli::$args, 's', 'e');
	}
	else
	{
		// end date is 2 days ago: we don't need to refresh conversions for the previous day
		// this also means in theory we only need to 30 days back from end date
		$end_date = date(util::DATE, time() - 172800);
		$start_date = date(util::DATE, strtotime("$end_date -31 days"));
	}
	
	$api = new g_api(1);
	$report_path = $api->run_conversion_refresh_report($start_date, $end_date);
	$api->process_conversion_refresh_report($report_path);
	#unlink($report_path);
}


?>