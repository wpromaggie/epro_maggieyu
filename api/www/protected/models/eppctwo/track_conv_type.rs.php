<?php


class mod_eppctwo_track_conv_type extends rs_object
{
	public static $db, $cols, $primary_key, $uniques;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$cols = array(
			'id' => new rs_col('id', 'int', 10, null, rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT),
			'client_id' => new rs_col('client_id', 'varchar', 32, '', rs::NOT_NULL),
			'conv_type' => new rs_col('conv_type', 'varchar', 64, '', rs::NOT_NULL)
		);
		self::$primary_key = array('id');
		self::$uniques = array(
			array('client_id', 'conv_type')
		);
	}
}
?>
