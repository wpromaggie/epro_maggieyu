<?php
class mod_account_tasks_default_tasks extends mod_account_tasks_tasks {
	
	
	function __construct($layout_id=0)
	{
		$this->layout = new layouts(array('id' => $layout_id));
	}
	
	function build()
	{
			
		//Select branch nodes
		$branch_info = db::select("
			SELECT id, parent_id, child_order, type, text
			FROM account_tasks.default_nodes
			WHERE struct = 'branch' AND
				layout_id = {$this->layout->id}
		", 'ASSOC', array('parent_id'));

		//Select leaf nodes
		$leaf_info = db::select("
			SELECT id, parent_id, child_order, type, text
			FROM account_tasks.default_nodes
			WHERE struct = 'leaf' AND 
				layout_id = {$this->layout->id}
		", 'ASSOC', array('parent_id'));
		
		//Create root parent node
		$root_node = $this->make_branch(0, 0, 'default_root', '');
		
		//e($branch_info);
		$this->build_branches($root_node, $branch_info);
		
		//e($leaf_info);
		$this->build_leaves($root_node, $leaf_info);
		
		return $root_node;
	}
	
	public function make_branch($parent_id, $node_id, $node_type, $node_text)
	{
		return new at_default_branch($parent_id, $node_id, $node_type, $node_text);
	}
	
	public function make_leaf($parent_id, $node_id, $node_type, $node_text)
	{
		return new at_default_leaf($parent_id, $node_id, $node_type, $node_text);
	}
	
}

?>