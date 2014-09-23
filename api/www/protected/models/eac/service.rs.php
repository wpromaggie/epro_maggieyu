<?php

class mod_eac_service extends mod_eac_account
{
	public static $db, $cols, $primary_key;

	// getter in account
	protected static $depts;

	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'                   ,'char'    ,16  ,''    ,rs::READ_ONLY)
		);
	}

	public static function get_manager_options()
	{
		$dept = self::get_dept_from_class();
		return db::select("
			select u.id, u.realname
			from users u, user_guilds ug
			where
				ug.guild_id = :dept &&
				u.id = ug.user_id &&
				u.password <> ''
			order by realname asc
		", array(
			"dept" => $dept
		));
	}

	public static function manager_form_input($table, $col, $val)
	{
		$options = self::get_manager_options();
		array_unshift($options, array('', ' - None - '));
		return cgi::html_select($table.'_'.$col->name, $options, $val);
	}

	public static function sales_rep_form_input($table, $col, $val)
	{
		$dept = self::get_dept_from_class();
		$options = db::select("
			select u.id, u.realname
			from users u, user_guilds ug
			where
				ug.guild_id = 'sales' &&
				u.id = ug.user_id &&
				u.password <> ''
			order by realname asc
		");
		array_unshift($options, array('', ' - None - '));
		return cgi::html_select($table.'_'.$col->name, $options, $val);
	}

	public static function display_text($service)
	{
		switch (strtolower($service)) {
			case ('ppc'):
			case ('seo'):
			case ('smo'):
				return strtoupper($service);
			case ('partner'):
			case ('email'):
			case ('webdev'):
			default:
				return ucwords($service);
		}
	}
}
?>
