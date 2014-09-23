<?php
require_once('cli.php');

$zip = new ZipArchive();
$zip->open('cl_export.zip');

$export_args = import_meta($zip);
// you can manually set export args if something went wrong
#$export_args = array_merge($export_args, array('b' => '2008-01-01', 'e' => '2008-05-31'));

$cl_id = $export_args['c'];
if (array_key_exists('d', $export_args)) import_data($zip, $cl_id, $export_args['b'], $export_args['e']);
if (array_key_exists('i', $export_args)) import_info($zip, $cl_id);

function import_meta(&$zip)
{
	$meta = unserialize($zip->getFromName('cl_export_meta.php'));
	print_r($meta);
	list($cl_id, $company, $name, $status, $data_id, $external_id) = util::list_assoc($meta, 'id', 'company', 'name', 'status', 'data_id', 'external_id');
	
	// client table
	db::insert_update("eppctwo.clients", array('id'), array(
		'id' => $cl_id,
		'company' => $company,
		'name' => $name,
		'status' => $status,
		'data_id' => $data_id,
		'external_id' => $external_id
	));
	
	// client types
	$all_types = util::get_client_types();
	for ($i = 0; list($cl_type, $cl_type_display) = $all_types[$i]; ++$i)
		db::exec("delete from clients_{$cl_type} where client='$cl_id'");
	
	$cl_types = $meta['cl_types'];
	foreach ($cl_types as $cl_type => $cl_type_info)
	{
		foreach ($cl_type_info as $d)
		{
			db::insert("eppctwo.clients_{$cl_type}", $d);
		}
	}
	
	// accounts
	$accounts = &$meta['accounts'];
	foreach ($accounts as $market => $market_accounts)
	{
		foreach ($market_accounts as $ac_data)
		{
			db::exec("delete from {$market}_accounts where id='".$ac_data['id']."'");
			db::insert("eppctwo.{$market}_accounts", $ac_data);
		}
	}
	
	// data sources
	db::exec("delete from data_sources where client='$cl_id'");
	$data_sources = $meta['data_sources'];
	foreach ($data_sources as $data_source)
	{
		db::insert("eppctwo.data_sources", $data_source);
	}
	
	// gae convs
	$gae_convs = unserialize($zip->getFromName('cl_gae_convs.php'));
	for ($i = 0, $ci = count($gae_convs); $i < $ci; ++$i)
	{
		$d = $gae_convs[$i];
		list($gae_conv_id, $gae_redirect_id, $market, $gae_cl_id) = $d;
		if ($gae_cl_id == $cl_id)
		{
			db::insert("eppctwo.gae_convs", $d);
		}
	}
	return $meta['export_args'];
}

function import_data(&$zip, $cl_id, $begin_date, $end_date)
{
	$data_id = db::select_one("select data_id from clients where id='$cl_id'");
	$aggregation_types = array('clients', 'campaigns', 'ad_groups', 'ads', 'keywords');
	$markets = util::get_ppc_markets();
	foreach ($markets as $market)
	{
		foreach ($aggregation_types as $aggregation_type)
		{
			$file_name = "data_{$market}_{$aggregation_type}.mysql";
			$table = "{$market}_data.{$aggregation_type}_{$data_id}";
			
			// delete old data
			db::exec("
				delete from $table
				where
					client = '$cl_id' &&
					data_date >= '$begin_date' &&
					data_date <= '$end_date'
			");
			for ($fi = new file_iterator($zip->getStream($file_name)); $fi->next($block); )
			{
				$lines = explode("\n", $block);
				echo $file_name.':'.$table.':'.$begin_date.':'.$end_date.':'.count($lines)."\n";
				foreach ($lines as $line) {
					db::exec("insert into $table values ('".str_replace("\t", "','", $line)."')");
				}
			}
		}
	}
}

function import_info(&$zip, $cl_id)
{
	$data_id = db::select_one("select data_id from clients where id='$cl_id'");
	$aggregation_types = array('campaigns', 'ad_groups', 'ads', 'keywords');
	$markets = util::get_ppc_markets();
	foreach ($markets as $market)
	{
		foreach ($aggregation_types as $aggregation_type)
		{
			$file_name = "info_{$market}_{$aggregation_type}.mysql";
			$table = "{$market}_info.{$aggregation_type}_{$data_id}";
			
			// delete old info
			db::exec("
				delete from $table
				where client = '$cl_id'
			");
			for ($fi = new file_iterator($zip->getStream($file_name)); $fi->next($block); )
			{
				$lines = explode("\n", $block);
				echo $file_name.':'.$table.':'.count($lines)."\n";
				foreach ($lines as $line)
					db::exec("insert into $table values ('".str_replace("\t", "','", addslashes($line))."')");
			}
		}
	}
}


?>