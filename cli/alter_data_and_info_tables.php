<?php
require('cli.php');

define('BATCH_SIZE', 65536);

cli::run();

class alter_data_and_info_tables
{
	
	public static function add_mpc_vt_data_cols()
	{
		$markets = array('y');
		
		foreach ($markets as $market)
		{
			db::use_db($market.'_data');
			$tables = db::select("show tables");
			
			// tracking stuff
			foreach ($tables as $table)
			{
				if (!db::col_exists($table, 'mpc_convs'))
				{
					if (1 || strpos($table, 'client') === 0 || strpos($table, 'campaign') === 0)
					{
						echo "$market, $table\n";
						db::exec("
							alter table {$table}
							add mpc_convs smallint unsigned not null default 0,
							add vt_convs smallint unsigned not null default 0
						");
					}
				}
/*
				if (db::col_exists($table, 'mpc_convs') && db::col_exists($table, 'vt_convs'))
				{
					if (1 || strpos($table, 'client') === 0 || strpos($table, 'campaign') === 0)
					{
						echo "$market, $table\n";
						db::exec("
							alter table {$table}
							drop mpc_convs,
							drop vt_convs
						");
					}
				}
				if ($table == 'ql_client_update_engine') exit;
*/
			}
		}
	}
	
	public static function fb_ads()
	{
		$markets = array('f');
		
		db::dbg();
		foreach ($markets as $market)
		{
			db::use_db($market.'_info');
			$tables = db::select("show tables like 'ads\_%'");
			
			e($tables);
			continue;
			// tracking stuff
			foreach ($tables as $table)
			{
				if (!db::col_exists($table, 'cities'))
				{
				}
/*
				if (db::col_exists($table, 'mpc_convs') && db::col_exists($table, 'vt_convs'))
				{
					if (1 || strpos($table, 'client') === 0 || strpos($table, 'campaign') === 0)
					{
						echo "$market, $table\n";
						db::exec("
							alter table {$table}
							drop mpc_convs,
							drop vt_convs
						");
					}
				}
				if ($table == 'ql_client_update_engine') exit;
*/
			}
		}
	}
	
	public static function at()
	{
/*
		$alter_info = array(
			// deleted columns, simple 1d array
			'drop' => array('ii'),
			
			// old name => new name and definition
			'change' => array(
				'thing' => 'trong INT( 11 ) NOT NULL'
			),
			
			// new column definitions
			'add' => array(
				'aiai varchar(20) default \'ye\''
			)
		);
*/
		
		$alter_info = array(
			// deleted columns, simple 1d array
			//'drop' => array('ii'),
			
			// old name => new name and definition
			//'change' => array(
			//	'thing' => 'trong INT( 11 ) NOT NULL'
			//),
			
			// new column definitions
			'add' => array(
				'mpc_convs smallint unsigned not null default 0',
				'vt_convs smallint unsigned not null default 0'
			)
		);
		$ta = new table_alterer('g_data', 'keywords_11', $alter_info);
		$ta->go();
	}
	
	public static function is_wpropathed()
	{
		$markets = array('g', 'm');
		foreach ($markets as $market)
		{
			db::use_db($market.'_info');
			$tables = db::select("show tables");
			
			// tracking stuff
			foreach ($tables as $table)
			{
				if (strpos($table, 'campaigns') !== false || strpos($table, 'ad_groups') !== false)
				{
					db::exec("alter table $table add is_wpropathed tinyint not null default 0 after mod_date");
					#db::exec("alter table $table drop is_wpropathed");
				}
			}
		}
	}
	
}

class table_alterer
{
	public function __construct($db, $table, $alter_info)
	{
		$this->db = $db;
		$this->table = $table;
		$this->alter_info = $alter_info;
		
		$this->renamed_cols = null;
	}
	
	public function go()
	{
		db::use_db($this->db);
		$this->cols = db::get_cols($this->table);
		
		$this->sanity_check();
		$this->set_before_alter_select_query();
		$this->create_temp_table();
		
		$this->drop_cols();
		$this->change_cols();
		$this->add_cols();
		
		$this->transfer_data();
		$this->table_swap();
	}
	
	private function is_drop()   { return (array_key_exists('drop'  , $this->alter_info) && is_array($this->alter_info['drop'])); }
	private function is_change() { return (array_key_exists('change', $this->alter_info) && is_array($this->alter_info['change'])); }
	private function is_add()    { return (array_key_exists('add'   , $this->alter_info) && is_array($this->alter_info['add'])); }
	
	public function sanity_check()
	{
		$sanity_cols = array_flip(array_values($this->cols));
		
		// make sure columns to drop exist
		if ($this->is_drop())
		{
			foreach ($this->alter_info['drop'] as $col)
			{
				if (!array_key_exists($col, $sanity_cols))
				{
					echo "Error: cannot drop '$col' - '$col' not in table {$this->table}\n";
					exit(1);
				}
				unset($sanity_cols[$col]);
			}
		}
		
		// make sure columns to change exist
		// and if they have a new name that it does *not* exist
		if ($this->is_change())
		{
			foreach ($this->alter_info['change'] as $before_col => $after_definition)
			{
				if (!array_key_exists($before_col, $sanity_cols))
				{
					echo "Error: cannot change '$before_col' - '$before_col' not in table {$this->table}\n";
					exit(1);
				}
				
				preg_match("/^(.+?)\s/", $after_definition, $matches);
				$after_col = $matches[1];
				
				if ($after_col != $before_col && array_key_exists($after_col, $sanity_cols))
				{
					echo "Error: cannot rename '$before_col' to '$after_col' - '$after_col' already in table {$this->table}\n";
					exit(1);
				}
				
				$sanity_cols[$after_col] = 1;
				unset($sanity_cols[$before_col]);
			}
		}
		
		// make sure columns to add do *not* exist
		if ($this->is_add())
		{
			foreach ($this->alter_info['add'] as $col_definition)
			{
				preg_match("/^(.+?)\s/", $col_definition, $matches);
				$col = $matches[1];
				
				if (array_key_exists($col, $sanity_cols))
				{
					echo "Error: cannot add '$col' - '$col' already in table {$this->table}\n";
					exit(1);
				}
				$sanity_cols[$col] = 1;
			}
		}
	}
	
	private function set_before_alter_select_query()
	{
		$before_alter_select_cols = array();
		foreach ($this->cols as $col)
		{
			if (!$this->is_drop() || !in_array($col, $this->alter_info['drop']))
			{
				$before_alter_select_cols[] = $col;
			}
		}
		$this->before_alter_select_query = implode(',', $before_alter_select_cols);
	}
	
	private function create_temp_table()
	{
		for ($this->temp_table = $this->table.'_tmp_'; db::table_exists($this->temp_table); $this->temp_table .= chr(mt_rand(97, 122)));
		
		// copy table structure to new temp table
		db::exec("create table {$this->temp_table} like {$this->table}");
	}
	
	private function drop_cols()
	{
		// dropped cols
		if ($this->is_drop())
		{
			foreach ($this->alter_info['drop'] as $col)
			{
				db::exec("alter table {$this->temp_table} drop `$col`");
			}
		}
	}
	
	private function change_cols()
	{
		$this->renamed_cols = array();
		if ($this->is_change())
		{
			foreach ($this->alter_info['change'] as $before_col => $after_definition)
			{
				preg_match("/^(.+?)\s/", $after_definition, $matches);
				$after_col = $matches[1];
				if ($after_col != $before_col)
				{
					$this->renamed_cols[$before_col] = $after_col;
				}
				db::exec("alter table {$this->temp_table} change `$before_col` $after_definition");
			}
		}
	}
	
	private function add_cols()
	{
		// new cols
		if ($this->is_add())
		{
			foreach ($this->alter_info['add'] as $col_definition)
			{
				db::exec("alter table {$this->temp_table} add $col_definition");
			}
		}
	}
	
	private function transfer_data()
	{
		// loop over data and insert from old table to temp table
		$num_rows = db::select_one("select count(*) from {$this->table}");
		
		for ($i = 0; $i < $num_rows; $i += BATCH_SIZE)
		{
			echo "$i / $num_rows (select {$this->before_alter_select_query} from {$this->table})\n";
			$r = mysql_query("select {$this->before_alter_select_query} from {$this->table} limit $i, ".BATCH_SIZE);
			while ($d = mysql_fetch_assoc($r))
			{
				if ($this->renamed_cols)
				{
					foreach ($this->renamed_cols as $before_key => $after_key)
					{
						$d[$after_key] = $d[$before_key];
						unset($d[$before_key]);
					}
				}
				db::insert($this->temp_table, $d);
			}
		}
	}
	
	private function table_swap()
	{
		// drop old table
		db::exec("drop table {$this->table}");
		
		// rename tmp table to table name
		db::exec("rename table {$this->temp_table} to {$this->table}");
	}
}

/*
	public static function old_stuff()
	{
		// add ad info_mod_date to ad group info tables
		foreach ($tables as $table)
		{
			if (strpos($table, 'ad_group') !== false)
			{
				echo "$market, $table -> alter table $table add `ad_info_mod_time` datetime NOT NULL default '0000-00-00 00:00:00' after kw_info_mod_time\n";
				mysql_query("alter table $table add `ad_info_mod_time` datetime NOT NULL default '0000-00-00 00:00:00' after kw_info_mod_time");
				#mysql_query("alter table $table change `ad_info_mod_date` `ad_info_mod_time` datetime NOT NULL default '0000-00-00 00:00:00'");
			}
		}
		
		// add info_mod_date to ad group info tables
		foreach ($tables as $table)
			{
				if (strpos($table, 'ad_group') !== false)
				{
					echo "$market, $table\n";
					mysql_query("alter table $table change `kw_info_mod_date` kw_info_mod_time datetime NOT NULL default '0000-00-00 00:00:00'");
				}
				if (strpos($table, 'campaign') !== false)
				{
					echo "$market, $table\n";
					mysql_query("alter table $table change `ag_info_mod_date` ag_info_mod_time datetime NOT NULL default '0000-00-00 00:00:00'");
				}
			}
			
		// add market_info to keyword info tables
		db::use_db($market.'_info');
		$tables = db::select("show tables");
		foreach ($tables as $table)
			{
				if (strpos($table, 'keywords') === false) continue;
				echo "$market, $table\n";
				mysql_query("alter table $table add `market_info` varchar(500) NOT NULL default ''");
			}
		// add revenue col to data tables
		$tables = db::select("show tables");
		foreach ($tables as $table)
			{
				echo "$market, $table\n";
				mysql_query("alter table $table add revenue double not null default 0");
			}
		// add revenue to raw data tables
		db::use_db($market.'_data_tmp');
		$tables = db::select("show tables");
		foreach ($tables as $table)
			{
				echo "$market, $table\n";
				mysql_query("alter table $table add revenue double not null default 0");
			}
			//change name of id col to table type
			db::use_db($market.'_info');
		$tables = db::select("show tables");
		
		foreach ($tables as $table)
			{
				preg_match("/^(.*)s_\d+/", $table, $matches);
				$info_type = $matches[1];
				echo "$market,$table,$info_type\n";
				db::exec("alter table $table change id $info_type varchar(32) not null default ''");
			}
			
			
			
		// add "unassigned" info tables
		$info_table_types = array('campaigns', 'ad_groups', 'ads', 'keywords');
		db::use_db($market.'_info');
		foreach ($info_table_types as $info_table_type)
		{
			#db::exec("create table unassigned_{$info_table_type} as select * from {$info_table_type}_0 where 1=2");
			list($table, $create_query) = db::select_row("show create table {$info_table_type}_0");
			echo "$create_query\n";
			// replace sample table name with unassigned name
			$create_query = str_replace("{$info_table_type}_0", "unassigned_{$info_table_type}", $create_query);
			echo "$create_query\n---\n";


			// as the info is unassigned, theree is no client, get rid of it
			// turns out this makes inserting data more complicated, just keep the same form as the other tables
			
			//$lines = explode("\n", $create_query);
			//$create_query = '';
			//foreach ($lines as $line)
			//{
				//if (strpos($line, 'client') === false) $create_query .= "$line\n";
			//}
			//echo "$create_query\n---\n";

			
			mysql_query("drop table unassigned_{$info_table_type}");
			mysql_query($create_query);
		}
	}
*/
?>