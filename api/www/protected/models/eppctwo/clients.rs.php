<?php
class mod_eppctwo_clients extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$cols = self::init_cols(
			new rs_col('id'         ,'bigint'  ,20  ,null   ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY | rs::AUTO_INCREMENT),
			new rs_col('company'    ,'int'     ,11  ,null   ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('name'       ,'varchar' ,255 ,''     ,rs::NOT_NULL),
			new rs_col('partner'    ,'varchar' ,32  ,''     ,rs::NOT_NULL),
			new rs_col('status'     ,'enum'    ,null,''     ,rs::NOT_NULL, array('', 'On', 'Cancelled', 'Incomplete', 'Off', 'Active')),
			new rs_col('data_id'    ,'varchar' ,8   ,'-1'   ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('cc_id'      ,'bigint'  ,20  ,null   ,rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('old_id'     ,'bigint'  ,20  ,'0'    ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('external_id','varchar' ,12  ,''     ,rs::NOT_NULL | rs::READ_ONLY)
		);
		self::$primary_key = array('id');
	}
	
	public static function sales_rep_form_input($table, $col, $val)
	{
		$options = db::select("
			select u.id u0, u.realname u1
			from users u
			order by u1 asc
		");
		array_unshift($options, array('', ' - Select - '));
		return cgi::html_select($table.'_'.$col->name, $options, $val);
	}
}
?>