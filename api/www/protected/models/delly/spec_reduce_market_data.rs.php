<?php


// we map by market, ad group
class mod_delly_spec_reduce_market_data extends job_spec
{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition()
	{
		self::$db = 'delly';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'       ,'int' ,null,null,rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('source'   ,'char',32  ,''  ,rs::NOT_NULL),
			new rs_col('data_opts','text',null,null)
		);
	}
}
?>