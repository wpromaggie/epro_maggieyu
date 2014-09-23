<?php

class mod_service_tasks extends mod_service
{
	
	public function pre_output()
	{
		parent::pre_output();
		$this->display_default = 'layouts';

		// todo? this doesn't need to be a widget any more?
		$this->account_tasks = $this->register_widget('account_tasks', array('layout' => $_GET['layout'], 'dept' => $this->dept));
	}
	
	/*
	 * 
	 *  ACTION Methods
	 * 
	 */
	public function action_add_layout()
	{
		$this->account_tasks->add_layout();
	}
	
	public function action_add_section()
	{
		$this->account_tasks->add_section();
		//use redirect to prevent multiple adds and refresh
		cgi::redirect('service/tasks/edit/'.$this->dept.'?layout='.$_GET['layout']);
	}
	
	/*
	 * 
	 *  DISPLAY Methods
	 * 
	 */
	public function display_layouts()
	{
		$layouts = $this->account_tasks->get_layouts();
		$tbody = '<tbody>';
		foreach($layouts as $l){
			$user_rn = db::select_one("select realname from users where id = {$l['user_id']}");
			$tr  = '<tr>';
			$tr .= "<td>{$l['id']}</td>";
			$tr .= "<td><a href='".$this->href('edit/'.$this->dept.'?layout='.$l['id'])."'>{$l['title']}</a></td>";
			$tr .= "<td>$user_rn</td>";
			$tr .= "<td>".$l['dt']."</td>";
			$tr .= '</tr>';
			$tbody .= $tr;
		}
		$tbody .= '</tbody>';
		$table = "
			<table>
				<thead>
					<tr>
						<th>ID</th>
						<th>Title</th>
						<th>Created By</th>
						<th>Created</th>
					</tr>
				</thead>
				$tbody
			</table>
		";
	?>
		<h2>Select Layout</h2>
		
		<div id="new-layout">
			<input name="layout[title]" placeholder='Add a layout' value="" />
			<input type="submit" value="Add" a0="action_add_layout" />
		</div>
		
		<?php echo $table ?>
	<?php
	}
	
	public function display_edit()
	{
		echo "<a href='".$this->href($this->dept)."'>View All</a><br /><br />";
		$this->account_tasks->output();
	}
	
}
?>