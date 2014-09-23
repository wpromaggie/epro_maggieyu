<?php

class mod_eppctwo_email_template_mapping extends rs_object
{
	public static $db, $cols, $primary_key, $uniques, $indexes;
	
	public static $actions = array(
		'Signup',
		'Activation',
		'Non-Renewing',
		'Report',
		'Cancel',
		'Multi-Reminder'
	);
	
	public static $department_options = array('ql','sb','gs','ww', 'combined');
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$uniques = array(
			array('department', 'action', 'plan')
		);
		self::$indexes = array(
			array('tkey')
		);
		self::$cols = self::init_cols(
			new rs_col('id'        ,'bigint',null,null,rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT),
			new rs_col('tkey'      ,'char'  ,32  ,''  ,rs::NOT_NULL),
			new rs_col('department','enum'  ,16  ,''  ,0),
			new rs_col('action'    ,'char'  ,16  ,''  ,rs::NOT_NULL),
			new rs_col('plan'      ,'char'  ,16  ,''  ,rs::NOT_NULL)
		);
	}
}
?>
