<?php

class mod_eppctwo_reports extends rs_object
{
	public static $db, $cols, $primary_key, $uniques;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$uniques = array(
			'user' => array('user', 'account_id', 'name')
		);
		self::$cols = self::init_cols(
			new rs_col('id'         ,'char'    ,32  ,null   ,rs::NOT_NULL),
			new rs_col('user'       ,'int'     ,11  ,0      ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('account_id' ,'char'    ,16  ,''     ,rs_col_NOT_NULL),
			new rs_col('name'       ,'varchar' ,64  ,''     ,rs::NOT_NULL),
			new rs_col('is_template','tinyint' ,1   ,0      ,rs::NOT_NULL),
			new rs_col('create_date','datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('last_run'   ,'datetime',null,rs::DDT,rs::NOT_NULL),
			new rs_col('sheets'     ,'text'    ,null,null   ,rs::NOT_NULL)
		);
	}
	
	protected function uprimary_key(){
		return util::mt_rand_uuid();
	}

	public static function get_report_details_by_id($report_id){
		$q = "SELECT 
				r.id AS report_id,
				r.user AS user_id,
				r.account_id,
				r.name AS report_name,
				r.is_template,
				r.create_date AS report_created_date,
				r.last_run,
				rs.id AS report_sheet_id,
				rs.name AS sheet_name,
				rs.position AS sheet_position,
				rt.id AS report_table_id,
				rt.position AS sheet_table_position,
				rt.definition AS table_definition
				FROM  `eppctwo`.`reports` r
				LEFT JOIN `eppctwo`.`ppc_report_sheet` rs ON rs.report_id = r.id
				LEFT JOIN `eppctwo`.`ppc_report_table` rt ON rt.sheet_id = rs.id
				WHERE r.id = 30";
		$r = db::select($q,"ASSOC");
		$r_a = array();
		foreach($r as $idx => $row){
			$r_a[$idx] = $row;
			$r_a[$idx]['table_definition'] = json_decode($row['table_definition'],true); 
		}

		//logger($r);
		//logger(json_decode($r['table_definition']));
		return $r_a;
	}
	public static function get_report_summary($job_id =NULL){
		$q = "SELECT 
				r.sheets
				FROM `eppctwo`.`reports` r
				LEFT JOIN `delly`.`job` j ON j.fid = r.id
				WHERE j.id = '{$job_id}'";
		$r = db::select($q,"ASSOC");
		Logger($q);
        Logger($r[0]['sheets']);
		return json_decode($r[0]['sheets']);
		
	}
	
}
?>
