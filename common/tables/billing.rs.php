<?php

/*
 * for many to one relationship, create cc_x_{thing} table
 * for one to one relationship, add cc_id column to {$thing}
 * should not have one to many relationship - create card in multiple places if needed
 */

/*
 * todo: make sure everything that references a cc uses cc_x_{thing},
 *  get rid of foreign_table and foreign_id in ccs
 */

/*
 * todo: would be nice to have a base class for all pay_methods so
 *  payments could simply record an id instead of id and pay_method
 */

class ccs extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'           , 'bigint',null, null, rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT),
			new rs_col('foreign_table','varchar',64  , '', rs::NOT_NULL),
			new rs_col('foreign_id'   ,'varchar',64  , '', rs::NOT_NULL),
			new rs_col('name'         ,'varchar',128 , '', rs::NOT_NULL),
			new rs_col('country'      ,'varchar',4   , '', rs::NOT_NULL),
			new rs_col('zip'          ,'varchar',16  , '', rs::NOT_NULL),
			new rs_col('cc_number'    ,'blob'   ,null, null, rs::NOT_NULL),
			new rs_col('cc_type'      ,'varchar',16  , '', rs::NOT_NULL),
			new rs_col('cc_exp_month' ,'char'   ,2   , '', rs::NOT_NULL),
			new rs_col('cc_exp_year'  ,'varchar',4   , '', rs::NOT_NULL),
			new rs_col('cc_code'      ,'blob'   ,null, null, rs::NOT_NULL),
			new rs_col('status'       ,'enum'   ,null, 'Active', rs::NOT_NULL, array('Active','Inactive'))
		);
	}

	private static function encrypt_fields($x)
	{
		$encrypted_fields = array('cc_number', 'cc_code');
		foreach ($encrypted_fields as $field) {
			// encrypt number and code before we put them in db
			// if they are non-words, unset them
			// handle arrays and objects
			if (is_array($x)) {
				if ($x[$field]) {
					if (preg_match("/^\w+$/", $x[$field])) {
						$x[$field] = util::encrypt($x[$field]);
					}
					else {
						unset($x[$field]);
					}
				}
			}
			else {
				if ($x->$field) {
					if (preg_match("/^\w+$/", $x->$field)) {
						$x->$field = util::encrypt($x->$field);
					}
					else {
						unset($x->$field);
					}
				}
			}
		}
		return $x;
	}
	
	// override put
	public function put($opts = array())
	{
		self::encrypt_fields($this);
		return parent::put($opts);
	}

	public static function update_all($opts = array())
	{
		$opts['set'] = self::encrypt_fields($opts['set']);
		return parent::update_all($opts);
	}
	
	public static function country_form_input($table, $col, $val)
	{
		$countries = db::select("
			select a2, country
			from eppctwo.countries
			order by country asc
		");
		if (!$val)
		{
			$val = 'US';
		}
		return cgi::html_select($table.'_'.$col->name, $countries, $val);
	}
	
	public static function cc_type_form_input($table, $col, $val)
	{
		return cgi::html_select($table.'_'.$col->name, array('amex', 'disc', 'mc', 'visa'), $val);
	}
	
	public static function cc_exp_month_form_input($table, $col, $val, $opts = array())
	{
		$months = array(
			array('01', '01 - Jan'),
			array('02', '02 - Feb'),
			array('03', '03 - Mar'),
			array('04', '04 - Apr'),
			array('05', '05 - May'),
			array('06', '06 - Jun'),
			array('07', '07 - Jul'),
			array('08', '08 - Aug'),
			array('09', '09 - Sep'),
			array('10', '10 - Oct'),
			array('11', '11 - Nov'),
			array('12', '12 - Dec')
		);
		return cgi::html_select($table.'_'.$col->name.(($opts['suffix']) ? '_'.$opts['suffix'] : ''), $months, $val);
	}
	
	public static function cc_exp_year_form_input($table, $col, $val, $opts = array())
	{
		$year = date('Y');
		$years = range($year, $year + 12);
		return cgi::html_select($table.'_'.$col->name.(($opts['suffix']) ? '_'.$opts['suffix'] : ''), $years, $val);
	}
	
	// default is just to select ids
	public static function get_client_ccs($client_id, $display_or_actual = 'DISPLAY', $select = false)
	{
		if (!$select) {
			$select = array("ccs" => array("id"));
		}
		return ccs::get_all(array(
			'select' => $select,
			'join' => array("cc_x_client" => "ccs.id = cc_x_client.cc_id"),
			'where' => "cc_x_client.client_id = :cid",
			'data' => array('cid' => $client_id)
		));
	}
}

class check extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'            ,'bigint',null,null,rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT),
			new rs_col('check_number'  ,'int'   ,64  ,null)
		);
	}
}

class wire extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'            ,'bigint',null,null,rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT)
		);
	}
}

class cc_x_client extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('cc_id', 'client_id');
		self::$cols = self::init_cols(
			new rs_col('cc_id'    ,'bigint',null,0 , rs::READ_ONLY),
			new rs_col('client_id','char'  ,16  ,'', rs::READ_ONLY)
		);
	}
}

?>