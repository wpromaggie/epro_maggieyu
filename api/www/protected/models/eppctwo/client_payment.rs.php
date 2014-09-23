<?php

class mod_eppctwo_client_payment extends rs_object
{
	public static $db, $cols, $primary_key, $indexes, $has_many;
	
	public static $pay_method_options;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('client_id')
		);
		self::$has_many = array('client_payment_part');
		self::$pay_method_options = array('cc','check','wire');
		self::$cols = self::init_cols(
			new rs_col('id'             ,'int'    ,null,null  ,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('client_id'      ,'bigint' ,null,0     ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('user_id'        ,'int'    ,null,0     ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('pay_id'         ,'bigint' ,null,0     ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('pay_method'     ,'enum'   ,8   ,''    ,rs::NOT_NULL),
			new rs_col('fid'            ,'varchar',64  ,''    ,rs::NOT_NULL),
			new rs_col('date_received'  ,'date'   ,null,rs::DD,rs::NOT_NULL),
			new rs_col('date_attributed','date'   ,null,rs::DD,rs::NOT_NULL),
			new rs_col('amount'         ,'double' ,null,0     ,rs::NOT_NULL),
			new rs_col('notes'          ,'varchar',256 ,''    ,rs::NOT_NULL),
			new rs_col('sales_notes'    ,'varchar',256 ,''    ,rs::NOT_NULL)
		);
	}

	public static function get_all_payments(){

	}

	public static function get_payments_by_cid($cid){
		$q = "SELECT 
					cp.id AS cp_id,
					cp.client_id,
					user_id,
					pay_id,
					pay_method,
					fid,
					date_received,
					date_attributed,
					cp.amount AS total_amount,
					notes,
					sales_notes,
					cpp.id AS cpp_id,
					cpp.type AS cpp_types,
					cpp.amount AS cpp_amount,
					cpp.rep_pay_num,
					cpp.rep_comm 
				FROM `eppctwo`.`client_payment` cp
				LEFT JOIN `eppctwo`.`client_payment_part` cpp on cpp.client_payment_id = cp.id
				WHERE `cp`.`client_id` IN ('{$cid}')";
		return db::select($q,'ASSOC');
	}
}

?>
