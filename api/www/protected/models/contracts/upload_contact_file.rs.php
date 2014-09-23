<?php

class mod_contracts_upload_contact_file extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'contracts';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'     ,'int'     ,null,null   ,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('created','datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('name'   ,'varchar' ,128 ,''     ,rs::NOT_NULL)
		);
	}
}
?>
