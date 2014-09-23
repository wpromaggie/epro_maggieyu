<?php
class proposal
{
	protected $prospect;
	protected $terms;
	protected $layout;
	
	protected static $layouts = array(
	    'Agency Services',
	    'TeleVox',
	    'Dealers United'
	);

	function __construct($prospect)
	{
		$this->prospect = $prospect;
	}
	
	public static function get_layouts(){
		return self::$layouts;
	}
	
	public function get_layout(){
		return $this->layout;
	}

	public function build_contract()
	{
		
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
			
			$this->terms = db::select_one("SELECT text FROM contracts.prospect_terms_text WHERE prospect_id={$this->prospect->id}");
			if(!$this->terms){
				$this->terms = db::select_one("SELECT text FROM contracts.prospect_terms_text WHERE prospect_id=0 AND layout='{$this->prospect->layout}'");
			}
				
		} else {
			
			//Select branch nodes
			$branch_info = db::select("
				SELECT id, parent_id, child_order, node_type
				FROM contracts.default_nodes
				WHERE node_struct = 'branch' AND default_nodes.deleted=0 AND layout = '$this->layout'
			", 'ASSC', array('parent_id'));

			//Select leaf nodes
			$leaf_info = db::select("
				SELECT id, parent_id, child_order, node_type, node_text
				FROM contracts.default_nodes
				WHERE node_struct = 'leaf' AND default_nodes.deleted=0 AND layout = '$this->layout'
			", 'ASSC', array('parent_id'));
			
			//Create root parent node
			$root_node = $this->make_branch(0, 0, 'default_root');
			
			$this->terms = db::select_one("SELECT text FROM contracts.prospect_terms_text WHERE prospect_id=0");
			
			
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
	public function make_branch($parent_id, $node_id, $node_type)
	{
		return new basic_branch($parent_id, $node_id, $node_type);
	}

	
	/**
	 * Creates an appropriate leaf node for this SAP proposal. Override
	 * in a child of proposal to use different node classes.
	 */
	public function make_leaf($parent_id, $node_id, $node_type, $node_text)
	{
		return new basic_leaf($parent_id, $node_id, $node_type, $node_text);
	}
	

	/**
	 * Perform a bulk set of add, delete, edit, and move operations.
	 * @param array $ops_array an array of arrays that describes the operations to perform
	 */
	public function perform_operations($ops_array)
	{
		//Not using a foreach, because may need to substitute in 'add'
		$ops_array_length = count($ops_array);
		for ($i=0; $i<$ops_array_length; $i++) {
			$op = $ops_array[$i];

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
	protected function handle_extra_op($op)
	{
		feedback::add_error_msg("Unsupported Operation: {$op['op_type']}");
	}


	public function add_node($parent_id, $node_type, $node_order)
	{

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
				'deleted' => 0,
				'layout' => $this->layout
			));
		}

		//return new node's id
		return $node_struct . '_' . $node_id_num;
	}

	
	public function delete_node($node_id)
	{
		list($ns, $node_id) = node::decode_id($node_id);
		
		//Shift sibling orders
		list($node_order, $parent_id) = db::select_row("
			SELECT child_order, parent_id FROM contracts.prospect_nodes
			WHERE id = :id LIMIT 1
		", array('id' => $node_id));
		
		//Make sure the node was found
		if ($parent_id) {
			db::update(
				"contracts.prospect_nodes",
				array('child_order' => db::literal('child_order - 1')),
				"parent_id = :pid && child_order > :node_order",
				array('pid' => $parent_id, 'node_order' => $node_order)
			);

			//Delete related node text
			db::delete("contracts.prospect_node_text", "prospect_node_id = :nid", array('nid' => $node_id));

			//Delete the node
			db::delete("contracts.prospect_nodes", "id = :nid", array('nid' => $node_id));
		}
	}
	
	
	//Recursivly delete children from the passed node
	public function delete_node_children($node_id)
	{
		//Select the children
		$child_nodes = db::select("
			SELECT id 
			FROM contracts.prospect_nodes 
			WHERE parent_id = :pid
		", array('pid' => $node_id));
		foreach ($child_nodes as $node_id) {
			
			//Ignore moving the sibling order because they will be deleted as well
			
			//Delete related node text
			db::delete("contracts.prospect_node_text", "prospect_node_id = :nid", array('nid' => $node_id));

			//Delete the node
			db::delete("contracts.prospect_nodes", "id = :nid", array('nid' => $node_id));
			
			//Get recursive
			$this->delete_node_children($node_id);
		}
	}

	
	public function edit_node($node_id, $text)
	{
		list($ls, $node_id) = node::decode_id($node_id);
		//text gets escaped in lib_db
		$text = str_replace("\r\n", '', $text);
		db::insert_update('contracts.prospect_node_text', array('prospect_node_id'), array(
		    'prospect_node_id' => $node_id,
		    'text' => $text
		));
		db::exec("
			UPDATE contracts.prospect_nodes
			SET edited_text=1
			WHERE id = :nid
		", array('nid' => $node_id));
	}

	
	public function move_node($parent_id, $node_id, $old_order, $new_order)
	{
		list($ps, $parent_id) = node::decode_id($parent_id);
		list($cs, $node_id) = node::decode_id($node_id);
		$old_order = db::escape($old_order);
		$new_order = db::escape($new_order);
		
		//Close node child_order gaps
		$siblings = db::select("
			SELECT id 
			FROM contracts.prospect_nodes 
			WHERE parent_id = :pid
			ORDER BY child_order ASC
		", array('pid' => $parent_id));
		
		for ($i = 0; $i < count($siblings); $i++) {
			db::update(
				"contracts.prospect_nodes",
				array('child_order' => $i),
				"id = :id",
				array('id' => $siblings[$i])
			);
		}

		//Increment/decrement order for nodes affected by the move
		if ($old_order < $new_order) {
			db::exec("
				UPDATE contracts.prospect_nodes
				SET child_order = child_order - 1
				WHERE parent_id='$parent_id'
					AND child_order > $old_order AND child_order <= $new_order
			");
		}
		else {
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
	public function get_root_id()
	{
		return db::select_one("
			SELECT id
			FROM contracts.prospect_nodes
			WHERE parent_id=0 AND prospect_id = :pid
		", array('pid' => $this->prospect->id));
	}
	
	
	/**
	 *  Add a package to an existing proposal
	 */
	public function add_package($package_id)
	{
		if(!$this->prospect->add_package_vars($package_id)) return false;
		//Recursive add of package branches
		$this->insert_branch_children($package_id);
		return true;
	}
	
	
	/**
	 *  Remove a package to an existing proposal
	 */
	public function delete_package($package_id)
	{
		$this->prospect->delete_package_vars($package_id);
				
		//Find all default_nodes assigned to this package
		$pkg_nodes = db::select("
			SELECT prospect_nodes.id FROM contracts.prospect_nodes prospect_nodes
			INNER JOIN contracts.default_nodes_packages default_nodes_packages
				ON prospect_nodes.default_node_id = default_nodes_packages.default_node_id 
				AND default_nodes_packages.package_id = :package_id
			WHERE prospect_nodes.prospect_id = :prospect_id
		", array('package_id' => $package_id, 'prospect_id' => $this->prospect->id));

		foreach($pkg_nodes as $node_id){
			$this->delete_node($node_id);
			$this->delete_node_children($node_id);
		}
	}
	
	
	public function insert_branch_children($pkg_id=0, $default_parent_id=0)
	{
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
			WHERE
				default_nodes.parent_id = $default_parent_id AND 
				default_nodes.node_struct='leaf' AND
				($pks_cond) AND
				default_nodes.deleted=0 AND
				default_nodes.layout='{$this->prospect->layout}'
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
				default_nodes.node_struct='branch' AND ($pks_cond) AND default_nodes.deleted=0 AND default_nodes.layout='{$this->prospect->layout}'
			ORDER BY default_nodes.child_order ASC
		", 'ASSOC');
			
		if($branch_nodes){
			foreach($branch_nodes as $branch_node){
				$this->insert_node($branch_node, $default_parent_id);
				$this->insert_branch_children($pkg_id, $branch_node['id']);
			}
		}
	}
	
	
	public function insert_node($node, $default_parent_id)
	{
		
		//check if the node already exitsts... adding default nodes may inlude duplicates
		if (db::count_select("SELECT id FROM contracts.prospect_nodes WHERE default_node_id={$node['id']} AND prospect_id={$this->prospect->id} LIMIT 1")) {
			//this node exists...
			return;
		}
		
		//find the new prospect node's parent
		$parent_node_id = db::select_one("SELECT id FROM contracts.prospect_nodes WHERE default_node_id=$default_parent_id AND prospect_id={$this->prospect->id} LIMIT 1");
		
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
	public static function create_new_old($prospect_id, $prospect_packages)
	{
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
	public static function create_new($prospect_id, $layout)
	{
		
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
			WHERE parent_id=0 AND packages.id IS NULL AND default_nodes.deleted=0 AND layout='$layout'
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
		
		foreach ($root_branches as $node) {
			self::save_branch_node($node, $pkg_ids, $node_parent_id, $prospect_id);
		}
	}
	
	
	public static function save_branch_node($node, $pkg_ids, $node_parent_id, $prospect_id)
	{
		
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
		if ($pkg_ids!="") {
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
	
	
	public static function save_leaf_node($leaf_node, $node_parent_id, $prospect_id)
	{
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
	public static function join_node_package()
	{
		return "
			LEFT JOIN contracts.default_nodes_packages default_nodes_packages
				ON default_nodes.id = default_nodes_packages.default_node_id
			LEFT JOIN contracts.packages packages
				ON default_nodes_packages.package_id = packages.id
		";
	}

}



class proposal_default extends proposal
{
	//The package information map for default nodes (indexed by the default_node_id)
	private $node_packages;

	
	/**
	 * Constructor. This is always going to be the default SAP, so use the
	 * special default prospect.
	 */
	public function __construct($layout)
	{
		$this->prospect = new prospects(array('id' => 0));
		$this->node_packages = array();
		$this->layout = $layout;
	}

	
	/**
	 * Build up a copy of the default SAP, from the sap node database, including
	 * the packages associated with each of the default nodes.
	 * @return A tree of nodes representing the default SAP.
	 */
	public function build_contract()
	{
		
		//Get packages for the default nodes
		$node_packages = db::select("
			SELECT default_node_id, package_id
			FROM contracts.default_nodes_packages default_nodes_packages
			INNER JOIN contracts.packages packages
				ON default_nodes_packages.package_id = packages.id
			WHERE packages.deleted=0
		", 'ASSOC');
		
		foreach($node_packages as $node_package){
			if(empty($this->node_packages[$node_package['default_node_id']])){
				$this->node_packages[$node_package['default_node_id']] = $node_package['package_id'];
			} else {
				$this->node_packages[$node_package['default_node_id']] .= ",{$node_package['package_id']}";
			}
		}
		
		return parent::build_contract();
	}
	
	
	/**
	 * Creates an appropriate branch node for the default SAP proposal.
	 */
	public function make_branch($parent_id, $node_id, $node_type)
	{
		$node_package = (isset($this->node_packages[$node_id]))?$this->node_packages[$node_id]:0;
		return new default_branch($parent_id, $node_id, $node_type, $node_package);
	}

	
	/**
	 * Creates an appropriate leaf node for the default SAP proposal.
	 */
	public function make_leaf($parent_id, $node_id, $node_type, $node_text)
	{
		$node_package = (isset($this->node_packages[$node_id]))?$this->node_packages[$node_id]:0;
		return new default_leaf($parent_id, $node_id, $node_type, $node_package, $node_text);
	}
	
	
	/**
	 * Handles any extra operations that can be passed to "perform_operations()",
	 * but that aren't handled by the base proposal class. For the Default SAP,
	 * this is only the 'package' operation for now.
	 * @param array $op operation info
	 */
	protected function handle_extra_op($op)
	{
		if ($op['op_type'] == 'package') {
			$this->package_node($op['node_id'], $op['packages']);
		} else {
			parent::handle_extra_op($op);
		}
	}
	

	public function add_node($parent_id, $node_type, $node_order)
	{
		$new_id = parent::add_node($parent_id, $node_type, $node_order);

		//Need to give this default node a package
		$this->package_node($new_id, 'default');

		return $new_id;
	}
	

	public function delete_node($node_id, $reorder_siblings=TRUE)
	{
		list($ns, $node_id) = node::decode_id($node_id);
		
		list($node_order, $parent_id) = db::select_row("
			SELECT child_order, parent_id
			FROM contracts.default_nodes
			WHERE id = $node_id LIMIT 1
		");
		
		if($reorder_siblings){
			//Shift sibling (share parent_id) orders
			db::exec("
				UPDATE contracts.default_nodes
				SET child_order = child_order - 1
				WHERE parent_id='$parent_id' AND child_order > $node_order AND deleted=0
			");
		}

		//Delete the default node
		db::exec("
			UPDATE contracts.default_nodes
			SET deleted=1, child_order=NULL
			WHERE id=$node_id
			LIMIT 1
		");
		
		//Delete child nodes...
		$child_nodes = db::select("
			SELECT id
			FROM contracts.default_nodes
			WHERE parent_id=$node_id AND deleted=0
		");
		if($child_nodes){
			foreach($child_nodes as $child_node_id){
				//don't reorder siblings because they will all be deleted
				$this->delete_node($child_node_id, FALSE);
			}
		}
	}
	
	
	public function edit_node($node_id, $text)
	{
		list($ls, $node_id) = node::decode_id($node_id);
		$text = db::escape(str_replace("\r\n", '', $text));
		db::exec("
			UPDATE contracts.default_nodes
			SET node_text = '$text'
			WHERE id = $node_id
		");
	}
	
	
	public function move_node($parent_id, $node_id, $old_order, $new_order)
	{
		list($ps, $parent_id) = node::decode_id($parent_id);
		list($cs, $node_id) = node::decode_id($node_id);
		$old_order = db::escape($old_order);
		$new_order = db::escape($new_order);

		//Increment/decrement order for nodes affected by the move
		if ($old_order < $new_order) {
			db::exec("
				UPDATE contracts.default_nodes
				SET child_order = child_order - 1
				WHERE parent_id='$parent_id' AND child_order > $old_order AND child_order <= $new_order
					AND deleted=0
			");
		} else {
			db::exec("
				UPDATE contracts.default_nodes
				SET child_order = child_order + 1
				WHERE parent_id='$parent_id' AND child_order < $old_order AND child_order >= $new_order
					AND deleted=0
			");
		}

		//Set new order for moved node
		db::exec("
			UPDATE contracts.default_nodes
			SET child_order = $new_order
			WHERE id = $node_id
		");
	}

	public function package_node($node_id, $package_ids)
	{
		list($ns, $node_id) = node::decode_id($node_id);
		
		//Delete previous relations
		db::delete("contracts.default_nodes_packages", "default_node_id = :nid", array('nid' => $node_id));
		
		if(!empty($package_ids)){
			$package_ids = explode(',', $package_ids);
			foreach($package_ids as $package_id){
				db::insert("contracts.default_nodes_packages", array(
					'default_node_id' => $node_id,
					'package_id' => $package_id
				));
			}
		}
	}
	
}



class proposal_display extends proposal
{
	/**
	 * Creates an appropriate branch node for the default SAP proposal.
	 */
	public function make_branch($parent_id, $node_id, $node_type)
	{
		return new display_branch($parent_id, $node_id, $node_type);
	}

	/**
	 * Creates an appropriate leaf node for the default SAP proposal.
	 */
	public function make_leaf($parent_id, $node_id, $node_type, $node_text)
	{
		return new display_leaf($parent_id, $node_id, $node_type, $this->replace_var_text($node_text));
	}
	
	public function printTerms()
	{
		echo $this->replace_var_text($this->terms);
	}
	
	private function printAgencyServicesTable(){
		$table_vars = db::select("
			SELECT *
			FROM contracts.package_vars
			WHERE prospect_id={$this->prospect->id} AND order_table=1
			ORDER BY row_order ASC
		", 'ASSOC');
			
		$description_text = 'Description';
		$monthly_text = 'Monthly';
		$first_month_fee_text = 'First Month Fee';
		$total_text = '1st Month Total Due';

		$hide_monthly = false;
		$hide_first_month = false;
		$hide_summary = false;
		
		//hacks
		//
		//Mutual Minds LLC
		if($this->prospect->id==6300){
			$hide_monthly = true;
		} 
		//pelican water
		else if (in_array($this->prospect->id, array(6636))){
			$hide_first_month = true;
			$hide_summary = true;
		//CU Direct
		} else if ($this->prospect->id==6967){
			$first_month_fee_text = 'First Fee';
		}
			
		?>
			<div class="title-header">
				<span class="field">Order Details</span>
			</div>

			<table id="order-table">
				<thead>
					<tr>
						<th><?php echo $description_text ?></th>
						<?php if(!$hide_monthly){ ?><th><?php echo $monthly_text ?></th><?php } ?>
						<?php if(!$this->prospect->hide_total){ ?><th>Total</th><?php } ?>
						<?php if(!$hide_first_month){ ?><th><?php echo $first_month_fee_text ?></th><?php } ?>
					</tr>
				</thead>
				<tbody>
					<?php 
					$total = 0;
					foreach($table_vars as $var){
						$contract_length = $this->prospect->get_contract_length($var['package_id']);
						$total += $this->print_order_table_row($var, $contract_length, $hide_monthly, $hide_first_month);
					}
					?>
				</tbody>

				<? if (!$hide_summary){ ?>
				<tfoot>
					<tr>
						<td><?php echo $total_text ?></td>
						<?php if(!$hide_monthly){ ?><td></td><?php } ?>
						<?php if(!$this->prospect->hide_total){ ?><th></th><?php } ?>
						<td><?php echo util::format_dollars($total) ?></td>
					</tr>
				</tfoot>
				<? } ?>
			</table>
		<?php
	}
	
	private function printSmallBusinessTable(){
		$total = 0;
		$services = $this->prospect->get_services();
	?>
		<div class="title-header">
				<span class="field">Order Details</span>
		</div>

		<table id="order-table">
			<thead>
				<tr>
					<th>Description</th>
					<th>Setup</th>
					<th>Monthly</th>
					<th>First Month Cost</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$contract_length = 0;
				foreach($services as $service){
					//e($service);
					$setup_fee = $this->prospect->get_package_var_by_type($service['package_id'], 'setup_fee');
					$monthly_cost = $this->prospect->get_package_var_by_type($service['package_id'], 'monthly_cost');
					$discount = $this->prospect->get_package_var_by_type($service['package_id'], 'discount');

					$setup_fee_value = $setup_fee['value'];
					$monthly_cost_value = $monthly_cost['value'];

					$discount = $setup_fee['discount'] + $monthly_cost['discount'];

					$contract_length = $this->prospect->get_contract_length($service['package_id']);
				?>
				<tr<?php if(!empty($discount)) { echo " class='discount-strike'"; } ?>>
					<td><?php echo package::get_service_display($service['service'], $this->prospect->layout)?></td>
					<td><?php echo util::format_dollars($setup_fee_value) ?></td>
					<td><?php echo util::format_dollars($monthly_cost_value) ?></td>
					<td><?php echo util::format_dollars($setup_fee_value+$monthly_cost_value) ?></td>
				</tr>

				<?php
				if($discount){
					$setup_fee_value -= $setup_fee['discount'];
					$monthly_cost_value -= $monthly_cost['discount'];
				?>
				<tr>
					<td><?php echo package::get_service_display($service['service'], $this->prospect->layout)?> (Discounted Rate)</td>
					<td><?php echo util::format_dollars($setup_fee_value) ?></td>
					<td><?php echo util::format_dollars($monthly_cost_value) ?></td>
					<td><?php echo util::format_dollars($setup_fee_value+$monthly_cost_value) ?></td>
				</tr>
				<?php
				}
				$total += $setup_fee_value+$monthly_cost_value;
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<td>1st Month Total Due</td>
					<td></td>
					<?php if(!$this->prospect->hide_total){ ?><td></td><?php } ?>
					<td><?php echo util::format_dollars($total) ?></td>
				</tr>

				<? if($this->prospect->layout != 'Dealers United') { ?>
					<tr>
						<td>Contract Term</td>
						<td></td>
						<td></td>
						<td><?php echo $contract_length ?> Months</td>
					</tr>
				<? } ?>

			</tfoot>
		</table>
	<?php
	}
	
	public function printOrderTable()
	{
		if($this->prospect->layout == "Agency Services"){
			$this->printAgencyServicesTable();
		} else {
			$this->printSmallBusinessTable();
		}
	}
	
	private function replace_var_text($text)
	{
		preg_match_all("/\{(.*?)\}/", $text, $matches);
		$full_matches = $matches[0];
		for ($i = 0; $i < count($matches[0]); $i++)
		{
			$var_value = $this->prospect->get_var_by_key($full_matches[$i]);
			$text = str_replace($full_matches[$i], $var_value, $text);
		}	
		return $text;
	}
	
	private function print_order_table_row($var, $contract_length, $hide_monthly, $hide_first_month)
	{
		$first_month = $var['value'];
		$note = $var['note'];
		$description = empty($var['description'])?$var['name']:$var['description'];

		switch($var['type']){

			case 'split_pay':
				$monthly = "N/A";
				$total = $var['value']*2;
				break;
			
			case 'no_first':
				$monthly = util::format_dollars($var['value']);
				$total = $var['value']*($contract_length-1);
				$first_month = $var['value'] = 0;
				break;

			case 'other':
				$monthly = "N/A";
				$total = $var['value'];
				break;
			
			case 'setup_fee':
				$monthly = "N/A";
				$total = $var['value'];
				break;

			default:

				$monthly = util::format_dollars($var['value']);
				$total = $var['value']*$contract_length;
				break;
		}

		if (!empty($note)){
			$note = "<div class='note'>".$note."</div>";
		}

		?>

		<tr>
			<td><?php echo $description.$note ?></td>
			<?php if(!$hide_monthly){ ?><td><?php echo $monthly ?></td><?php } ?>
			<?php
				if ($this->prospect->hide_total){
					//do nothing
				}
				else {
					echo "<td>".util::format_dollars($total)."</td>";
				}
			?>
			<?php if(!$hide_first_month){ ?><td><?php echo util::format_dollars($first_month) ?></td><?php } ?>
		</tr>
		
		<?php
		return $var['order_table']?$var['value']:0;
	}
}
?>