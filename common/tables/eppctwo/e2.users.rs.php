<?php

class users extends rs_object
{
	public static $db, $cols, $primary_key, $uniques;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$uniques = array(
			array('username')
		);
		self::$cols = self::init_cols(
			new rs_col('id'                ,'int'    ,11  ,null,rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('e1_id'             ,'int'    ,11  ,0   ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('wiki_id'           ,'int'    ,11  ,0   ,rs::NOT_NULL),
			new rs_col('login_cookie'      ,'varchar',8   ,''  ,rs::NOT_NULL),
			new rs_col('company'           ,'int'    ,11  ,0   ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('username'          ,'varchar',255 ,''  ,rs::NOT_NULL),
			new rs_col('password'          ,'varchar',128 ,null,rs::NOT_NULL),
			new rs_col('realname'          ,'varchar',128 ,''  ,rs::NOT_NULL),
			new rs_col('primary_dept'      ,'varchar',32  ,''  ,rs::NOT_NULL),
			new rs_col('phone_ext'         ,'varchar',32  ,''  ,rs::NOT_NULL),
			new rs_col('exempt'            ,'bool'   ,1   ,1   ,rs::NOT_NULL),
			new rs_col('offsite_clockin_ok','bool'   ,null,0   ,rs::NOT_NULL),
			new rs_col('is_test_user'      ,'bool'   ,null,0   ,rs::NOT_NULL)
		);
	}

	public static function get_test_where_clause($prefix = '&&')
	{
		return (class_exists('user') && !user::is_developer()) ? "{$prefix} !is_test_user" : "";
	}
	
	public static function get_all_users()
	{
		$users = db::select("
			select *
			from users
			".users::get_test_where_clause('where')."
			order by realname asc
		", 'ASSOC');
		return $users;
	}
}

?>