<?php
abstract class mod_eac_multi_col_enum extends rs_object
{
	protected static function init_cols($enum_cols, $mixed_col_len = false)
	{
		// optional, default 32, if passed in can be constant or array
		if (is_array($mixed_col_len))
		{
			$col_lens = $mixed_col_len;
		}
		else
		{
			$col_lens = array_fill(0, count($enum_cols), ($mixed_col_len) ? $mixed_col_len : 32);
		}
		// init with id col
		$args = array(new rs_col('id'     ,'char',8 ,''     ,rs::READ_ONLY));
		foreach ($enum_cols as $i => $enum_col)
		{
			$args[] = new rs_col($enum_col,'char',$col_lens[$i],'');
		}
		return call_user_func_array(array(parent, init_cols), $args);
	}
	
	// 6 hex digits
	protected function uprimary_key($i)
	{
		return strtoupper(substr(sha1(mt_rand()), 0, 6));
	}
}

?>