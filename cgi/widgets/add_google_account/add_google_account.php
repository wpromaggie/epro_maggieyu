<?php

class wid_add_google_account extends widget_base
{
	public function action_add_google_account()
	{
		list($id, $name, $one_line) = util::list_assoc($_POST, 'id', 'name', 'one_line');
		if ($one_line)
		{
			if (preg_match("/^(.*?)\(.*(\d{3}-\d{3}-\d{4})/", $one_line, $matches))
			{
				$name = $matches[1];
				$id = $matches[2];
			}
			if (!$id || !$name)
			{
				return feedback::add_error_msg('One line non-empty but could not understand format');
			}
		}
		else if (!$id || !$name)
		{
			return feedback::add_error_msg('Need ID and Name, or one line format');
		}
		db::insert("eppctwo.g_accounts", array(
			'id' => trim(str_replace('-', '', $id)),
			'company' => 1,
			'text' => trim($name),
			'status' => 'On',
			'currency' => 'USD',
			'is_mcc' => 0
		));
		feedback::add_success_msg('Account <i>'.$name.'</i> added');
	}
}

?>