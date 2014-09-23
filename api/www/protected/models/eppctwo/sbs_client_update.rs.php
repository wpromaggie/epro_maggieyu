<?php

class mod_eppctwo_sbs_client_update extends rs_object
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
?>
