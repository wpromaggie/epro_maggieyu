<?php
require_once('cli.php');

class assign_orphaned_market_info
{
	public static function go()
	{
		global $cl_args;
		
		$data_id = (array_key_exists('d', $cl_args)) ? $cl_args['d'] : null;
		$markets = util::get_ppc_markets();
		foreach ($markets as $market)
		{
			$data_sources = null;
			util::set_active_data_sources($data_sources, $market);
			
			self::save_orphans($market, $data_id, $data_sources['acs'], 'account' , array('keywords', 'ads', 'ad_groups', 'campaigns'));
			self::save_orphans($market, $data_id, $data_sources['cas'], 'campaign', array('keywords', 'ads', 'ad_groups', 'campaigns'));
			self::save_orphans($market, $data_id, $data_sources['ags'], 'ad_group', array('keywords', 'ads', 'ad_groups'));
		}
	}
	
	private static function save_orphans($market, $target_data_id, &$entities, $entity_type, $tables)
	{
		if (empty($entities))
		{
			return;
		}
		
		$i = 0;
		$ci = count($entities);
		foreach ($entities as $entity_id => $cl_id)
		{
			if (($i++ % 10) == 0) echo "$market, $entity_type: $i / $ci\n";
			$data_id = db::select_one("select data_id from eppctwo.clients where id = '$cl_id'");
			if ($data_id == -1)
			{
				continue;
			}
			if (!is_null($target_data_id) && $target_data_id != $data_id)
			{
				continue;
			}
			foreach ($tables as $table_base)
			{
				$are_orphans = db::select_one("
					select count(*)
					from {$market}_info.unassigned_{$table_base}
					where {$entity_type} = '$entity_id'
				");
				if ($are_orphans)
				{
					db::exec("
						insert into {$market}_info.{$table_base}_{$data_id}
						select * from {$market}_info.unassigned_{$table_base} where {$entity_type} = '$entity_id'
					");
					
					// assign client to info
					db::exec("
						update {$market}_info.{$table_base}_{$data_id}
						set client = '$cl_id'
						where {$entity_type} = '$entity_id'
					");
					
					// assign client to data
					db::exec("
						update {$market}_data.{$table_base}_{$data_id}
						set client = '$cl_id'
						where {$entity_type} = '$entity_id'
					");
					
					// delete from unassigned table
					db::exec("
						delete from {$market}_info.unassigned_{$table_base}
						where {$entity_type} = '$entity_id'
					");
				}
			}
		}
	}
}

?>