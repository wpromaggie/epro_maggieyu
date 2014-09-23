<?php

/*
 * all we care about for dq'ed leads is the email
 */
class mod_contracts_disqualified_lead extends rs_object
{
	public static $db, $cols, $primary_key, $uniques;

	public static function set_table_definition()
	{
		self::$db = 'contracts';
		self::$primary_key = array('id');
		self::$uniques = array(
			array('email')
		);
		self::$cols = self::init_cols(
			new rs_col('id'       ,'int' ,null,null,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('upload_id','int' ,null,null,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('email'    ,'char',64  ,''  ,rs::NOT_NULL)
		);
	}
}
?>
