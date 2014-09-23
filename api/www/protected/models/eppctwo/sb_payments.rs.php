<?php
class mod_eppctwo_sb_payments extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	public static $type_options = array('Order','Recurring','Upgrade','Buyout','Optimization','Reseller','Refund New','Refund Old','Other');
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('account_id')
		);
		self::$cols = self::init_cols(
			new rs_col('id'           ,'bigint' ,null,null  ,rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('client_id'    ,'bigint' ,null,0     ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('account_id'   ,'bigint' ,null,0     ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('pay_id'       ,'bigint' ,null,0     ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('d'            ,'date'   ,null,rs::DD,rs::NOT_NULL),
			new rs_col('t'            ,'time'   ,null,rs::DT,rs::NOT_NULL),
			new rs_col('type'         ,'enum'   ,null,null  ,rs::NOT_NULL),
			new rs_col('department'   ,'enum'   ,null,null  ,0            ,sbs_lib::$departments),
			new rs_col('pay_method'   ,'enum'   ,null,null  ,0            ,array('cc','check','wire')),
			new rs_col('pay_option'   ,'enum'   ,null,'NA'  ,rs::NOT_NULL ,array('NA','1_0','3_0','6_1','12_3')),
			new rs_col('amount'       ,'double' ,null,0     ,rs::NOT_NULL),
			new rs_col('do_charge'    ,'bool'   ,null,1     ,rs::NOT_NULL),
			new rs_col('notes'        ,'varchar',256 ,''    ,rs::NOT_NULL),
			new rs_col('sb_payment_id','int'    ,null,0     ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY)
		);
	}
	
	public static function get_payment_option($pay_option)
	{
		return (($pay_option == '' || $pay_option == 'standard') ? '1_0' : $pay_option);
	}
	
	public function is_refund()
	{
		return (strpos($this->type, 'Refund') !== false);
	}
}
?>
