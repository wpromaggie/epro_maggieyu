<?php

class mod_account_service_partner extends mod_account_service
{
	protected $m_name = 'email';

	public function pre_output()
	{
		parent::pre_output();
		$this->display_default = 'info';
	}

	public function get_menu()
	{
		$menu = parent::get_menu();
		$menu->append(
			new MenuItem('Tasks', 'tasks', array('query_keys' => array('aid')))
		);
		return $menu;
	}

	protected function get_default_payment_values()
	{
		$default_vals = array();
		foreach (as_partner::$recurring_payment_types as $type) {
			$partner_type = as_partner::get_account_payment_type_key($type);
			if ($this->account->$partner_type) {
				$default_vals[$type] = $this->account->$partner_type;
			}
		}
		return $default_vals;
	}

	public function pre_output_tasks()
	{
		$this->tasks_url = 'account/service/'.$this->dept.'/tasks?aid='.$this->aid;
		$this->layout_id = $_GET['layout_id'];
		if(!empty($_POST['select_layout_id']) && empty($this->layout_id)){
			cgi::redirect($this->tasks_url.'&layout_id='.$_POST['select_layout_id']);
		}
		$this->account_tasks = $this->register_widget('account_tasks', array('layout' => $this->layout_id, 'dept' => $this->dept, 'ac' => $this->account));
	}
	
	public function action_add_task_list()
	{
		if(empty($_POST['add_layout_id'])) return;
		$this->account_tasks->attach_tasks_to_client($_POST['add_layout_id']);
		cgi::redirect($this->tasks_url.'&layout_id='.$_POST['add_layout_id']);
	}
	
	public function action_add_section()
	{
		$this->account_tasks->add_section();
		cgi::redirect($this->tasks_url.'&layout_id='.$this->layout_id);
	}
	
	public function action_remove_layout()
	{
		$this->account_tasks->delete_layout();
	}
	
	public function display_tasks()
	{
		$cur_layout_ids = db::select("SELECT DISTINCT layout_id FROM account_tasks.client_nodes WHERE ac_id = :aid", array('aid' => $this->aid));
		$add_options = $cur_options = "<option value=''></option>\n";
		foreach($this->account_tasks->get_layouts() as $layout) {
			$selected = ($_GET['layout_id']==$layout['id']) ? ' selected' : '';
			$option = "<option value={$layout['id']}$selected>{$layout['title']}</option>";
			if (!in_array($layout['id'], $cur_layout_ids)){
				$add_options .= $option."\n";
			}
			else {
				$cur_options .= $option."\n";
			}
		}
		?>
		<fieldset>
			<legend>Add Task List</legend>
			<select name="add_layout_id">
				<?= $add_options; ?>
			</select>
			<input type="submit" value="Add" a0="action_add_task_list" />
		</fieldset>

		<fieldset>
			<legend>Select Task List</legend>
			<select id="select_layout" name="select_layout_id" a0href="<?= $this->href('tasks?aid='.$this->aid)?>">
				<?= $cur_options; ?>
			</select>
		</fieldset>

		<div class="clear"></div>

		<?php
		if(!empty($_GET['layout_id'])){
			$this->account_tasks->output();
		}
	}
	
}

?>