<?php
/*
 * don't want to use guild so that if someone leaves the company we still have their guild history
 * use this table instead to track active reps
 */
class mod_eppctwo_sbr_active_rep extends rs_object{
	public static $db, $cols, $primary_key;

	public static function set_table_definition(){
		self::$db = 'eppctwo';
		self::$primary_key = array('users_id');
		self::$cols = self::init_cols(
			new rs_col('users_id'                   ,'int'                   ,11    ,''		)
			);
	}
}
?>
