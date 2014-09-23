<?php

class mod_eac_payment extends rs_object
{
	public static $db, $cols, $primary_key, $indexes, $has_many;
	
	public static $pay_method_options = array('CC','Check','Wire');
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$has_many = array('payment_part');
		self::$cols = self::init_cols(
			new rs_col('id'             ,'char'    ,16  ,''     ,rs::READ_ONLY),
			new rs_col('client_id'      ,'char'    ,16  ,''     ,rs::READ_ONLY),
			new rs_col('user_id'        ,'int'     ,null,0      ,rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('pay_id'         ,'bigint'  ,null,0      ,rs::UNSIGNED | rs::NOT_NULL), // the id of the object used to pay (eg cc id)
			new rs_col('pay_method'     ,'enum'    ,8   ,''     ),
			new rs_col('fid'            ,'char'    ,64  ,''     ,rs::READ_ONLY), // id returned by payment processor, if any
			new rs_col('ts'             ,'datetime',null,rs::DDT,rs::READ_ONLY), // timestamp processed
			new rs_col('date_received'  ,'date'    ,null,rs::DD ),
			new rs_col('date_attributed','date'    ,null,rs::DD ),
			new rs_col('event'          ,'enum'    ,32  ,''     ),
			new rs_col('amount'         ,'double'  ,null,0      ),
			new rs_col('notes'          ,'char'    ,255 ,''     )
		);
	}
	
	// 16 hex chars
	protected function uprimary_key($i)
	{
		$sha = sha1(mt_rand());
		return strtoupper(substr($sha, 0, 16));
	}
}
?>
