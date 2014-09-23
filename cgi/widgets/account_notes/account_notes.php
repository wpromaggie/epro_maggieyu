<?php

class wid_account_notes extends widget_base
{
	public $dept, $ac_id;
	
	function __construct($args = array())
	{
		foreach ($this as $k => $v)
		{
			if (array_key_exists($k, $args))
			{
				$this->$k = $args[$k];
			}
		}
	}
	
	public function output()
	{
		$notes = account_note::get_all(array(
			'where' => "ac_type = '{$this->dept}' && ac_id = '{$this->ac_id}'",
			'order_by' => "dt asc"
		));
		
		$users = array();
		foreach ($notes as $i => &$note)
		{
			if ($note->users_id)
			{
				if (array_key_exists($note->users_id, $users))
				{
					$user = $users[$note->users_id];
				}
				else
				{
					$user = db::select_one("select username from eppctwo.users where id = '{$note->users_id}'");
					$user = substr($user, 0, strpos($user, '@'));
					$users[$note->users_id] = $user;
				}
				$note->user = $user;
			}
			else
			{
				$note->user = '';
			}
			$note->date = substr($note->dt, 0, 11);
		}
		$ml_notes = ($notes->count()) ? '<table id="notes_table"></table>' : '<b>No Account Notes</b>';
		
		?>
		<div id="account_notes" ejo>
			<?php echo $ml_notes; ?>
		</div>
		<div>
			<p><b>New Note</b></p>
			<textarea name="new_note" id="new_note"></textarea><br />
			<input type="submit" a0="action_new_note" value="Submit" />
		</div>
		<?php
		cgi::add_js_var('notes', $notes);
		?>
		<?php
	}
	
	public function action_new_note()
	{
		$n = new account_note(array(
			'ac_type' => $this->dept,
			'ac_id' => $this->ac_id,
			'users_id' => user::$id,
			'dt' => date(util::DATE_TIME),
			'note' => $_POST['new_note']
		));
		$n->put();
	}
	
	public function ajax_note_edit_update()
	{
		db::dbg();
		$n = new account_note(array(
			'id' => $_POST['note_id'],
			'note' => $_POST['note']
		));
		$n->put();
	}
	
	public function ajax_note_edit_delete()
	{
		$n = new account_note(array(
			'id' => $_POST['note_id']
		));
		$n->delete();
	}
}

?>