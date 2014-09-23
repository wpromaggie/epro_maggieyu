<?php

class mod_account_tasks_at_basic_branch extends mod_account_tasks_at_branch_node
{
	public function __construct($parent_id, $node_id, $node_type, $text) {
		$this->parent_id = $parent_id;
		$this->node_id = $node_id;
		$this->node_type = $node_type;
		$this->text = $text;
	}

	public function printBeforeChildren()
	{
		echo "<div id='branch_{$this->getNodeID()}' class='node branch {$this->getNodeType()}' node_type='{$this->getNodeType()}'>";
		
		if($this->node_type!='default_root')
		{
			echo "	<div class='title'>";
			echo "	<div class='icons'>";
			echo "		<img class='toggle_items expanded' src='".cgi::href('img/story_expanded.png')."' />";
			echo "		<img class='toggle_items collapsed' src='".cgi::href('img/story_collapsed.png')."' />";
			echo "	</div>";
			echo "	<div class='text'>{$this->getInnerText()}</div> <span class='count'>(0)</span>";
			echo "	<div class='edit_title_wrapper'><textarea class='title_input'>{$this->getInnerText()}</textarea></div>";
			
			echo "  <input type='submit' class='delete-section' value='Delete'/>";
			
			echo "  <div class='clear'></div>";
			echo "	</div>";
			
			echo "	<div class='item-list'>";
		}
	}
	
	public function printAfterChildren()
	{
		//add item row
		if($this->node_type!='default_root')
		{
			//close item list
			echo "	</div>";
			
			echo "	<div class='add item'>";
			echo "		<input type='submit' value='Add' class='add_task' />";
			echo "		<div class='edit_task_wrapper'>";
			echo "			<textarea class='add_input' placeholder='Add a task'></textarea>";
			echo "		</div>";
			echo "	</div>";
		}
		
		//close branch node
		echo "</div>";
	}
	
}
?>