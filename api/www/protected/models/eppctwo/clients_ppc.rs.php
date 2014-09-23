<?php

class mod_eppctwo_clients_ppc extends rs_object
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
?>
