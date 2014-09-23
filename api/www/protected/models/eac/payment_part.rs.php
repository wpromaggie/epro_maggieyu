<?php

class mod_eac_payment_part extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('payment_id'),
			array('account_id')
		);
		self::$cols = self::init_cols(
			new rs_col('id'          ,'int'    ,null,null,rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('payment_id'  ,'char'   ,16  ,0   ,rs::READ_ONLY),
			new rs_col('account_id'  ,'char'   ,16  ,''  ,rs::READ_ONLY),
			new rs_col('division'    ,'enum'   ,32  ,''   ),
			new rs_col('dept'        ,'enum'   ,32  ,''   ),
			new rs_col('type'        ,'enum'   ,32  ,''   ),
			new rs_col('is_passthru' ,'bool'   ,null,0    ),
			new rs_col('amount'      ,'double' ,null,0    ),
			new rs_col('rep_pay_num' ,'tinyint',null,0    )
		);
	}
}
?>
