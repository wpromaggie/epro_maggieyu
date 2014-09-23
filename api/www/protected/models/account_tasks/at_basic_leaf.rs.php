<?php

class mod_account_tasks_at_basic_leaf extends mod_account_tasks_at_leaf_node 
{
	public function __construct($parent_id, $node_id, $node_type, $text, $note='', $status='') {
		$this->parent_id = $parent_id;
		$this->node_id = $node_id;
		$this->node_type = $node_type;
		$this->text = $text;
		$this->note = $note;
		$this->status = $status;
	}

	public function printNode() {
		$class = "node leaf {$this->getNodeType()}";
		if($this->status=="complete"){
			$class .= ' complete';
		}
		echo "<div id='leaf_{$this->getNodeID()}' class='$class' node_type='{$this->getNodeType()}'>";
		
		echo " <input type='submit' value='Save' class='save_task' />";
		
		echo "  <div class='actions'>";
		echo "		<a href='#' class='edit'>edit</a>";
		echo "		<a href='#' class='delete'>delete</a>";
		echo "	</div>";
		
		echo " <input class='checkbox' type='checkbox'";
		if($this->status == 'complete'){
			echo " checked=checked";
		}
		echo " />";
		
		echo "  <div class='display-text'>";
		echo "	<div class='text'>{$this->getInnerText()}</div>";
		echo "  <div class='note'>{$this->getInnerNote()}</div>";
		echo "  </div>";
		
		echo "	<div class='edit_task_wrapper'>";
		
		echo "		<textarea class='edit_input'>".$this->getInnerText()."</textarea>";
		echo "		<textarea class='edit_input_note' placeholder='notes'>".$this->note."</textarea>";
		
		echo "	</div>";
		
		echo "<div class='clear'></div>";

		echo "</div>";
	}
	
}

?>