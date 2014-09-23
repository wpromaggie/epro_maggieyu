<?php

class mod_contracts_lead extends rs_object
{
	public static $db, $cols, $primary_key, $uniques, $indexes;
	
	public static $dup_type_options = array('', 'disqualified', 'contact');
	
	public static function set_table_definition()
	{
		self::$db = 'contracts';
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
?>
