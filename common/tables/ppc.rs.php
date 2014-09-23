<?php

class clients_ppc extends rs_object
{
	public static $db, $cols, $primary_key, $has_one;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$cols = self::init_cols(
			new rs_col('company'            ,'int'    ,11  ,null  ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('client'             ,'varchar',32  ,''    ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('ncid'               ,'char'   ,16  ,''    ,rs::READ_ONLY),
			new rs_col('naid'               ,'char'   ,16  ,''    ,rs::READ_ONLY),
			new rs_col('manager'            ,'varchar',64  ,''    ,rs::NOT_NULL),
			new rs_col('status'             ,'enum'   ,null,'On'  ,rs::NOT_NULL ,array('On', 'Cancelled', 'Incomplete', 'Off')),
			new rs_col('billing_contact_id' ,'bigint' ,20  ,null  ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('url'                ,'varchar',128 ,''    ,rs::NOT_NULL),
			new rs_col('start_date'         ,'date'   ,null,rs::DD,rs::NOT_NULL),
			new rs_col('bill_day'           ,'tinyint',null,0     ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('next_bill_date'     ,'date'   ,null,rs::DD,rs::NOT_NULL),
			new rs_col('prev_bill_date'     ,'date'   ,null,rs::DD,rs::NOT_NULL),
			new rs_col('notes'              ,'text'   ,null,null  ,0           ),
			new rs_col('revenue_tracking'   ,'bool'   ,null,false ,rs::NOT_NULL),
			new rs_col('facebook'           ,'bool'   ,null,false ,rs::NOT_NULL),
			new rs_col('google_mpc_tracking','bool'   ,null,false ,rs::NOT_NULL),
			new rs_col('conversion_types'   ,'bool'   ,null,false ,rs::NOT_NULL),
			new rs_col('who_pays_clicks'    ,'enum'   ,null,null  ,0            ,array('Wpromote','Client')),
			new rs_col('budget'             ,'double' ,null,0     ,rs::NOT_NULL),
			new rs_col('carryover'          ,'double' ,null,0     ,rs::NOT_NULL),
			new rs_col('adjustment'         ,'double' ,null,0     ,rs::NOT_NULL),
			new rs_col('actual_budget'      ,'double' ,null,0     ,rs::NOT_NULL),
			new rs_col('mo_spend'           ,'double' ,null,0     ,rs::NOT_NULL),
			new rs_col('yd_spend'           ,'double' ,null,0     ,rs::NOT_NULL),
			new rs_col('days_to_date'       ,'tinyint',null,0     ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('days_remaining'     ,'tinyint',null,0     ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('days_in_month'      ,'tinyint',null,0     ,rs::UNSIGNED | rs::NOT_NULL)
		);
		self::$primary_key = array('client');
		self::$has_one = array('clients');
	}
	
	public function calc_actual_budget()
	{
		$this->actual_budget = $this->budget + $this->carryover + $this->adjustment;
	}
	
	public function update_actual_budget()
	{
		$this->calc_actual_budget();
		$this->put(array('cols' => array('actual_budget')));
	}
	
	public static function manager_form_input($table, $col, $val)
	{
		$options = db::select("
			select u.username u0, u.realname u1
			from users u, user_guilds ug
			where
				ug.guild_id = 'ppc' &&
				u.id = ug.user_id
			order by u1 asc
		");
		return cgi::html_select($table.'_'.$col->name, $options, $val);
	}
	
	public static function bill_day_form_input($table, $col, $val)
	{
		return cgi::html_select($table.'_'.$col->name, range(0, 31), $val);
	}
}

class track_browse_keyword_timestamp extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$cols = self::init_cols(
			new rs_col('ad_group_id', 'varchar' , 32  , ''                   , rs::NOT_NULL),
			new rs_col('t'          , 'datetime', null, '0000-00-00 00:00:00', rs::NOT_NULL)
		);
		self::$primary_key = array('ad_group_id');
	}
}

class track_browse_keyword extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$cols = self::init_cols(
			new rs_col('ad_group_id', 'varchar', 32 , '', rs::NOT_NULL),
			new rs_col('keyword_id' , 'varchar', 32 , '', rs::NOT_NULL),
			new rs_col('dest_url'   , 'varchar', 500, '', rs::NOT_NULL)
		);
		self::$primary_key = array('ad_group_id', 'keyword_id');
	}
}

class ppc_data_source_refresh extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'          ,'int'    ,null,null  ,rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT),
			new rs_col('account_id'  ,'char'   ,32  ,''    ,rs::NOT_NULL),
			new rs_col('refresh_type','enum'   ,null,''    ,rs::NOT_NULL, array('', 'local', 'remote', 'convs')),
			new rs_col('do_force'    ,'bool'   ,null,0     ,rs::NOT_NULL),
			new rs_col('market'      ,'varchar',4   ,''    ,rs::NOT_NULL),
			new rs_col('start_date'  ,'date'   ,null,rs::DD,rs::NOT_NULL),
			new rs_col('end_date'    ,'date'   ,null,rs::DD,rs::NOT_NULL)
		);
	}
}

class ppc_schedule_refresh extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('client_id')
		);
		self::$cols = self::init_cols(
			new rs_col('id'          ,'int'    ,null,null    ,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('client_id'   ,'bigint' ,null,0       ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('user_id'     ,'int'    ,null,0       ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('frequency'   ,'enum'   ,null,'Weekly',rs::NOT_NULL, array('Weekly','Monthly')),
			new rs_col('day_of_week' ,'enum'   ,null,null    ,0           , array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')),
			new rs_col('day_of_month','tinyint',null,null    ,rs::UNSIGNED),
			new rs_col('time'        ,'time'   ,null,rs::DT  ,rs::NOT_NULL),
			new rs_col('num_days'    ,'tinyint',null,null    ,rs::NOT_NULL)
		);
	}
	
	public static function day_of_month_form_input($table, $col, $val)
	{
		return cgi::html_select($table.'_'.$col->name, range(1, 31), $val);
	}
	
	public static function num_days_form_input($table, $col, $val)
	{
		return cgi::html_select($table.'_'.$col->name, range(1, 60), $val);
	}
	
	public function get_frequency_details_string()
	{
		switch ($this->frequency)
		{
			case ('Weekly'): return $this->day_of_week;
			case ('Monthly'): return 'The '.util::ordinal($this->day_of_month);
		}
	}
	
	public function put($opts = array())
	{
		if ($this->frequency == 'Weekly')
		{
			unset($this->day_of_month);
		}
		if ($this->frequency == 'Monthly')
		{
			unset($this->day_of_week);
		}
		return parent::put($opts);
	}
}

class ppc_rollover extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('account_id')
		);
		self::$cols = self::init_cols(
			new rs_col('id'            ,'int'   ,null,null  ,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('account_id'    ,'char'  ,16  ,''    ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('user_id'       ,'int'   ,null,0     ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('d'             ,'date'  ,null,rs::DD,rs::NOT_NULL),
			new rs_col('budget'        ,'double',null,0     ,rs::NOT_NULL),
			new rs_col('carryover'     ,'double',null,0     ,rs::NOT_NULL),
			new rs_col('adjustment'    ,'double',null,0     ,rs::NOT_NULL),
			new rs_col('next_bill_date','date'  ,null,rs::DD,rs::NOT_NULL)
		);
	}
}

class data_sources extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$cols = self::init_cols(
			new rs_col('account_id','char'   ,16,'',rs::NOT_NULL),
			new rs_col('market'    ,'varchar',4 ,'',rs::NOT_NULL),
			new rs_col('account'   ,'varchar',32,'',rs::NOT_NULL),
			new rs_col('campaign'  ,'varchar',32,'',rs::NOT_NULL),
			new rs_col('ad_group'  ,'varchar',32,'',rs::NOT_NULL)
		);
		self::$primary_key = array('market','account','campaign','ad_group');
	}
}

class reports extends rs_object
{
	public static $db, $cols, $primary_key, $uniques;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$uniques = array(
			'user' => array('user', 'account_id', 'name')
		);
		self::$cols = self::init_cols(
			new rs_col('id'         ,'char'    ,32  ,null   ,rs::NOT_NULL),
			new rs_col('user'       ,'int'     ,11  ,0      ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('account_id' ,'char'    ,16  ,''     ,rs_col_NOT_NULL),
			new rs_col('name'       ,'varchar' ,64  ,''     ,rs::NOT_NULL),
			new rs_col('is_template','tinyint' ,1   ,0      ,rs::NOT_NULL),
			new rs_col('create_date','datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('last_run'   ,'datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('sheets'     ,'text'    ,null,null   ,rs::NOT_NULL)
		);
	}
	
	protected function uprimary_key(){
		return util::mt_rand_uuid();
	}
}

class ppc_report_sheet extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('report_id')
		);
		self::$cols = self::init_cols(
			new rs_col('report_id','char'  	 ,36  ,null   ,rs::NOT_NULL),
			new rs_col('id'       ,'char'    ,6   ,''     ,rs::READ_ONLY),
			new rs_col('name'     ,'char'    ,100 ,''     ,rs::READ_ONLY),
			new rs_col('position' ,'int'     ,null,0      ,rs::UNSIGNED)
		);
	}
	
	protected function uprimary_key($i)
	{
		$sha = sha1(mt_rand());
		return strtoupper(substr($sha, 0, 6));
	}
}

class ppc_report_table extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('report_id')
		);
		self::$cols = self::init_cols(
			new rs_col('report_id' ,'char'	,36  ,null,rs::NOT_NULL),
			new rs_col('sheet_id'  ,'char'  ,6   ,''  ,rs::READ_ONLY),
			new rs_col('id'        ,'char'  ,8   ,''  ,rs::READ_ONLY),
			new rs_col('position'  ,'int'   ,null,0   ,rs::UNSIGNED),
			new rs_col('definition','text'  ,null,null)
		);
	}
	
	protected function uprimary_key($i)
	{
		$sha = sha1(mt_rand());
		return strtoupper(substr($sha, 0, 8));
	}
}

class ppc_cdl_user_cols extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('account_id', 'user_id');
		self::$cols = self::init_cols(
			new rs_col('account_id','char',16  ,'',rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('user_id'   ,'int' ,null,0 ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('cols'      ,'char',250 ,'',rs::NOT_NULL)
		);
	}
}

class ppc_cdl extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('account_id');
		self::$cols = self::init_cols(
			new rs_col('account_id'    ,'char'   ,16  ,'',rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('mo_spend'      ,'double' ,null,0 ,rs::NOT_NULL),
			new rs_col('yd_spend'      ,'double' ,null,0 ,rs::NOT_NULL),
			new rs_col('days_to_date'  ,'tinyint',null,0 ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('days_remaining','tinyint',null,0 ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('days_in_month' ,'tinyint',null,0 ,rs::UNSIGNED | rs::NOT_NULL)
		);
	}
}


class conv_type_count extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('aid', 'd')
		);
		self::$cols = self::init_cols(
			new rs_col('id'         ,'char'    ,16  ,''    ,rs::READ_ONLY),
			new rs_col('aid'        ,'char'    ,16  ,''    ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('d'          ,'date'    ,null,rs::DD,rs::NOT_NULL),
			new rs_col('market'     ,'char'    ,2   ,''    ,rs::NOT_NULL),
			new rs_col('account_id' ,'char'    ,16  ,''    ,rs::NOT_NULL),
			new rs_col('campaign_id','char'    ,16  ,''    ,rs::NOT_NULL),
			new rs_col('ad_group_id','char'    ,32  ,''    ,rs::NOT_NULL),
			new rs_col('ad_id'      ,'char'    ,32  ,''    ,rs::NOT_NULL),
			new rs_col('keyword_id' ,'char'    ,32  ,''    ,rs::NOT_NULL),
			new rs_col('device'     ,'char'    ,32  ,''    ,rs::NOT_NULL),
			new rs_col('purpose'    ,'char'    ,64  ,''    ,rs::NOT_NULL),
			new rs_col('name'       ,'char'    ,64  ,''    ,rs::NOT_NULL),
			new rs_col('amount'     ,'smallint',null,0     ,rs::NOT_NULL)
		);
	}

	// 16 hex chars
	protected function uprimary_key($i)
	{
		$sha = sha1(mt_rand());
		return strtoupper(substr($sha, 0, 16));
	}

	public static function get_account_conv_types($aid, $opts = array())
	{
		util::set_opt_defaults($opts, array(
			'include_market' => false
		));
		$tmp_conv_types = conv_type_count::get_all(array(
			'select' => "distinct name, market",
			'where' => "aid = :aid && name <> ''",
			'data' => array("aid" => $aid),
			'order_by' => "name asc"
		));
		$map = conv_type::get_client_market_map($aid);
		$conv_types = array();
		foreach ($tmp_conv_types as $conv_type) {
			if ($opts['include_market']) {
				$conv_types[$conv_type->market][] = $conv_type->name;
			}
			else {
				$ct_name = (isset($map[$conv_type->market][$conv_type->name])) ?
					$map[$conv_type->market][$conv_type->name] :
					$conv_type->name
				;
				if (!in_array($ct_name, $conv_types)) {
					$conv_types[] = $ct_name;
				}
			}
		}
		return $conv_types;
	}
}

// conv types might have differently spelled names in different markets but actually
// represent the same action
class conv_type extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('aid')
		);
		self::$cols = self::init_cols(
			new rs_col('id'       ,'char',8,'',rs::READ_ONLY),
			new rs_col('aid'      ,'char',16,'',rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('canonical','char',64,'',rs::NOT_NULL)
		);
	}

	// 16 hex chars
	protected function uprimary_key($i)
	{
		$sha = sha1(mt_rand());
		return strtoupper(substr($sha, 0, 8));
	}

	public static function get_client_market_map($aid, $market = false)
	{
		$qwhere = array("aid = :aid");
		$qdata = array("aid" => $aid);
		if ($market) {
			$qwhere[] = "market = :market";
			$qdata["market"] = $market;
		}
		$tmp_map = conv_type::get_all(array(
			"select" => array(
				"conv_type" => array("id", "canonical"),
				"conv_type_market" => array("conv_type_id", "market", "market_name")
			),
			"where" => $qwhere,
			"data" => $qdata,
			"join_many" => array(
				"conv_type_market" => "conv_type.id = conv_type_market.conv_type_id"
			)
		));
		$map = array();
		foreach ($tmp_map as $mapping) {
			foreach ($mapping->conv_type_market as $ct_market) {
				if ($market) {
					$map[$ct_market->market] = $mapping->canonical;
				}
				else {
					$map[$ct_market->market][$ct_market->market_name] = $mapping->canonical;
				}
			}
		}
		return $map;
	}
}

// conv types might have differently spelled names in different markets but actually
// represent the same action
class conv_type_market extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('conv_type_id', 'market');
		self::$cols = self::init_cols(
			new rs_col('conv_type_id','char',8,'',rs::READ_ONLY),
			new rs_col('market'      ,'char',2 ,'',rs::NOT_NULL),
			new rs_col('market_name' ,'char',64,'',rs::NOT_NULL)
		);
	}
}







?>