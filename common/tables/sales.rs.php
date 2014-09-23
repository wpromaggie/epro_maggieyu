<?php

class sales_client_info extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	public static $types = array(
		'Inbound',
		'Outbound',
		'SPL'
	);
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('account_id');
		self::$indexes = array(
			array('client_id')
		);
		self::$cols = self::init_cols(
			new rs_col('client_id' ,'char',16  ,''       ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('account_id','char',16  ,''       ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('sales_rep' ,'int' ,null,null     ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('type'      ,'enum',null,'Inbound',0,self::$types)
		);
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

class sales_commission extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('dept', 'sale_type');
		self::$cols = self::init_cols(
			new rs_col('dept'    ,'varchar',8   ,''  ,rs::NOT_NULL),
			new rs_col('com_type','enum'   ,null,null,rs::NO_ATTRS,sales_client_info::$types),
			new rs_col('percent' ,'double' ,null,0.0 ,rs::UNSIGNED | rs::NOT_NULL)
		);
	}
}

class upload_lead_file extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'sales_leads';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'     ,'int'     ,null,null   ,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('created','datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('name'   ,'char'    ,128 ,''     ,rs::NOT_NULL)
		);
	}
}

class upload_disqualified_file extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'sales_leads';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'     ,'int'     ,null,null   ,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('created','datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('name'   ,'char'    ,128 ,''     ,rs::NOT_NULL)
		);
	}
}

class upload_contact_file extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'sales_leads';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'     ,'int'     ,null,null   ,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('created','datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('name'   ,'varchar' ,128 ,''     ,rs::NOT_NULL)
		);
	}
}

class lead extends rs_object
{
	public static $db, $cols, $primary_key, $uniques, $indexes;
	
	public static $dup_type_options = array('', 'disqualified', 'contact');
	
	public static function set_table_definition()
	{
		self::$db = 'sales_leads';
		self::$primary_key = array('id');
		self::$uniques = array(
			array('email')
		);
		self::$indexes = array(
			array('upload_id')
		);
		self::$cols = self::init_cols(
			new rs_col('id'           ,'int' ,null,null,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('upload_id'    ,'int' ,null,null,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('is_dup'       ,'bool',null,0   ,rs::NOT_NULL),
			new rs_col('dup_type'     ,'enum',16  ,''  ,rs::NOT_NULL),
			new rs_col('dup_upload_id','int' ,null,0   ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('company'      ,'char',64  ,''  ,rs::NOT_NULL),
			new rs_col('prefix'       ,'char',8   ,''  ,rs::NOT_NULL),
			new rs_col('first'        ,'char',32  ,''  ,rs::NOT_NULL),
			new rs_col('last'         ,'char',32  ,''  ,rs::NOT_NULL),
			new rs_col('phone'        ,'char',64  ,''  ,rs::NOT_NULL),
			new rs_col('email'        ,'char',64  ,''  ,rs::NOT_NULL),
			new rs_col('title'        ,'char',64  ,''  ,rs::NOT_NULL),
			new rs_col('address'      ,'text',null,''  ,rs::NOT_NULL),
			new rs_col('url'          ,'char',128 ,''  ,rs::NOT_NULL),
			new rs_col('biz_desc'     ,'text',null,''  ,rs::NOT_NULL)
		);
	}
}

/*
 * all we care about for dq'ed leads is the email
 */
class disqualified_lead extends rs_object
{
	public static $db, $cols, $primary_key, $uniques;

	public static function set_table_definition()
	{
		self::$db = 'sales_leads';
		self::$primary_key = array('id');
		self::$uniques = array(
			array('email')
		);
		self::$cols = self::init_cols(
			new rs_col('id'       ,'int' ,null,null,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('upload_id','int' ,null,null,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('email'    ,'char',64  ,''  ,rs::NOT_NULL)
		);
	}
}

/*
 * all we care about for contacts
 */
class contact_lead extends rs_object
{
	public static $db, $cols, $primary_key, $uniques;

	public static function set_table_definition()
	{
		self::$db = 'sales_leads';
		self::$primary_key = array('id');
		self::$uniques = array(
			array('email')
		);
		self::$cols = self::init_cols(
			new rs_col('id'       ,'int' ,null,null,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('upload_id','int' ,null,null,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('email'    ,'char',64  ,''  ,rs::NOT_NULL)
		);
	}
}

/*
 * url lead stuff
 */

class url_lead extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition()
	{
		self::$db = 'sales_leads';
		self::$primary_key = array('url');
		self::$indexes = array(array('lead_upload_id'));
		self::$cols = self::init_cols(
			new rs_col('url'               ,'varchar',128 ,''     ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('url_lead_upload_id','int'    ,null,null,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('name'              ,'varchar',80  ,''     ,rs::NOT_NULL),
			new rs_col('email'             ,'varchar',80  ,''     ,rs::NOT_NULL),
			new rs_col('phone'             ,'varchar',256 ,''     ,rs::NOT_NULL)
		);
	}
}

class url_lead_upload extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'sales_leads';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'     ,'int'     ,null,null   ,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('created','datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('name'   ,'varchar' ,128 ,''     ,rs::NOT_NULL)
		);
	}
}

class url_lead_url extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition()
	{
		self::$db = 'sales_leads';
		self::$primary_key = array('id');
		self::$indexes = array(array('lead_upload_id'));
		self::$cols = self::init_cols(
			new rs_col('id'                ,'bigint' ,null,null,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY | rs::AUTO_INCREMENT),
			new rs_col('url_lead_upload_id','int'    ,null,null,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('url'               ,'varchar',128 ,''  ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('is_new'            ,'bool'   ,null,0   ,rs::NOT_NULL | rs::READ_ONLY)
		);
	}
}

class sales_hierarch extends rs_object
{
	public static $db, $cols, $primary_key;

	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id' ,'bigint',null,null,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY | rs::AUTO_INCREMENT),
			new rs_col('pid','int'   ,null,0   ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('cid','int'   ,null,0   ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY)
		);
	}
}

?>