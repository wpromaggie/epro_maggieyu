<?php

class mod_eac_as_ppc extends mod_eac_service
{
	public static $db, $cols, $primary_key;

	public static $who_pays_clicks_options = array('Wpromote','Client');

	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                 ,'char'   ,16  ,''    ,rs::READ_ONLY),
			new rs_col('notes'              ,'text'   ,null,null  ,0           ),
			new rs_col('revenue_tracking'   ,'bool'   ,null,false ,rs::NOT_NULL),
			new rs_col('facebook'           ,'bool'   ,null,false ,rs::NOT_NULL),
			new rs_col('google_mpc_tracking','bool'   ,null,false ,rs::NOT_NULL),
			new rs_col('conversion_types'   ,'bool'   ,null,false ,rs::NOT_NULL),
			new rs_col('who_pays_clicks'    ,'enum'   ,16  ,null  ,0           ),
			new rs_col('budget'             ,'double' ,null,0     ,rs::NOT_NULL),
			new rs_col('carryover'          ,'double' ,null,0     ,rs::NOT_NULL),
			new rs_col('adjustment'         ,'double' ,null,0     ,rs::NOT_NULL),
			new rs_col('actual_budget'      ,'double' ,null,0     ,rs::NOT_NULL)
		);
	}

	public static function create($data, $opts = array())
	{
		util::load_lib('ppc');
		util::set_opt_defaults($data, array(
			'division' => 'mod_eac_service',
			'dept' => self::get_dept_from_class(),
			'status' => 'Active'
		));
		$account = parent::create($data, $opts);
		if (!isset($opts['skip_object_tables']) || $opts['skip_object_tables'] === false) {
			$markets = util::get_ppc_markets();
			foreach ($markets as $market) {
				ppc_lib::create_market_object_tables($market, $account->id);
			}
		}
		ppc_cdl::create(array(
			'account_id' => $account->id
		));
		return $account;
	}

	public function calc_actual_budget()
	{
		$this->actual_budget = $this->budget + $this->carryover + $this->adjustment;
	}
	
	public function update_actual_budget()
	{
		$this->calc_actual_budget();
		$this->update(array('cols' => array('actual_budget')));
	}

	public static function actual_budget_form_input($table, $col, $val)
	{
		return '<span title="Cannot be directly edited. Change budget, carryover and/or adjustment to set &quot;Actual Budget&quot;">'.util::format_dollars($val).'</span>';
	}
}

?>
