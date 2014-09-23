<?php

class offline_conversion_schedule extends rs_object{
	public static $db, $cols, $primary_key, $indexes;

	public static function set_table_definition(){
		self::$db = 'delly';
		self::$primary_key = array('id');
		self::$indexes = array(
			array('status')
		);

		// todo: no fid - all job specs can just use 
		self::$cols = self::init_cols(
			new rs_col('aid'        		 	,'char'    	,self::$id_len 	,''     ), // client id
			new rs_col('next_runtime_upload' 	,'datetime'	,''			 	,''     ), // next time to upload data
			new rs_col('frequency_upload'    	,'int'    	,32           	,''     ), // how frequent we will be uploading data
			new rs_col('next_runtime_download'	,'datetime'	,''			 	,''     ), // next time to download data
			new rs_col('frequency_download'     ,'int'    	,32           	,''     ), // how frequent we will be downloading data
			new rs_col('market'      			,'char'    	,5            	,''     )  // for whom we will upload to
		);
	}
}
?>