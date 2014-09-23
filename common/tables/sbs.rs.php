<?php
require_once(__DIR__.'/../modules/sbs_lib.php');

class sbs_payment extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	public static $type_options = array('Order','Recurring','Upgrade','Buyout','Optimization','Reseller','Refund New','Refund Old','Other');
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('account_id')
		);
		self::$cols = self::init_cols(
			new rs_col('id'           ,'bigint' ,null,null  ,rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('client_id'    ,'bigint' ,null,0     ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('account_id'   ,'bigint' ,null,0     ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('pay_id'       ,'bigint' ,null,0     ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('d'            ,'date'   ,null,rs::DD,rs::NOT_NULL),
			new rs_col('t'            ,'time'   ,null,rs::DT,rs::NOT_NULL),
			new rs_col('type'         ,'enum'   ,null,null  ,rs::NOT_NULL),
			new rs_col('department'   ,'enum'   ,null,null  ,0            ,sbs_lib::$departments),
			new rs_col('pay_method'   ,'enum'   ,null,null  ,0            ,array('cc','check','wire')),
			new rs_col('pay_option'   ,'enum'   ,null,'NA'  ,rs::NOT_NULL ,array('NA','1_0','3_0','6_1','12_3')),
			new rs_col('amount'       ,'double' ,null,0     ,rs::NOT_NULL),
			new rs_col('do_charge'    ,'bool'   ,null,1     ,rs::NOT_NULL),
			new rs_col('notes'        ,'varchar',256 ,''    ,rs::NOT_NULL),
			new rs_col('sb_payment_id','int'    ,null,0     ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY)
		);
	}
	
	public static function get_payment_option($pay_option)
	{
		return (($pay_option == '' || $pay_option == 'standard') ? '1_0' : $pay_option);
	}
	
	public function is_refund()
	{
		return (strpos($this->type, 'Refund') !== false);
	}
}

class sbs_billing_failure extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('department', 'account_id');
		self::$cols = self::init_cols(
			new rs_col('department'  ,'enum'   ,null,null  ,0            ,sbs_lib::$departments),
			new rs_col('account_id'  ,'bigint' ,null,0     ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('details'     ,'varchar',256 ,''    ,rs::NOT_NULL),
			new rs_col('first_fail'  ,'date'   ,null,rs::DD,rs::NOT_NULL),
			new rs_col('last_fail'   ,'date'   ,null,rs::DD,rs::NOT_NULL),
			new rs_col('num_fails'   ,'tinyint',null,0     ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('last_contact','date'   ,null,rs::DD,rs::NOT_NULL),
			new rs_col('num_contacts','tinyint',null,0     ,rs::UNSIGNED | rs::NOT_NULL)
		);
	}
}

class sbs_client_update extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(array('department', 'account_id'));
		self::$cols = self::init_cols(
			new rs_col('id'          ,'bigint'  ,null       ,null   ,rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT),
			new rs_col('department'  ,'enum'    ,null       ,null   ,0,sbs_lib::$departments),
			new rs_col('account_id'  ,'bigint'  ,null       ,0      ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('dt'          ,'datetime',null       ,rs::DDT,rs::NOT_NULL),
			new rs_col('processed_dt','datetime',null       ,rs::DDT,rs::NOT_NULL),
			new rs_col('users_id'    ,'int'     ,null       ,0      ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('note'        ,'varchar' ,512        ,''     ,rs::NOT_NULL),
			new rs_col('type'        ,'enum'    ,null       ,null   ,0,array('sbs-contact','sbs-cc','sbs-upgrade','ql-ad','ql-keywords','sb-ad')),
			new rs_col('data'        ,'text'    ,null       ,''     ,0)
		);
	}
}

class sbs_manual_order extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'     ,'bigint'  ,null,null   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('created','datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('mo_key' ,'varchar' ,32  ,''     ,rs::NOT_NULL)
		);
	}
}

class coupons extends rs_object
{
        public static $db, $cols, $primary_key, $indexes;
        
        public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'             ,'int'      ,null    , null  ,rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT),
			new rs_col('code'           ,'varchar'  ,16      ,''   ,0     ,rs::NOT_NULL),
			new rs_col('type'           ,'enum'     ,null    ,null   ,0     ,array('setup fee','first month')),
			new rs_col('value'          ,'double'   ,null    ,0      ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('value_type'     ,'enum'     ,null    ,null   ,0     ,array('percent','dollars')),
                        new rs_col('contract_length','int'      ,2       ,0      ,rs::NOT_NULL),
			new rs_col('description'    ,'varchar'  ,256     ,''     ,rs::NOT_NULL),
                        new rs_col('status'         ,'enum'     ,null    ,null   ,0     ,array('active','expired'))
		);
	}
}

class sbs_coupons extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('department', 'account_id');
		self::$cols = self::init_cols(
			new rs_col('department'  ,'enum'   ,null,null  ,0            ,sbs_lib::$departments),
			new rs_col('account_id'  ,'bigint' ,null,0     ,rs::UNSIGNED | rs::NOT_NULL),
                        new rs_col('coupon_id'   ,'int'    ,null,0     ,rs::UNSIGNED | rs::NOT_NULL)
		);
	}
}

class sbr_partner extends rs_object
{
	public static $db, $cols, $primary_key, $has_many;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$has_many = array('sbr_source');
		self::$cols = self::init_cols(
			new rs_col('id'     ,'varchar',64  ,''  ,rs::NOT_NULL),
			new rs_col('status' ,'enum'   ,null,'On',rs::NOT_NULL,array('On', 'Off'))
		);
	}
}

class sbr_source extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('sbr_partner_id', 'id');
		self::$cols = self::init_cols(
			new rs_col('sbr_partner_id','varchar',64  ,''  ,rs::NOT_NULL),
			new rs_col('id'            ,'varchar',64  ,''  ,rs::NOT_NULL),
			new rs_col('status'        ,'enum'   ,null,'On',rs::NOT_NULL,array('On', 'Off'))
		);
	}
}

/*
 * don't want to use guild so that if someone leaves the company we still have their guild history
 * use this table instead to track active reps
 */
class sbr_active_rep extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('users_id');
		self::$cols = self::init_cols(
			new rs_col('users_id','int',11 ,null,rs::UNSIGNED | rs::NOT_NULL)
		);
	}
}


class email_log_entry extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	public static $department_options;
	
	public static function set_table_definition()
	{
		self::$department_options = array_merge(sbs_lib::$departments, array('combined'));
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('department', 'account_id'),
			array('created')
		);
		self::$cols = self::init_cols(
			new rs_col('id'          ,'bigint'  ,null,null   ,rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT),
			new rs_col('department'  ,'enum'    ,null,null   ,0),
			new rs_col('account_id'  ,'bigint'  ,null,0      ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('created'     ,'datetime',null,RS::DDT,rs::NOT_NULL),
			new rs_col('sent_success','bool'    ,null,0      ,rs::NOT_NULL),
			new rs_col('sent_details','varchar' ,128 ,0      ,rs::NOT_NULL),
			new rs_col('type'        ,'varchar' ,32  ,''     ,rs::NOT_NULL),
			new rs_col('from'        ,'varchar' ,128 ,''     ,rs::NOT_NULL),
			new rs_col('to'          ,'varchar' ,128 ,''     ,rs::NOT_NULL),
			new rs_col('subject'     ,'varchar' ,128 ,''     ,rs::NOT_NULL),
			new rs_col('headers'     ,'varchar' ,512 ,''     ,rs::NOT_NULL),
			new rs_col('body'        ,'text'    ,null,''     ,rs::NOT_NULL)
		);
	}
}

class sbs_account_rep extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('users_id');
		self::$cols = self::init_cols(
			new rs_col('users_id','int' ,11 ,null,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('name'    ,'char',64 ,''  ,rs::NOT_NULL),
			new rs_col('email'   ,'char',64 ,''  ,rs::NOT_NULL),
			new rs_col('phone'   ,'char',64 ,''  ,rs::NOT_NULL)
		);
	}
}


?>