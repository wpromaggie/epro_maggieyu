<?php


class mod_eac_ppe_dept extends mod_eac_payment_enum
{
	public static $db, $cols, $primary_key, $uniques;

	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$uniques = array(
			array(self::$enum_col)
		);
		self::$cols = self::init_cols(
			new rs_col('id'                         ,'char'                  ,8     ,''		),
			new rs_col('dept'                       ,'char'                  ,32    ,''		)
			);
	}
	
	private static function standardize_enum_col(&$data)
	{
		$tmp = util::simple_text($data[self::$enum_col]);
		$data[self::$enum_col] = preg_replace("/^[^a-z]+/", '', $tmp);
	}
	
	public function update_from_array($data)
	{
		self::standardize_enum_col($data);
		return parent::update_from_array($data);
	}
	
	public static function create($data, $opts = array())
	{
		self::standardize_enum_col($data);
		return parent::create($data, $opts);
	}
}

?>
