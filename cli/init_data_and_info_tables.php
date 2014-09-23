<?php
require_once('cli.php');

/*
 * enet migration note: not updated
 */

$start_date = '2009-01-01';
$end_date = date(util::DATE, time() - 86400);

$table_count = util::DATA_TABLE_COUNT;

$markets = util::get_ppc_markets();


// reset everything
if (array_key_exists('r', cli::$args))
{
	reset_data_and_info();
}

// create the tables
if (array_key_exists('c', cli::$args))
{
	create_tables($table_count);
}

// set data
if (array_key_exists('d', cli::$args))
{
	// also responsible for assigning data ids to each client
	populate_data_tables($table_count);
}

// set info
if (array_key_exists('i', cli::$args))
{
	// uses data ids as determined by populate_data_tables
	populate_info_tables($table_count);
}

function reset_data_and_info()
{
	global $markets;
	
	$data_table_types = array('clients', 'campaigns', 'ad_groups', 'ads', 'keywords');
	
	foreach ($markets as $market)
	{
		db::use_db("{$market}_data");
		for ($i = 0; $i < util::DATA_TABLE_COUNT; ++$i)
		{
			echo "$market, ".($i+1)."/".util::DATA_TABLE_COUNT."\n";
			foreach ($data_table_types as $data_table_type)
			{
				// drop the data table
				db::exec("drop table {$data_table_type}_{$i}");
				
				// re-create it
				$create_func = 'create_'.$data_table_type.'_data_table';
				$create_func($i);
			}
		}
	}
	
	// make copy of old data ids
	// we need these so we can find old "info"
	// pass in -d flag to skip this step
	if (!array_key_exists('d', cli::$args))
	{
		$data_ids = db::select("select id, data_id from clients", 'NUM', 0);
		file_put_contents('tmp/data_reset_ids.php', serialize($data_ids));
		
		// get rid of old ids
		db::exec("update clients set data_id=-1");
	}
	
	// reset data speeds table
	db::exec("delete from data_speeds");
	for ($i = 0; $i < util::DATA_TABLE_COUNT; ++$i)
		db::exec("insert into data_speeds values ($i, 0)");
}

function create_tables($table_count)
{
	create_data_tables();
	create_info_tables();
}

function create_data_tables()
{
	global $markets;
	
	// get rid of old data tables and create new ones
	foreach ($markets as $market)
	{
		echo "$market: creating data tables\n";
		db::use_db($market.'_data');
		
		for ($i = 0; $i < util::DATA_TABLE_COUNT; ++$i)
		{
/*
			db::delete("delete from clients_$i");
			db::delete("delete from campaigns_$i");
			db::delete("delete from ad_groups_$i");
			db::delete("delete from ads_$i");
			db::delete("delete from keywords_$i");
*/
			create_clients_data_table($i);
			create_campaigns_data_table($i);
			create_ad_groups_data_table($i);
			create_ads_data_table($i);
			create_keywords_data_table($i);
		}
		// QL
		create_clients_data_table('ql');
		create_campaigns_data_table('ql');
		create_ad_groups_data_table('ql');
		create_ads_data_table('ql');
		create_keywords_data_table('ql');
	}
}

function create_info_tables()
{
	global $markets;
	
	// get rid of old data tables and create new ones
	foreach ($markets as $market)
	{
		echo "$market: creating info tables\n";
		db::use_db($market.'_info');
		
		create_campaign_info_table('unassigned');
		create_ad_group_info_table('unassigned');
		create_ad_info_table('unassigned');
		create_keyword_info_table('unassigned');
		
		for ($i = 0; $i < util::DATA_TABLE_COUNT; ++$i)
		{
			create_campaign_info_table($i);
			create_ad_group_info_table($i);
			create_ad_info_table($i);
			create_keyword_info_table($i);
		}
		// QL
		create_campaign_info_table('ql');
		create_ad_group_info_table('ql');
		create_ad_info_table('ql');
		create_keyword_info_table('ql');
	}
}

function populate_data_tables($table_count)
{
	global $markets, $start_date, $end_date;
	
/*
	// clear data table for clients
	db::update("update eppctwo.clients set data_id=-1");
	
	// init tables with 1 random client each
	$clients = db::select("
		select id, name
		from eppctwo.clients
		where status='On'
	");
	shuffle($clients);
	
	for ($i = 0; $i < $table_count; ++$i)
	{
		// get a random client
		$rand_index = mt_rand(0, count($clients) - 1);
		list($cl_info) = array_splice($clients, $rand_index, 1);
		list($cl_id, $cl_name) = $cl_info;
		
		echo 'init data table: '.$cl_id.','.$cl_name.','.$i."\n";
		
		// set their data
		db::update("update eppctwo.clients set data_id=$i where id=$cl_id");
		foreach ($markets as $market)
			refresh_client_data($cl_id, $market, $start_date, $end_date);
	}
	return;
*/

	$clients = db::select("
		select id, name
		from eppctwo.clients
		where status='On' && data_id=-1
	");
	shuffle($clients);
	
	// make sure we have at least default values in data_speeds table
	for ($i = 0; $i < util::DATA_TABLE_COUNT; ++$i)
		db::exec("insert into eppctwo.data_speeds values ($i, 0)");
	
	for ($i = 0; list($cl_id, $cl_name) = $clients[$i]; ++$i)
	{
		$data_id = db::select_one("select id from data_speeds order by speed asc limit 1");
		echo ($i+1).'/'.count($clients).": $cl_id, $cl_name, $data_id\n";
		db::exec("update clients set data_id=$data_id where id='$cl_id'");
		foreach ($markets as $market)
		{
			echo "market: $market\n";
			refresh_client_data($cl_id, $market, $start_date, $end_date, util::REFRESH_CLIENT_DATA_PROGRESS_MONTHLY);
		}
		data_speed_update($data_id);
	}
	return;
	
	// init data speeds for each table
	$data_speeds = array();
	for ($i = 0; $i < $table_count; ++$i)
		$data_speeds[$i] = data_speed_test($i, $start_date, $end_date);
	
	// loop over the rest of the clients and insert into fastest table
	for ($i = 0; count($clients) > 0; ++$i)
	{
		// get a random client
		$rand_index = mt_rand(0, count($clients) - 1);
		list($cl_info) = array_splice($clients, $rand_index, 1);
		list($cl_id, $cl_name) = $cl_info;
		
		// find the fastest 
		for ($j = 0, $min_speed = 65536, $data_id = 0; $j < $table_count; ++$j)
		{
			$val = $data_speeds[$j];
			// 2nd part: randomly break ties
			if ($val < $min_speed || ($val == $min_speed && mt_rand(0, 1)))
			{
				$min_speed = $val;
				$data_id = $j;
			}
		}
		
		echo 'set data: '.$cl_id.','.$cl_name.','.$min_speed.','.$data_id."\n";
		echo implode(',', $data_speeds)."\n";
		// set the client's data id and their data
		db::exec("update eppctwo.clients set data_id=$data_id where id=$cl_id");
		foreach ($markets as $market)
			refresh_client_data($cl_id, $market, $start_date, $end_date);
		
		// rerun speed test for the index
		$data_speeds[$data_id] = data_speed_test($data_id, $start_date, $end_date);
		echo "speed after: ".$data_speeds[$data_id]."\n";
	}
}

function populate_info_tables($table_count)
{
	global $markets;
	
	$cl_data_ids = db::select("
		select id, data_id
		from clients
	", 'NUM', 0);
	
	// initialize info from files
	foreach ($markets as $market)
	{
		set_active_data_sources($data_sources, $market);
		
		// campaigns
		for ($fi = new file_iterator('sync_files/'.$market.'_ca_info.dat'); $fi->next($block); )
		{
			$lines = explode("\n", $block);
			echo "$market, campaigns, ".count($lines)."\n";
			foreach ($lines as $line)
			{
				list($tmp_cl_id, $ac_id, $ca_id, $mod_date, $text) = explode("\t", $line);
				$cl_id = get_client_from_data_ids($data_sources, $ac_id, $ca_id);
				$data_id = $cl_data_ids[$cl_id];
				$info_table = (!empty($cl_id) && !empty($data_id) && $data_id != -1) ? "campaigns_{$data_id}" : "unassigned_campaigns";
				db::exec("
					insert into {$market}_info.{$info_table}
						(client, account, campaign, mod_date, text, status)
					values
						(
							'$cl_id',
							'$ac_id',
							'$ca_id',
							'$mod_date',
							'".addslashes($text)."',
							'On'
						)
				");
			}
		}
		// ad groups
		for ($fi = new file_iterator('sync_files/'.$market.'_ag_info.dat'); $fi->next($block); )
		{
			$lines = explode("\n", $block);
			echo "$market, ad groups, ".count($lines)."\n";
			foreach ($lines as $line)
			{
				list($tmp_cl_id, $ac_id, $ca_id, $ag_id, $mod_date, $text) = explode("\t", $line);
				$cl_id = get_client_from_data_ids($data_sources, $ac_id, $ca_id, $ag_id);
				$data_id = $cl_data_ids[$cl_id];
				$info_table = (!empty($cl_id) && !empty($data_id) && $data_id != -1) ? "ad_groups_{$data_id}" : "unassigned_ad_groups";
				db::exec("
					insert into {$market}_info.{$info_table}
						(client, account, campaign, ad_group, mod_date, text, status)
					values
						(
							'$cl_id',
							'$ac_id',
							'$ca_id',
							'$ag_id',
							'$mod_date',
							'".addslashes($text)."',
							'On'
						)
				");
			}
		}
		// keywords
		for ($fi = new file_iterator('sync_files/'.$market.'_kw_info.dat'); $fi->next($block); )
		{
			$lines = explode("\n", $block);
			echo "$market, keywords, ".count($lines)."\n";
			foreach ($lines as $line)
			{
				list($tmp_cl_id, $ac_id, $ca_id, $ag_id, $kw_id, $mod_date, $text, $kw_type, $kw_max_cpc) = explode("\t", $line);
				$cl_id = get_client_from_data_ids($data_sources, $ac_id, $ca_id, $ag_id);
				$data_id = $cl_data_ids[$cl_id];
				$info_table = (!empty($cl_id) && !empty($data_id) && $data_id != -1) ? "keywords_{$data_id}" : "unassigned_keywords";
				db::exec("
					insert into {$market}_info.keywords_{$data_id}
						(client, account, campaign, ad_group, keyword, mod_date, text, type, max_cpc, status)
					values
						(
							'$cl_id',
							'$ac_id',
							'$ca_id',
							'$ag_id',
							'$kw_id',
							'$mod_date',
							'".addslashes($text)."',
							'$kw_type',
							'$kw_max_cpc',
							'On'
						)
				");
			}
		}
	}
	// run info speed tests
	
	// put in info for inactive clients
}

/*
 * 
 * data tables
 * 
 */

function create_clients_data_table($i)
{
	db::exec("CREATE TABLE `clients_$i` (
		`client` varchar(32) NOT NULL default '',
		`data_date` date NOT NULL default '0000-00-00',
		`imps` int(10) unsigned NOT NULL default '0',
		`clicks` mediumint(8) unsigned NOT NULL default '0',
		`convs` smallint(5) unsigned NOT NULL default '0',
		`cost` double unsigned NOT NULL default '0',
		`pos_sum` int(10) unsigned NOT NULL default '0',
		`revenue` double not null default 0,
		PRIMARY KEY  (`client`,`data_date`),
		KEY `cl_index` (`client`)
		) ENGINE=MyISAM
	");
}

function create_campaigns_data_table($i)
{
	db::exec("CREATE TABLE `campaigns_$i` (
		`client` varchar(32) NOT NULL default '',
		`account` varchar(32) NOT NULL default '',
		`campaign` varchar(32) NOT NULL default '',
		`data_date` date NOT NULL default '0000-00-00',
		`imps` int(10) unsigned NOT NULL default '0',
		`clicks` mediumint(8) unsigned NOT NULL default '0',
		`convs` smallint(5) unsigned NOT NULL default '0',
		`cost` double unsigned NOT NULL default '0',
		`pos_sum` int(10) unsigned NOT NULL default '0',
		`revenue` double not null default 0,
		PRIMARY KEY  (`campaign`,`data_date`),
		KEY `cl_index` (`client`)
		) ENGINE=MyISAM
	");
}

function create_ad_groups_data_table($i)
{
	db::exec("CREATE TABLE `ad_groups_$i` (
		`client` varchar(32) NOT NULL default '',
		`account` varchar(32) NOT NULL default '',
		`campaign` varchar(32) NOT NULL default '',
		`ad_group` varchar(32) NOT NULL default '',
		`data_date` date NOT NULL default '0000-00-00',
		`imps` int(10) unsigned NOT NULL default '0',
		`clicks` mediumint(8) unsigned NOT NULL default '0',
		`convs` smallint(5) unsigned NOT NULL default '0',
		`cost` double unsigned NOT NULL default '0',
		`pos_sum` int(10) unsigned NOT NULL default '0',
		`revenue` double not null default 0,
		PRIMARY KEY  (`ad_group`,`data_date`),
		KEY `cl_index` (`client`)
		) ENGINE=MyISAM
	");
}

function create_ads_data_table($i)
{
	db::exec("CREATE TABLE `ads_$i` (
		`client` varchar(32) NOT NULL default '',
		`account` varchar(32) NOT NULL default '',
		`campaign` varchar(32) NOT NULL default '',
		`ad_group` varchar(32) NOT NULL default '',
		`ad` varchar(32) NOT NULL default '',
		`data_date` date NOT NULL default '0000-00-00',
		`imps` int(10) unsigned NOT NULL default '0',
		`clicks` mediumint(8) unsigned NOT NULL default '0',
		`convs` smallint(5) unsigned NOT NULL default '0',
		`cost` double unsigned NOT NULL default '0',
		`pos_sum` int(10) unsigned NOT NULL default '0',
		`revenue` double not null default 0,
		PRIMARY KEY  (`ad_group`,`ad`,`data_date`),
		KEY `cl_index` (`client`)
		) ENGINE=MyISAM
	");
}

function create_keywords_data_table($i)
{
	db::exec("CREATE TABLE `keywords_$i` (
		`client` varchar(32) NOT NULL default '',
		`account` varchar(32) NOT NULL default '',
		`campaign` varchar(32) NOT NULL default '',
		`ad_group` varchar(32) NOT NULL default '',
		`keyword` varchar(32) NOT NULL default '',
		`data_date` date NOT NULL default '0000-00-00',
		`imps` int(10) unsigned NOT NULL default '0',
		`clicks` mediumint(8) unsigned NOT NULL default '0',
		`convs` smallint(5) unsigned NOT NULL default '0',
		`cost` double unsigned NOT NULL default '0',
		`pos_sum` int(10) unsigned NOT NULL default '0',
		`revenue` double not null default 0,
		PRIMARY KEY  (`ad_group`,`keyword`,`data_date`),
		KEY `cl_index` (`client`)
		) ENGINE=MyISAM
	");
}

/*
 * 
 * info tables
 * 
 */

function create_campaign_info_table($i)
{
	$table_name = ($i === 'unassigned') ? $i.'_campaigns' : 'campaigns_'.$i;
	db::exec("
		CREATE TABLE `$table_name` (
			`client` varchar(32) NOT NULL default '0',
			`account` varchar(32) NOT NULL default '',
			`campaign` varchar(32) NOT NULL default '0',
			`mod_date` date NOT NULL default '0000-00-00',
			`ag_info_mod_date` date NOT NULL default '0000-00-00',
			`text` varchar(128) NOT NULL default '',
			`status` varchar(16) NOT NULL,
			PRIMARY KEY (`campaign`),
			KEY `cl_index` (`client`),
			KEY `ac_index` (`account`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8
	");
}

function create_ad_group_info_table($i)
{
	$table_name = ($i === 'unassigned') ? $i.'_ad_groups' : 'ad_groups_'.$i;
	db::exec("
		CREATE TABLE `$table_name` (
			`client` varchar(32) NOT NULL default '0',
			`account` varchar(32) NOT NULL default '',
			`campaign` varchar(32) NOT NULL default '',
			`ad_group` varchar(32) NOT NULL default '0',
			`mod_date` date NOT NULL default '0000-00-00',
			`kw_info_mod_date` date NOT NULL default '0000-00-00',
			`text` varchar(128) NOT NULL default '',
			`max_cpc` double NOT NULL default '0',
			`max_content_cpc` double NOT NULL default '0',
			`status` varchar(16) NOT NULL default '',
			PRIMARY KEY (`ad_group`),
			KEY `cl_index` (`client`),
			KEY `ac_index` (`account`),
			KEY `ca_index` (`campaign`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8
	");
}

function create_ad_info_table($i)
{
	$table_name = ($i === 'unassigned') ? $i.'_ads' : 'ads_'.$i;
	db::exec("
		CREATE TABLE `$table_name` (
			`client` varchar(32) NOT NULL default '0',
			`account` varchar(32) NOT NULL default '',
			`campaign` varchar(32) NOT NULL default '',
			`ad_group` varchar(32) NOT NULL default '',
			`ad` varchar(32) NOT NULL default '0',
			`mod_date` date NOT NULL default '0000-00-00',
			`text` varchar(128) NOT NULL default '',
			`desc_1` varchar(255) NOT NULL default '',
			`desc_2` varchar(64) NOT NULL default '',
			`disp_url` varchar(255) NOT NULL default '',
			`dest_url` varchar(1024) NOT NULL default '',
			`status` varchar(16) NOT NULL default '',
			PRIMARY KEY (`ad`, `ad_group`),
			KEY `cl_index` (`client`),
			KEY `ac_index` (`account`),
			KEY `ca_index` (`campaign`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8
	");
}

function create_keyword_info_table($i)
{
	$table_name = ($i === 'unassigned') ? $i.'_keywords' : 'keywords_'.$i;
	db::exec("
		CREATE TABLE `$table_name` (
			`client` varchar(32) NOT NULL default '0',
			`account` varchar(32) NOT NULL default '',
			`campaign` varchar(32) NOT NULL default '',
			`ad_group` varchar(32) NOT NULL default '',
			`keyword` varchar(32) NOT NULL default '0',
			`mod_date` date NOT NULL default '0000-00-00',
			`text` varchar(128) NOT NULL default '',
			`type` varchar(8) NOT NULL default '',
			`max_cpc` double NOT NULL default '0',
			`dest_url` varchar(1024) NOT NULL default '',
			`status` varchar(16) NOT NULL default '',
			`market_info` varchar(500) NOT NULL default '',
			PRIMARY KEY (`keyword`, `ad_group`),
			KEY `cl_index` (`client`),
			KEY `ac_index` (`account`),
			KEY `ca_index` (`campaign`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8
	");
}

?>