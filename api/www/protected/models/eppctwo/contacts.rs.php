<?php
class mod_eppctwo_contacts extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$indexes = array(array('client_id'));
		self::$cols = self::init_cols(
			new rs_col('id'            ,'bigint'  ,20  ,null    ,rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT),
			new rs_col('client_id'     ,'char'    ,16  ,''      ,rs::READ_ONLY),
			new rs_col('account_id'    ,'char'    ,16  ,''      ,rs::READ_ONLY),
			new rs_col('name'          ,'varchar' ,128 ,''      ,rs::NOT_NULL),
			new rs_col('title'         ,'varchar' ,32  ,''      ,rs::NOT_NULL),
			new rs_col('email'         ,'varchar' ,64  ,''      ,rs::NOT_NULL),
			new rs_col('phone'         ,'varchar' ,32  ,''      ,rs::NOT_NULL),
			new rs_col('fax'           ,'varchar' ,32  ,''      ,rs::NOT_NULL),
			new rs_col('street'        ,'varchar' ,64  ,''      ,rs::NOT_NULL),
			new rs_col('city'          ,'varchar' ,32  ,''      ,rs::NOT_NULL),
			new rs_col('state'         ,'varchar' ,32  ,''      ,rs::NOT_NULL),
			new rs_col('zip'           ,'varchar' ,16  ,''      ,rs::NOT_NULL),
			new rs_col('country'       ,'varchar' ,4   ,''      ,rs::NOT_NULL),
			new rs_col('password'      ,'varchar' ,128 ,''      ,0),
			new rs_col('authentication','varchar' ,32  ,''      ,0),
			new rs_col('status'        ,'varchar' ,16  ,''      ,0),
			new rs_col('last_login'    ,'datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('notes'         ,'varchar' ,200 ,''     ,rs::NOT_NULL)
		);
	}
}
?>