<?php

class wid_managers extends widget_base
{
	public function pre_output()
	{
		if (empty(g::$client_id))
		{
			cgi::include_widget('client_list');
		}
	}
	
	public function output()
	{
		if (empty(g::$client_id))
		{
			$this->clients();
		}
		else
		{
			$this->managers_show();
		}
	}
	
	public function managers_show()
	{
		$departments = array(
			'ppc' => array(
				'text' => 'PPC',
			),
			'seo' => array(
				'text' => 'SEO',
				'assistant_managers' => array('link_builder_manager')
			),
			'webdev' => array(
				'text' => 'Web Dev',
			)
		);
		
		if (array_key_exists('managers_submit', $_POST))
		{
			$this->managers_submit($departments);
		}
		
		$users = db::select("
			select username, realname
			from eppctwo.users
			where password <> ''
			order by realname asc
		");
		array_unshift($users, array('', ' - Select - '));
		
		$ml = '';
		foreach ($departments as $department => $info)
		{
			$ml .= $this->get_manager_select($department, $info['text'], 'manager', $department, $users);
			if (array_key_exists('assistant_managers', $info))
			{
				foreach ($info['assistant_managers'] as $db_field)
				{
					$ml .= $this->get_manager_select($department, util::display_text($db_field), $db_field, "{$department}_{$db_field}", $users);
				}
			}
		}
		?>
		<h1><?php echo db::select_one("select name from clients where id = '".g::$client_id."'"); ?> Managers!</h1>
		<table>
			<tbody>
				<?php echo $ml; ?>
				<tr>
					<td></td>
					<td><input type="submit" name="managers_submit" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	
	private function managers_submit($departments)
	{
		$managers_updated = array();
		foreach ($departments as $department => $info)
		{
			if ($this->managers_submit_check_field($department, 'manager', $department))
			{
				$managers_updated[] = $info['text'];
			}
			if (array_key_exists('assistant_managers', $info))
			{
				foreach ($info['assistant_managers'] as $db_field)
				{
					if ($this->managers_submit_check_field($department, $db_field, "{$department}_{$db_field}"))
					{
						$managers_updated[] = util::display_text($db_field);
					}
				}
			}
		}
		if ($managers_updated)
		{
			feedback::add_success_msg('Managers Updated: '.implode(', ', $managers_updated));
		}
		else
		{
			feedback::add_msg('No Changes Detected', 'alert');
		}
	}
	
	private function managers_submit_check_field($department, $db_field, $form_field)
	{
		$db_manager = db::select_one("select {$db_field} from eppctwo.clients_{$department} where client = '".g::$client_id."'");
		$form_manager = $_POST[$form_field];
		
		if ($form_manager != $db_manager)
		{
			db::insert_update("eppctwo.clients_{$department}", array('client'), array(
				'client' => g::$client_id,
				'company' => 1,
				$db_field => $form_manager
			));
			return true;
		}
		return false;
	}
	
	private function get_manager_select($department, $text, $db_field, $form_field, $users)
	{
		$manager = db::select_one("select {$db_field} from eppctwo.clients_{$department} where client = '".g::$client_id."'");
		return '
			<tr>
				<td>'.$text.'</td>
				<td>'.cgi::html_select($form_field, $users, $manager).'</td>
			</tr>
		';
	}
	
	public function clients()
	{
		$client_list_widget = new wid_client_list();
		$client_list_widget->output();
	}
}

?>