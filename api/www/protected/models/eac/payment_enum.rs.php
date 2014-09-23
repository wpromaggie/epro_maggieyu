<?php
abstract class mod_eac_payment_enum extends rs_object
{
	protected static function init_cols($enum_col)
	{
		return parent::init_cols(
			new rs_col('id'     ,'char',8 ,''     ,rs::READ_ONLY),
			new rs_col($enum_col,'char',32,''      )
		);
	}
	
	// 4 hex digits
	protected function uprimary_key($i)
	{
		return strtoupper(substr(sha1(mt_rand()), 0, 4));
	}
	
	public function update_from_array($data)
	{
		$classname = get_called_class();
		$enum_col = $classname::$enum_col;
		$prev_val = $this->$enum_col;
		$r = parent::update_from_array($data);
		if ($r !== false) {
			// success, update payment parts
			$payments_updated = payment::update_all(array(
				'set' => array($enum_col => $data[$enum_col]),
				'where' => "$enum_col = '".db::escape($prev_val)."'"
			));
			if ($payments_updated && class_exists('feedback')) {
				feedback::add_success_msg($payments_updated.' payments updated');
			}
			return $r;
		}
		else {
			return false;
		}
	}
}
?>