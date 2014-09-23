<?php


/*
 * couple classes for mapping old ids to new ones
 */

// id map for account/client ids
// todo: delete once everything seems to be running smoothly
class mod_eac_nto_account extends rs_object
{
	public static $db, $cols, $primary_key, $has_many;
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('naid');
		self::$cols = self::init_cols(
			new rs_col('dept','char'  ,16  ,''  ,rs::READ_ONLY),
			new rs_col('naid','char'  ,16  ,''  ,rs::READ_ONLY),
			new rs_col('ncid','char'  ,16  ,''  ,rs::READ_ONLY),
			new rs_col('oaid','bigint',null,null,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('ocid','bigint',20  ,null,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY)
		);
	}
}
?>
