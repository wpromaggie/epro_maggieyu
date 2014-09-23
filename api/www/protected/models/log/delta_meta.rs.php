<?php
class mod_log_delta_meta extends rs_object
{
	public static $db, $cols, $primary_key, $has_one, $has_many;

	public static function set_table_definition(){
		self::$db = 'log';
		self::$primary_key = array('id');	
		self::$has_many = array('delta_kv');
		self::$cols = self::init_cols(
			new rs_col('id'			,'char'		,36		,'' 		,rs::NOT_NULL),
			new rs_col('user'		,'char'		,36		,NULL 		,rs::NOT_NULL),
			new rs_col('time'		,'datetime'	,NULL 	,rs::DDT 	,rs::NOT_NULL),
			new rs_col('database'	,'varchar'	,64		,NULL 		,rs::NOT_NULL),
			new rs_col('table'		,'varchar'	,64 	,NULL 		,rs::NOT_NULL),
			new rs_col('operation'	,'char'		,20 	,NULL 		,rs::NOT_NULL),
			new rs_col('pk_name'	,'varchar'	,64		,NULL 		,rs::NOT_NULL),
			new rs_col('pk_value'	,'varchar'	,64		,NULL 		,rs::NOT_NULL)
		);
	}

	protected function uprimary_key($i){
		return util::mt_rand_uuid();
	}


	public static function update_hook($user,$table,$kv,$pk = array()){
		self::change_hook('update',$user,$table,$kv,$pk = array());
	}

	public static function delete_hook($user,$table,$kv,$pk = array()){
		self::change_hook('delete',$user,$table,$kv,$pk = array());
	}

	public static function change_hook($type,$user,$table,$kv,$pk = array()){
		$dbtbl = (strpos($table,'.'))? explode('.',$table) : array('',$table);
		$pk = (array_key_exists('name',$pk) && array_key_exists('value',$pk))? $pk : array('name'=>'','value'=>'');

		$data = array(
					//'id'=>rand(), //id set by uprimary_key
					'user'=>$user,
					'time'=>date(util::DATE_TIME),
					'database'=>$dbtbl[0],
					'table'=>$dbtbl[1],
					'operation'=>$type,	
					'pk_name'=>$pk['name'],
					'pk_value'=>$pk['value']
				);
	
		$dm = self::create($data);
		foreach($kv as $k => $v){
			$dkv = new delta_kv();
			$data = array(
						//'id'=>rand(), //id set by uprimary_key
						'meta_id'=>$dm->id,
						'field'=>$k,
						'value'=>$v
					);
			$dkv::create($data);	
		}
	}
}
?>