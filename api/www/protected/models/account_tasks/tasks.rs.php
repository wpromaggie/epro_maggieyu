<?php
class mod_account_tasks_tasks {
	
	protected $account;
	protected $layout;
	
	function __construct($layout_id=0, $account=array())
	{
		$this->account = $account;
		$this->layout = new layouts(array('id' => $layout_id));
	}
	
	function save_from_default()
	{
		//Select branch nodes
		$branch_info = db::select("
			SELECT *
			FROM account_tasks.default_nodes
			WHERE struct = 'branch' AND
				layout_id = {$this->layout->id}
		", 'ASSOC');
				
		foreach($branch_info as $branch){
			//save to clients table
			$branch_id = db::insert('account_tasks.client_nodes', array(
			    'layout_id' => $this->layout->id,
			    'parent_id' => 0,
			    'child_order' => $branch['child_order'],
			    'struct' => $branch['struct'],
			    'type' => $branch['type'],
			    'ac_id' => $this->account->id,
			    'text' => $branch['text']
			));
			
			//Select leaf nodes
			$leaf_info = db::select("
				SELECT *
				FROM account_tasks.default_nodes
				WHERE struct = 'leaf' AND
					layout_id = {$this->layout->id} AND
					parent_id = {$branch['id']}
			", 'ASSOC');
					
			foreach($leaf_info as $leaf){
				//save to clients table
				db::insert('account_tasks.client_nodes', array(
				    'layout_id' => $this->layout->id,
				    'parent_id' => $branch_id,
				    'child_order' => $leaf['child_order'],
				    'struct' => $leaf['struct'],
				    'type' => $leaf['type'],
				    'ac_id' => $this->account->id,
				    'text' => $leaf['text'],
				    'note' => '',
				    'status' => 'incomplete'
				));
			}
		}
	}
	
	function build()
	{
		//Select branch nodes
		$branch_info = db::select("
			SELECT id, parent_id, child_order, type, text
			FROM account_tasks.client_nodes
			WHERE struct = 'branch' AND
				layout_id = {$this->layout->id} AND
				ac_id = '{$this->account->id}'
		", 'ASSOC', array('parent_id'));

		//Select leaf nodes
		$leaf_info = db::select("
			SELECT id, parent_id, child_order, type, text, note, status
			FROM account_tasks.client_nodes
			WHERE struct = 'leaf' AND 
				layout_id = {$this->layout->id} AND
				ac_id = '{$this->account->id}'
		", 'ASSOC', array('parent_id'));
		
		//Create root parent node
		$root_node = $this->make_branch(0, 0, 'default_root', '');
		
		// e($branch_info);
		$this->build_branches($root_node, $branch_info);
		
		// e($leaf_info);
		$this->build_leaves($root_node, $leaf_info);
		
		return $root_node;
	}
	
	protected function build_branches(&$parent_node, &$branch_info)
	{
		$parent_id = $parent_node->getNodeID();
		$children_info = $branch_info[$parent_id];
		if (!$children_info) {
			return;
		}

		foreach ($children_info as $child_info) {
			//Create child node and add to parent
			$child_node = $this->make_branch($parent_node, $child_info['id'], $child_info['type'], $child_info['text']);
			$parent_node->addChildNode($child_node, $child_info['child_order']);

			//Recur
			self::build_branches($child_node, $branch_info);
		}
	}
	
	protected function build_leaves(&$parent_node, &$leaf_info)
	{
		//Recur to children FIRST, so that getChildNodes only returns branch nodes
		$branch_children = $parent_node->getChildNodes();
		foreach ($branch_children as $branch_child) {
			self::build_leaves($branch_child, $leaf_info);
		}

		//Done with recursion, add on the leaf-child ("hippie") nodes
		$parent_id = $parent_node->getNodeID();
		$children_info = $leaf_info[$parent_id];
		if (!$children_info) {
			//No leaf nodes for this branch node
			return;
		}
		foreach ($children_info as $child_info) {
			//e($child_info);
			$text = $child_info['text'];
			
			//Build the leaf and add to the parent
			$child_node = $this->make_leaf($parent_id, $child_info['id'], $child_info['type'], $text, $child_info['note'], $child_info['status']);
			$parent_node->addChildNode($child_node, $child_info['child_order']);
		}
	}
	
	public function make_branch($parent_id, $node_id, $node_type, $node_text)
	{
		return new at_basic_branch($parent_id, $node_id, $node_type, $node_text);
	}
	
	public function make_leaf($parent_id, $node_id, $node_type, $node_text, $note, $status)
	{
		return new at_basic_leaf($parent_id, $node_id, $node_type, $node_text, $note, $status);
	}
	
}

?>