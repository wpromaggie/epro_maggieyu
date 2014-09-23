<?php

class mod_eppctwo_cache extends rs_object{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id' ,'char',64  ,'',rs::NOT_NULL),
			new rs_col('val','blob',null,'',rs::NOT_NULL)
		);
	}

	public static function write($id, $val)
	{
		$c = new cache(array('id' => $id, 'val' => $val), array('do_get' => false));
		return $c->put();
	}

	public static function read($id, $type = false)
	{
		$c = new cache(array('id' => $id));
		switch ($type) {
			case ('json'): return json_decode($c->val, true);
			default: return $c->val;
		}
	}
}
?>
