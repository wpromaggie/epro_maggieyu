<?php
require_once 'node.php';
//require_once 'prospect.php';

/**
 * All the classes and methods that deal with individual instances of SAPs.
 */
class proposal
{
	//The prospect associated with this contract.
	protected $prospect;

	/**
	 * Constructor. Used to initialize the prospect for this contract, but does not
	 * build up a contract tree for this contract.
	 * @param prospect $prospect The id of the prospect for this SAP.
	 */
	function __construct($prospect) {
		$this->prospect = $prospect;
	}

	/**
	 * Build up a copy of the this contract proposal.
	 * @return A tree of nodes representing this SAP.
	 */
	public function build_contract() {
		
		if($this->prospect->id){
			
			//Select branch nodes
			$branch_info = db::select("
				SELECT id, parent_id, child_order, node_type
				FROM contracts.prospect_nodes
				WHERE prospect_id = {$this->prospect->id} AND node_struct = 'branch' AND parent_id <> 0
			", 'ASSOC', array('parent_id'));

			//Select leaf nodes
			$leaf_info = db::select("
				SELECT prospect_nodes.id, prospect_nodes.parent_id, prospect_nodes.child_order, prospect_nodes.node_type, default_nodes.node_text AS default_text, prospect_node_text.text AS revised_text
				FROM contracts.prospect_nodes prospect_nodes
				LEFT JOIN contracts.default_nodes default_nodes
					ON prospect_nodes.default_node_id = default_nodes.id
				LEFT JOIN contracts.prospect_node_text prospect_node_text
					ON prospect_nodes.edited_text=1 AND 
					prospect_nodes.id = prospect_node_text.prospect_node_id
				WHERE prospect_nodes.prospect_id = {$this->prospect->id} AND prospect_nodes.node_struct = 'leaf'
			", 'ASSOC', array('parent_id'));
				
			//Create root parent node
			$root_node = $this->make_branch(0, $this->get_root_id(), 'prospect_root');
				
		} else {
			
			//Select branch nodes
			$branch_info = db::select("
				SELECT id, parent_id, child_order, node_type
				FROM contracts.default_nodes
				WHERE node_struct = 'branch' AND default_nodes.deleted=0
			", 'ASSC', array('parent_id'));

			//Select leaf nodes
			$leaf_info = db::select("
				SELECT id, parent_id, child_order, node_type, node_text
				FROM contracts.default_nodes
				WHERE node_struct = 'leaf' AND default_nodes.deleted=0
			", 'ASSC', array('parent_id'));
			
			//Create root parent node
			$root_node = $this->make_branch(0, 0, 'default_root');
			
		}

		//Build SAP branch structure
		$this->build_contract_branches($root_node, $branch_info);

		//Add leaf nodes to branches
		$this->build_contract_leaves($root_node, $leaf_info);

		//return root
		return $root_node;
	}
	
	/**
	 * MAKE ME protected final, I'M A RECURSIVE HELPER FOR build_sap().
	 * @param array $parent_node Node to add child nodes to.
	 * @param array $branch_info Info on all branch nodes.
	 */
	protected final function build_contract_branches(&$parent_node, &$branch_info)
	{
		$parent_id = $parent_node->getNodeID();
		$children_info = $branch_info[$parent_id];

		if (!$children_info) {
			//Done recurring
			return;
		}

		foreach ($children_info as $child_info) {
			//Create child node and add to parent
			$child_node = $this->make_branch($parent_id, $child_info['id'], $child_info['node_type']);
			$parent_node->addChildNode($child_node, $child_info['child_order']);

			//Recur
			self::build_contract_branches($child_node, $branch_info);
		}
	}

	/**
	 * MUST BE CALLED IMMEDIATELY AFTER build_sap_branches().
	 * MAKE ME protected final, I'M A RECURSIVE HELPER FOR build_sap().
	 * @param array $parent_node Node to add leaves to.
	 * @param array $leaf_info Info on all available leaves.
	 */
	protected final function build_contract_leaves(&$parent_node, &$leaf_info)
	{
		//Recur to children FIRST, so that getChildNodes only returns branch nodes
		$branch_children = $parent_node->getChildNodes();
		foreach ($branch_children as $branch_child) {
			self::build_contract_leaves($branch_child, $leaf_info);
		}

		//Done with recursion, add on the leaf-child ("hippie") nodes
		$parent_id = $parent_node->getNodeID();
		$children_info = $leaf_info[$parent_id];
		if (!$children_info) {
			//No leaf nodes for this branch node
			return;
		}
		foreach ($children_info as $child_info) {
			
			//Default node text for default proposal
			if(isset($child_info['node_text'])){
				$text = $child_info['node_text'];
			//Edited node text
			} else if(!empty($child_info['revised_text'])){
				$text = $child_info['revised_text'];
			//Unedited node text
			} else {
				$text = $child_info['default_text'];
			}
			
			//Build the leaf and add to the parent
			$child_node = $this->make_leaf($parent_id, $child_info['id'], $child_info['node_type'], $text);
			$parent_node->addChildNode($child_node, $child_info['child_order']);
		}
	}

	/**
	 * Creates an appropriate branch node for this SAP proposal. Override
	 * in a child of proposal to use different node classes.
	 */
	public function make_branch($parent_id, $node_id, $node_type) {
		return new basic_branch($parent_id, $node_id, $node_type);
	}

	/**
	 * Creates an appropriate leaf node for this SAP proposal. Override
	 * in a child of proposal to use different node classes.
	 */
	public function make_leaf($parent_id, $node_id, $node_type, $node_text) {
		return new basic_leaf($parent_id, $node_id, $node_type, $node_text);
	}
	

	/**
	 * Perform a bulk set of add, delete, edit, and move operations.
	 * @param array $ops_array an array of arrays that describes the operations to perform
	 */
	public function perform_operations($ops_array) {

		//Not using a foreach, because may need to substitute in 'add'
		$ops_array_length = count($ops_array);
		for ($i=0; $i<$ops_array_length; $i++) {
			$op = $ops_array[$i];

			//echo "<br />";
			//print_r($op);
			//echo "<br />";

			switch ($op['op_type']) {
				case 'add':
					//Add node, then get new and placeholder (old) node ids
					$new_node_id = $this->add_node($op['parent_id'], $op['node_type'], $op['node_order']);
					$old_node_id = $op['node_id'];

					//Substitute the new node id for the placeholder id in upcoming ops
					for ($j=$i+1; $j<$ops_array_length; $j++) {
						$future_op = &$ops_array[$j];
						if ($future_op['node_id'] == $old_node_id) {
							$future_op['node_id'] = $new_node_id;
						}
						if (isset($future_op['parent_id']) && $future_op['parent_id'] == $old_node_id) {
							$future_op['parent_id'] = $new_node_id;
						}
					}
					break;
				case 'delete':
					$this->delete_node($op['node_id']);
					break;
				case 'edit':
					$this->edit_node($op['node_id'], $op['text']);
					break;
				case 'move':
					$this->move_node($op['parent_id'], $op['node_id'], $op['start_order'], $op['stop_order']);
					break;
				default:
					$this->handle_extra_op($op);
					break;
			}
		}

	}

	/**
	 * Handles operations passed to "perform_operations()" that don't exist in this base proposal.
	 * Can be used by child classes to handle additional node operations.
	 * @param array $op the operation that wasn't otherwise handled by perform_operations()
	 */
	protected function handle_extra_op($op) {
		feedback::add_error_msg("Unsupported Operation: {$op['op_type']}");
	}


	public function add_node($parent_id, $node_type, $node_order) {

		$node_type = db::escape($node_type);
		$node_order = db::escape($node_order);
		list($ps, $parent_id) = node::decode_id($parent_id);

		//Get node details
		if (node::is_type_leaf($node_type)) {
			$node_struct = 'leaf';
			$node_text = "'new_$node_type'";
		} else {
			$node_struct = 'branch';
			$node_text = 'NULL';
		}

		//Add node
		if($this->prospect->id){
			$node_id_num = db::insert("contracts.prospect_nodes", array(
				'prospect_id' => $this->prospect->id,
				'parent_id' => $parent_id,
				'child_order' => $node_order,
				'node_struct' => $node_struct,
				'node_type' => $node_type
			));
		} else {
			$node_id_num = db::insert("contracts.default_nodes", array(
				'parent_id' => $parent_id,
				'child_order' => $node_order,
				'node_struct' => $node_struct,
				'node_type' => $node_type,
				'node_text' => $node_text,
				'deleted' => 0
			));
		}

		//return new node's id
		return $node_struct . '_' . $node_id_num;
	}

	public function delete_node($node_id) {
		list($ns, $node_id) = node::decode_id($node_id);
		
		//Shift sibling orders
		list($node_order, $parent_id) = db::select_row("
			SELECT child_order, parent_id FROM contracts.prospect_nodes
			WHERE id = $node_id LIMIT 1
		");
		
		//Make sure the node was found
		if($parent_id){
			db::exec("
				UPDATE contracts.prospect_nodes
				SET child_order = child_order - 1
				WHERE parent_id='$parent_id'
					AND child_order > $node_order
			");

			//Delete related node text
			db::exec("
				DELETE FROM contracts.prospect_node_text
				WHERE prospect_node_id = $node_id
				LIMIT 1
			");

			//Delete the node
			db::exec("
				DELETE FROM contracts.prospect_nodes
				WHERE id = $node_id
				LIMIT 1
			");
		}
	}
	
	//Recursivly delete children from the passed node
	public function delete_node_children($node_id) {
		//Select the children
		$child_nodes = db::select("
			SELECT id 
			FROM contracts.prospect_nodes 
			WHERE parent_id=$node_id
		");
		foreach($child_nodes as $node_id){
			
			//Ignore moving the sibling order because they will be deleted as well
			
			//Delete related node text
			db::delete("
				DELETE FROM contracts.prospect_node_text
				WHERE prospect_node_id = $node_id
				LIMIT 1
			");

			//Delete the node
			db::exec("
				DELETE FROM contracts.prospect_nodes
				WHERE id = $node_id
				LIMIT 1
			");
			
			//Get recursive
			$this->delete_node_children($node_id);
		}
	}

	public function edit_node($node_id, $text) {
		list($ls, $node_id) = node::decode_id($node_id);
		$text = db::escape($text);
		db::insert_update('contracts.prospect_node_text', array('prospect_node_id'), array(
		    'prospect_node_id' => $node_id,
		    'text' => $text
		));
		db::exec("UPDATE contracts.prospect_nodes SET edited_text=1 WHERE id=$node_id");
	}

	public function move_node($parent_id, $node_id, $old_order, $new_order) {
		list($ps, $parent_id) = node::decode_id($parent_id);
		list($cs, $node_id) = node::decode_id($node_id);
		$old_order = db::escape($old_order);
		$new_order = db::escape($new_order);
		
		//Close node child_order gaps
		$siblings = db::select("
			SELECT id 
			FROM contracts.prospect_nodes 
			WHERE parent_id='$parent_id'
			ORDER BY child_order ASC
		");
		
		for($i=0;$i<count($siblings);$i++){
			db::exec("
				UPDATE contracts.prospect_nodes
				SET child_order=$i
				WHERE id={$siblings[$i]}
				LIMIT 1
			");
		}

		//Increment/decrement order for nodes affected by the move
		if ($old_order < $new_order) {
			db::exec("
				UPDATE contracts.prospect_nodes
				SET child_order = child_order - 1
				WHERE parent_id='$parent_id'
					AND child_order > $old_order AND child_order <= $new_order
			");
		} else {
			db::exec("
				UPDATE contracts.prospect_nodes
				SET child_order = child_order + 1
				WHERE parent_id='$parent_id'
					AND child_order < $old_order AND child_order >= $new_order
			");
		}

		//Set new order for moved node
		db::exec("
			UPDATE contracts.prospect_nodes
			SET child_order = $new_order
			WHERE id = $node_id
		");
	}
	
	/**
	 * Returns the root prospect node id
	 */
	public function get_root_id(){
		return db::select_one("
			SELECT id
			FROM contracts.prospect_nodes
			WHERE parent_id=0 AND prospect_id=".$this->prospect->id."
			LIMIT 1
		");
	}
	
	/**
	 *  Add a package to an existing proposal
	 */
	public function add_package($package_id) {
		if(!$this->prospect->add_package_vars($package_id)) return false;
		//Recursive add of package branches
		$this->insert_branch_children($package_id);
		return true;
	}
	
	/**
	 *  Remove a package to an existing proposal
	 */
	public function delete_package($package_id) {
		//db::dbg();
		$this->prospect->delete_package_vars($package_id);
				
		//Find all default_nodes assigned to this package
		$pkg_nodes = db::select("
			SELECT prospect_nodes.id FROM contracts.prospect_nodes prospect_nodes
			INNER JOIN contracts.default_nodes_packages default_nodes_packages
				ON prospect_nodes.default_node_id = default_nodes_packages.default_node_id 
				AND default_nodes_packages.package_id = $package_id
			WHERE prospect_nodes.prospect_id=".$this->prospect->id
		);		

		foreach($pkg_nodes as $node_id){
			$this->delete_node($node_id);
			$this->delete_node_children($node_id);
		}
	}
	
	public function insert_branch_children($pkg_id=0, $default_parent_id=0){
		$select_clause = "
			SELECT DISTINCT default_nodes.id, default_nodes.parent_id, default_nodes.child_order, default_nodes.node_struct, 
			default_nodes.node_type, default_nodes.node_text
		";
		
		//Make sure to include nodes with no related package(DEFAULT), they may be children of package branch nodes...
		$pks_cond = "packages.id IS NULL";
		if($pkg_id){
			$pks_cond .= " OR packages.id=$pkg_id";
		}
		
		//Find and save the leaf nodes
		$leaf_nodes = db::select("
			$select_clause
			FROM contracts.default_nodes default_nodes".
			self::join_node_package()."
			WHERE default_nodes.parent_id=$default_parent_id AND 
				default_nodes.node_struct='leaf' AND ($pks_cond) AND default_nodes.deleted=0
			ORDER BY default_nodes.child_order ASC
		", 'ASSOC');
			
		if($leaf_nodes){
			foreach($leaf_nodes as $leaf_node){
				$this->insert_node($leaf_node, $default_parent_id);
			}
		}
		
		//Find and save the child branch nodes
		$branch_nodes = db::select("
			$select_clause
			FROM contracts.default_nodes default_nodes".
			self::join_node_package()."
			WHERE default_nodes.parent_id=$default_parent_id AND 
				default_nodes.node_struct='branch' AND ($pks_cond) AND default_nodes.deleted=0
			ORDER BY default_nodes.child_order ASC
		", 'ASSOC');
			
		if($branch_nodes){
			foreach($branch_nodes as $branch_node){
				$this->insert_node($branch_node, $default_parent_id);
				$this->insert_branch_children($pkg_id, $branch_node['id']);
			}
		}
	}
	
	public function insert_node($node, $default_parent_id){
		
		//check if the node already exitsts... adding default nodes may inlude duplicates
		if(db::count_select("SELECT id FROM contracts.prospect_nodes WHERE default_node_id={$node['id']} LIMIT 1")){
			//this node exists...
			return;
		}
		
		//find the new prospect node's parent
		$parent_node_id = db::select_one("SELECT id FROM contracts.prospect_nodes WHERE default_node_id=$default_parent_id LIMIT 1");
		
		$child_order = $node['child_order'];
		//if something is in the way, loop to the end of the siblings
		while(db::select_row("SELECT * FROM contracts.prospect_nodes WHERE parent_id=$parent_node_id AND child_order=$child_order LIMIT 1")){
			$child_order++;
		}
		
		db::insert("contracts.prospect_nodes", array(
			'parent_id' => $parent_node_id,
			'child_order' => $child_order,
			'node_struct' => $node['node_struct'],
			'node_type' => $node['node_type'],
			'prospect_id' => $this->prospect->id,
			'default_node_id' => $node['id']
		));
	}

	/**
	 * Creates a new SAP proposal from the default, and puts it in the database.
	 * @param unsigned int $prospect_id The prospect the new SAP is for.
	 */
	public static function create_new_old($prospect_id, $prospect_packages) {
		
		//Check if prospect_id already exists for an sap proposal
		$node_count = db::select_one("SELECT COUNT(*) FROM contracts.prospect_nodes WHERE prospect_id=$prospect_id");
		if ($node_count > 0) {
			exit("Contract already exists for prospect: $prospect_id");
		}
		
		$pkg_ids = implode(',', $prospect_packages);
		$pks_cond = "packages.id IS NULL";
		if($pkg_ids!=""){
			$pks_cond .= " OR packages.id IN ($pkg_ids)";
		}
		
		//Build the prospect nodes from the default nodes table
		//Start with the root (parent_id = 0)
		$root_branches = db::select("
			SELECT DISTINCT default_nodes.id, default_nodes.parent_id, default_nodes.child_order, default_nodes.node_struct, 
			default_nodes.node_type, default_nodes.node_text
			FROM contracts.default_nodes".
			self::join_node_package()."
			WHERE parent_id=0 AND ($pks_cond) AND default_nodes.deleted=0
		", 'ASSOC');
		
		//Create a root node to build the new contract from (special case where default_node_id = 0)
		$node_parent_id = db::insert("contracts.prospect_nodes", array(
			'parent_id' => 0,
			'child_order' => 0,
			'node_struct' => 'branch',
			'node_type' => 'section',
			'prospect_id' => $prospect_id,
			'default_node_id' => 0
		));

		foreach($root_branches as $node){
			self::save_branch_node($node, $pkg_ids, $node_parent_id, $prospect_id);
		}
	}
	
	/**
	 * Creates a new contract from the default, and puts it in the database.
	 * @param unsigned int $prospect_id The prospect the new contract is for.
	 */
	public static function create_new($prospect_id) {
		
		//Check if prospect_id already exists for an sap proposal
		$node_count = db::select_one("SELECT COUNT(*) FROM contracts.prospect_nodes WHERE prospect_id=$prospect_id");
		if ($node_count > 0) {
			exit("Contract already exists for prospect: $prospect_id");
		}
		
		//Build the prospect nodes from the default nodes table
		//Start with the root (parent_id = 0)
		$root_branches = db::select("
			SELECT DISTINCT default_nodes.id, default_nodes.parent_id, default_nodes.child_order, default_nodes.node_struct, 
			default_nodes.node_type, default_nodes.node_text
			FROM contracts.default_nodes".
			self::join_node_package()."
			WHERE parent_id=0 AND packages.id IS NULL AND default_nodes.deleted=0
		", 'ASSOC');
		
		//Create a root node to build the new contract from (special case where default_node_id = 0)
		$node_parent_id = db::insert("contracts.prospect_nodes", array(
			'parent_id' => 0,
			'child_order' => 0,
			'node_struct' => 'branch',
			'node_type' => 'section',
			'prospect_id' => $prospect_id,
			'default_node_id' => 0
		));
		
		foreach($root_branches as $node){
			self::save_branch_node($node, $pkg_ids, $node_parent_id, $prospect_id);
		}
		
	}
	
	public static function save_branch_node($node, $pkg_ids, $node_parent_id, $prospect_id){
		
		$node_parent_id = db::insert("contracts.prospect_nodes", array(
			'parent_id' => $node_parent_id,
			'child_order' => $node['child_order'],
			'node_struct' => $node['node_struct'],
			'node_type' => $node['node_type'],
			'prospect_id' => $prospect_id,
			'default_node_id' => $node['id']
		));
			
		$select_clause = "
			SELECT DISTINCT default_nodes.id, default_nodes.parent_id, default_nodes.child_order, default_nodes.node_struct, 
			default_nodes.node_type, default_nodes.node_text
		";
		
		$pks_cond = "packages.id IS NULL";
		if($pkg_ids!=""){
			$pks_cond .= " OR packages.id IN ($pkg_ids)";
		}
		
		//Find and save the leaf nodes
		$leaf_nodes = db::select("
			$select_clause
			FROM contracts.default_nodes default_nodes".
			self::join_node_package().
			"WHERE default_nodes.parent_id={$node['id']} AND 
				default_nodes.node_struct='leaf' AND
				($pks_cond) AND default_nodes.deleted=0
		", 'ASSOC');
			
			
		if($leaf_nodes){
			foreach($leaf_nodes as $leaf_node){
				self::save_leaf_node($leaf_node, $node_parent_id, $prospect_id);
			}
		}
		
		//Find and save the child branch nodes
		$branch_nodes = db::select("
			$select_clause
			FROM contracts.default_nodes default_nodes".
			self::join_node_package()."
			WHERE default_nodes.parent_id={$node['id']} AND 
				default_nodes.node_struct='branch' AND
				($pks_cond) AND default_nodes.deleted=0
		", 'ASSOC');
			
		if($branch_nodes){
			foreach($branch_nodes as $branch_node){
				self::save_branch_node($branch_node, $pkg_ids, $node_parent_id, $prospect_id);
			}
		}
	}
	
	public static function save_leaf_node($leaf_node, $node_parent_id, $prospect_id){
		db::insert("contracts.prospect_nodes", array(
			'parent_id' => $node_parent_id,
			'child_order' => $leaf_node['child_order'],
			'node_struct' => $leaf_node['node_struct'],
			'node_type' => $leaf_node['node_type'],
			'prospect_id' => $prospect_id,
			'default_node_id' => $leaf_node['id']
		));
	}
	
	/**
	 * Returns a commonly used sql clause joining packages to default nodes
	 * 
	 */
	public static function join_node_package(){
		return "
			LEFT JOIN contracts.default_nodes_packages default_nodes_packages
				ON default_nodes.id = default_nodes_packages.default_node_id
			LEFT JOIN contracts.packages packages
				ON default_nodes_packages.package_id = packages.id
		";
	}
}
?>
