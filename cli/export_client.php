<?php
require_once('cli.php');

define('CL_EXPORT_DIR', 'cl_export/');

// inspect command line args
$do_get_data = array_key_exists('d', cli::$args);
$do_get_info = array_key_exists('i', cli::$args);
$is_begin_date = (array_key_exists('b', cli::$args));
$is_end_date = (array_key_exists('e', cli::$args));
$is_help = (array_key_exists('h', cli::$args));
$is_client = (array_key_exists('c', cli::$args));

if ($is_begin_date && $is_end_date)
{
	$begin_date = cli::$args['b'];
	$end_date = cli::$args['e'];
}
if (($do_get_data && (!$is_begin_date || !$is_end_date)) || !$is_client || $is_help || $end_date < $begin_date)
{
	$error_str = '';
	if (!$is_client && !$is_help) $error_str .= "ERROR: no client id\n";
	if ($do_get_data && !$is_begin_date) $error_str .= "ERROR: begin date required for data\n";
	if ($do_get_data && !$is_end_date) $error_str .= "ERROR: end date for data\n";
	if ($end_date < $begin_date) $error_str .= "ERROR: end date before begin date\n";

	if (!empty($error_str)) echo "\n$error_str\n";
	echo "Usage: ".$argv[0]." -c client_id -d -b YYYY-MM-DD -e YYYY-MM-DD -i
\t-b\tBegin date, inclusive
\t-c\tId of the client to export
\t-d\tExport data (clicks, impressions, conversions, etc)
\t-e\tEnd date, inclusive
\t-h\tShow help
\t-i\tExport info (campaigns, ad groups, ads, keywords)
";
	exit;
}
$cl_id = cli::$args['c'];

$zip_name = CL_EXPORT_DIR.'cl_export.zip';
@unlink($zip_name);
$zip = new ZipArchive();
$zip->open($zip_name, ZIPARCHIVE::CREATE);


cl_export_meta($zip, $cl_id);
if ($do_get_data) cl_export_data($zip, $cl_id, $begin_date, $end_date);
if ($do_get_info) cl_export_info($zip, $cl_id);

$zip->close();

function cl_export_meta(&$zip, $cl_id)
{
	$cl_info = db::select("select * from clients where id=$cl_id", 'ASSOC');
	$cl_info = $cl_info[0];
	
	// put in command line args
	$cl_info['export_args'] = cli::$args;
	
	// data sources
	$cl_info['data_sources'] = db::select("select * from data_sources where client='$cl_id'");
	
	// get accounts
	$accounts = db::select("select distinct market, account from data_sources where client='$cl_id'");
	for ($i = 0; list($market, $ac_id) = $accounts[$i]; ++$i) {
		$cl_info['accounts'][$market] = db::select("select * from {$market}_accounts where id='$ac_id'", 'ASSOC');
	}
	
	// client info tables
	$cl_types = util::get_client_types();
	for ($i = 0; list($cl_type, $cl_type_display) = $cl_types[$i]; ++$i)
	{
		$tmp = db::select("select * from clients_{$cl_type} where client='$cl_id'");
		if (!empty($tmp)) $cl_info['cl_types'][$cl_type] = $tmp;
	}
	
	$tmp = db::select("select * from eppctwo.gae_convs where client = '$cl_id'");
	$zip->addFromString('cl_gae_convs.php', serialize($tmp));
	
	print_r($cl_info);
	$zip->addFromString('cl_export_meta.php', serialize($cl_info));
}

function cl_export_data(&$zip, $cl_id, $begin_date, $end_date)
{
	$data_id = db::select_one("select data_id from clients where id=$cl_id");

	$aggregation_types = array('clients', 'campaigns', 'ad_groups', 'ads', 'keywords');
	$markets = util::get_ppc_markets();
	foreach ($markets as $market)
	{
		foreach ($aggregation_types as $aggregation_type)
		{
			echo "export data: $market $aggregation_type\n";
			$file_name = CL_EXPORT_DIR."data_{$market}_{$aggregation_type}.mysql";
			$table = "{$market}_data.{$aggregation_type}_{$data_id}";
			cl_export_to_file_by_date($file_name, $table, $cl_id, $begin_date, $end_date);
			$zip->addFile($file_name, basename($file_name));
		}
	}
}

function cl_export_info(&$zip, $cl_id)
{
	$data_id = db::select_one("select data_id from clients where id=$cl_id");

	$aggregation_types = array('campaigns', 'ad_groups', 'ads', 'keywords');
	$markets = util::get_ppc_markets();
	foreach ($markets as $market)
	{
		foreach ($aggregation_types as $aggregation_type)
		{
			echo "export info: $market $aggregation_type\n";
			$file_name = CL_EXPORT_DIR."info_{$market}_{$aggregation_type}.mysql";
			$table = "{$market}_info.{$aggregation_type}_{$data_id}";
			cl_export_to_file_by_limit($file_name, $table, $cl_id, 8);
			$zip->addFile($file_name, basename($file_name));
		}
	}
}

function cl_export_to_file_by_date($file_name, $table, $cl_id, $begin_date, $end_date)
{
	// create a tmp file
	$tmp_filename = tempnam('.', '');
	$tmp_handle = fopen($tmp_filename, 'wb');

	// create data file
	exec("echo -n > $file_name");

	// read data one date at a time so we don't exceed memory limit
	for ($i = $begin_date; $i <= $end_date; $i = date(util::DATE, strtotime("$i +1 day")))
	{
		$data = db::select("select * from $table where client=$cl_id && data_date='$i'");

		$data_out = '';
		foreach ($data as $d) $data_out .= implode("\t", $d)."\n";

		// write to tmp file and concatenate to standardized report
		ftruncate($tmp_handle, 0);
		fseek($tmp_handle, 0);
		fwrite($tmp_handle, $data_out);
		exec('cat '.$tmp_filename.' >> '.$file_name);
	}
	unlink($tmp_filename);

	// add newline to end of report
	exec('echo >> '.$file_name);
}

function cl_export_to_file_by_limit($file_name, $table, $cl_id, $limit)
{
	// create a tmp file
	$tmp_filename = tempnam('.', '');
	$tmp_handle = fopen($tmp_filename, 'wb');

	// create data file
	exec("echo -n > $file_name");

	// read data one date at a time so we don't exceed memory limit
	for ($i = 0; 1; $i += $limit)
	{
		$data = db::select("select * from $table where client=$cl_id limit $i, $limit");
		if (count($data) == 0) break;

		$data_out = '';
		foreach ($data as $d) $data_out .= implode("\t", $d)."\n";

		// write to tmp file and concatenate to standardized report
		ftruncate($tmp_handle, 0);
		fseek($tmp_handle, 0);
		fwrite($tmp_handle, $data_out);
		exec('cat '.$tmp_filename.' >> '.$file_name);
	}
	unlink($tmp_filename);

	// add newline to end of report
	exec('echo >> '.$file_name);
}



?>