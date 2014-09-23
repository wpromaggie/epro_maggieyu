<?php

class wid_secondary_managers
{
	private $account_id, $dept;

	public function __construct($opts)
	{
		foreach ($opts as $k => $v) {
			$this->$k = $v;
		}
	}

	public function output()
	{
		$sms = db::select("
			select u.realname, u.id
			from eppctwo.users u, eppctwo.secondary_manager sm
			where sm.account_id = '{$this->account_id}' && sm.user_id = u.id
			order by realname asc
		");
		
		$users = db::select("
			select id, realname
			from eppctwo.users
			where password <> ''
			order by realname asc
		");
		array_unshift($users, array('', ' - Select - '));
		
		$ml = '';
		for ($i = 0; list($uname, $uid) = $sms[$i]; ++$i)
		{
			$ml .= '
				<tr>
					<td>'.$uname.'</td>
					<td><input type="submit" class="remove_user_button" uid="'.$uid.'" value="Remove" /></td>
				</tr>
			';
		}
		?>
		<div id="secondary_managers" ejo>
			<h2>Secondary Managers</h2>
			<table id="secondary_managers_table">
				<tbody>
					<?php echo $ml; ?>
					<tr>
						<td><?php echo cgi::html_select('add_manager', $users); ?></td>
						<td><input type="submit" a0="action_add_manager" value="Add Manager" /></td>
					</tr>
				</tbody>
			</table>
			<input type="hidden" name="remove_secondary_manager_id" id="remove_secondary_manager_id" value="" />
		</div>
		<?php
	}
	
	public function action_remove_secondary_manager()
	{
		$uid = $_POST['remove_secondary_manager_id'];
		$name = db::select_one("select realname from eppctwo.users where id = :uid", array("uid" => $uid));
		db::delete(
			"eppctwo.secondary_manager",
			"account_id = :aid &&user_id = :uid",
			array("aid" => $this->account_id, "uid" => $uid)
		);
		if (class_exists('feedback')) {
			feedback::add_success_msg('<i>'.$name.'</i> removed as manager.');
		}
	}

	public function action_add_manager()
	{
		$uid = $_POST['add_manager'];
		$name = db::select_one("select realname from eppctwo.users where id = :uid", array("uid" => $uid));
		db::insert("eppctwo.secondary_manager", array(
			'account_id' => $this->account_id,
			'user_id' => $uid
		));
		if (class_exists('feedback')) {
			feedback::add_success_msg('<i>'.$name.'</i> added as manager.');
		}
	}
}

?>