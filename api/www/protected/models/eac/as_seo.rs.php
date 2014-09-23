<?php

class mod_eac_as_seo extends mod_eac_service
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                  ,'char',16  ,'',rs::READ_ONLY),
			new rs_col('link_builder_manager','int' ,11  ,0 ,rs::UNSIGNED),
			new rs_col('billing_reminder'    ,'bool',null,1 ,rs::NOT_NULL)
		);
	}

	public static function link_builder_manager_form_input($table, $col, $val)
	{
		return self::manager_form_input($table, $col, $val);
	}
}
?>
