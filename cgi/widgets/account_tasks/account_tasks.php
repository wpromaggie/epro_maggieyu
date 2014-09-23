<?php

class wid_account_tasks extends widget_base
{
	public $dept, $ac, $layout;
	private $is_default = false;
	
	function __construct($args = array())
	{
		util::load_rs('account.tasks');
		foreach ($this as $k => $v)
		{
			if (array_key_exists($k, $args))
			{
				if($k=='layout'){
					$this->layout = new layouts(array('id' => $args[$k]));
				} else {
					$this->$k = $args[$k];
				}
				
			}
		}
		if(empty($this->ac)){
			$this->is_default = true;
		}
	}
	
	public function output()
	{
		if(!$this->is_default){
			$tasks = new tasks($this->layout->id, $this->ac);
		} else {
			$tasks = new default_tasks($this->layout->id);
		}
		$root = $tasks->build();
        ?>
		<div id="account_tasks" ejo>
			
			<?php if($this->is_default){ ?>
				<h2 id="layout-title"><?php echo $this->layout->title ?></h2>
				<div id="edit-layout-title-wrapper">
					<textarea id="layout_<?php echo $this->layout->id ?>" class="layout_title_input" data-default="<?php echo $this->layout->title ?>"><?php echo $this->layout->title ?></textarea>
				</div>
			<?php } ?>

			<div id="layout-actions">
				<input type="submit" value="+ Add Section" id="add-section" a0="action_add_section" />
				<input type="submit" value="Collapse All" id="collapse-all" />
				<?php if($this->is_default){ ?>
					<input type="submit" value="Edit Title" id="edit-title" /> 
				<?php } else { ?>
					<input type="submit" value="Remove List" id="remove-list" a0="action_remove_layout" /> 
				<?php } ?>
			</div>

			<div id="edit-default-tasks" class="task-list">
				<?php $root->printNode(); ?>
			</div>
		</div>
		
	<?php 
	}
	
	public function attach_tasks_to_client($layout_id)
	{
		$task_list = new tasks($layout_id, $this->ac);
		$task_list->save_from_default();
	}
	
	public function add_section()
	{
		$this->layout->add_section($this->ac->id);
	}
	
	public function add_layout()
	{
		$id = db::insert('account_tasks.layouts', array(
		    'ac_type' => $this->dept,
		    'user_id' => user::$id,
		    'title' => $_POST['layout']['title'],
		    'dt' => date(util::DATE_TIME)
		));
		$url = 'service/tasks/edit/'.$this->dept.'?layout='.$id;
		cgi::redirect($url);
	}
	
	public function get_layouts()
	{
		//db::dbg();
		$layouts = db::select("select * from account_tasks.layouts where ac_type='{$this->dept}'", "ASSOC");
		return $layouts;
	}
	
	public function delete_layout()
	{
		$table  = 'account_tasks.';
		if($this->is_default){
			$table .= 'default_nodes';
			db::update($table, array('status' => 'deleted'), 'id='.$this->layout->id);
		} else {
			$table .= 'client_nodes';
			db::exec("DELETE FROM $table WHERE layout_id = {$this->layout->id}");
		}
		
	}
	
	/*
	 * 
	 *  AJAX Methods
	 * 
	 */
	public function ajax_save_layout_title()
	{
		list($l, $layout_id) = self::decode_id($_POST['id']);
		db::update('account_tasks.layouts', array('title' => $_POST['text']), "id=$layout_id");
	}
	
	public function ajax_save_node()
	{
		$this->update_node($_POST['id'],  array(
		    'text' => $_POST['text'],
		    'note' => $_POST['note']
		));
	}
	
	public function ajax_set_status()
	{
		$this->update_node($_POST['id'],  array(
		    'status' => $_POST['status']
		));
	}
	
	public function ajax_delete_node()
	{
		list($node_type, $node_id) = self::decode_id($_POST['id']);
		layouts::delete_node($node_id, $this->ac->id);
	}
	
	public function ajax_add_task()
	{
		list($parent_type, $parent_id) = self::decode_id($_POST['id']);
		$data = array(
		    'text' => $_POST['text'],
		    'layout_id' => $this->layout->id,
		    'parent_id' => $parent_id,
		    'child_order' => $_POST['child_order'],
		    'type' => 'item',
		    'struct' => 'leaf'
		);
		if($this->is_default){
			$node_id = db::insert('account_tasks.default_nodes', $data);
			if($node_id){
				$task = new at_default_leaf($parent_id, $node_id, 'item', $_POST['text']);
				$task->printNode();
			}
		} else {
			$data = array_merge($data, array(
			    'status' => 'incomplete',
			    'ac_id' => $this->ac->id
			));
			$node_id = db::insert('account_tasks.client_nodes', $data);
			if($node_id){
				$task = new at_basic_leaf($parent_id, $node_id, 'item', $_POST['text']);
				$task->printNode();
			}
		}
	}
	
	public function ajax_move_node()
	{
		//db::dbg();
		list($node_type, $node_id) = self::decode_id($_POST['id']);
		list($parent_type, $parent_id) = self::decode_id($_POST['parent_id']);
		
		$table  = 'account_tasks.';
		$table .= ($this->is_default) ? 'default_nodes' : 'client_nodes';
		
		if($_POST['start_parent_id']){
			
			//update node orders for the original branch
			db::exec("
				UPDATE $table
				SET child_order = child_order-1
				WHERE 
					parent_id = {$_POST['start_parent_id']} AND
					child_order > {$_POST['start_order']}
			");
			
			//update node orders for the new branch
			db::exec("
				UPDATE $table
				SET child_order = child_order+1
				WHERE 
					parent_id = $parent_id AND
					child_order >= {$_POST['stop_order']}
			");
					
		} else {
			
			if($_POST['start_order'] > $_POST['stop_order']){
			$bottom_node = ">= {$_POST['stop_order']}";
			$top_node = "< {$_POST['start_order']}";
			$child_order = "child_order+1";
			} else {
				$bottom_node = "> {$_POST['start_order']}";
				$top_node = "<= {$_POST['stop_order']}";
				$child_order = "child_order-1";
			}
		
			db::exec("
				UPDATE $table
				SET child_order = $child_order
				WHERE 
					parent_id = $parent_id AND
					child_order $bottom_node AND
					child_order $top_node
			");
			
		}
		
		
		
		
		db::exec("
			UPDATE $table
			SET child_order = {$_POST['stop_order']}, parent_id = $parent_id
			WHERE id = $node_id
		");
	}
	
	/*
	 * 
	 *  HELPER Methods
	 * 
	 */
	private function update_node($id, $data)
	{
		list($node_type, $node_id) = self::decode_id($id);
		$where = 'id='.$node_id;
		if($this->is_default){
			unset($data['note']);
			db::update('account_tasks.default_nodes', $data, $where);
		} else {
			db::update('account_tasks.client_nodes', $data, $where);
		}
	}
	
	private static function decode_id($id)
	{
		return explode('_', $id);
	}
	
}

?>