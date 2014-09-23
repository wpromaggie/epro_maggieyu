<?php

class mod_eppctwo_google_app_token extends rs_object
{
	public static $db, $cols, $primary_key, $uniques;
	
	private static $goo_token_keys = array('access_token', 'expires_in', 'created');
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$uniques = array(
			array('scopes', 'iss', 'prn')
		);
		self::$cols = self::init_cols(
			new rs_col('id'          ,'int' ,null,null,rs::NOT_NULL | rs::UNSIGNED | rs::AUTO_INCREMENT | rs::READ_ONLY),
			new rs_col('scopes'      ,'char',128 ,''  ,rs::NOT_NULL),
			new rs_col('iss'         ,'char',128 ,''  ,rs::NOT_NULL),
			new rs_col('prn'         ,'char',128 ,''  ,rs::NOT_NULL),
			new rs_col('created'     ,'int' ,null,0   ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('expires_in'  ,'int' ,null,0   ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('expires_at'  ,'int' ,null,0   ,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('access_token','char',200 ,''  ,rs::NOT_NULL)
		);
	}
	
	public function __construct($scopes, $iss, $prn)
	{
		if (is_array($scopes))
		{
			$tmp = array_filter(array_unique($scopes));
			usort($tmp, 'strcasecmp');
			$scopes = implode(',', $tmp);
		}
		parent::__construct(array(
			'scopes' => $scopes,
			'iss' => $iss,
			'prn' => $prn
		));
		$this->get();
	}
	
	// sample tok str:
	// {"access_token":"1\/a5u0pc5K4ZClvCuQP4SITVRqKIgT2vKKYyzhENpVTfQ","expires_in":3600,"created":1346165658}
	public function import_new($tok_str)
	{
		$tok_data = json_decode($tok_str, true);
		$tok_data['expires_at'] = $tok_data['created'] + $tok_data['expires_in'];
		foreach ($tok_data as $k => $v)
		{
			$this->$k = $v;
		}
		return $this->put();
	}
	
	public function is_valid()
	{
		return ($this->id && time() < $this->expires_at);
	}
	
	public function serialize()
	{
		$d = array();
		foreach (self::$goo_token_keys as $k)
		{
			$d[$k] = $this->$k;
		}
		return json_encode($d);
	}
}
?>
