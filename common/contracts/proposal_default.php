<?php
require_once 'proposal.php';

/**
 * Class and functions for dealing with the default SAP text.
 */
class proposal_default extends proposal
{
	//The package information map for default nodes (indexed by the default_node_id)
	private $node_packages;

	/**
	 * Constructor. This is always going to be the default SAP, so use the
	 * special default prospect.
	 */
	public function __construct() {
		$this->prospect = new prospect(0);
		$this->node_packages = array();
	}

	/**
	 * Build up a copy of the default SAP, from the sap node database, including
	 * the packages associated with each of the default nodes.
	 * @return A tree of nodes representing the default SAP.
	 */
	public function build_contract() {
		
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
	public function make_branch($parent_id, $node_id, $node_type) {
		$node_package = (isset($this->node_packages[$node_id]))?$this->node_packages[$node_id]:0;
		return new default_branch($parent_id, $node_id, $node_type, $node_package);
	}

	/**
	 * Creates an appropriate leaf node for the default SAP proposal.
	 */
	public function make_leaf($parent_id, $node_id, $node_type, $node_text) {
		$node_package = (isset($this->node_packages[$node_id]))?$this->node_packages[$node_id]:0;
		return new default_leaf($parent_id, $node_id, $node_type, $node_package, $node_text);
	}
	
	/**
	 * Handles any extra operations that can be passed to "perform_operations()",
	 * but that aren't handled by the base proposal class. For the Default SAP,
	 * this is only the 'package' operation for now.
	 * @param array $op operation info
	 */
	protected function handle_extra_op($op) {
		if ($op['op_type'] == 'package') {
			$this->package_node($op['node_id'], $op['packages']);
		} else {
			parent::handle_extra_op($op);
		}
	}

	public function add_node($parent_id, $node_type, $node_order) {
		$new_id = parent::add_node($parent_id, $node_type, $node_order);

		//Need to give this default node a package
		$this->package_node($new_id, 'default');

		return $new_id;
	}

	public function delete_node($node_id, $reorder_siblings=TRUE) {
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
	
	public function edit_node($node_id, $text) {

		list($ls, $node_id) = node::decode_id($node_id);
		$text = db::escape($text);

		db::exec("
			UPDATE contracts.default_nodes
			SET node_text = '$text'
			WHERE id = $node_id
		");
	}
	
	public function move_node($parent_id, $node_id, $old_order, $new_order) {
		
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

	public function package_node($node_id, $package_ids) {
		list($ns, $node_id) = node::decode_id($node_id);
		
		//Delete previous relations
		db::exec("
			DELETE FROM contracts.default_nodes_packages 
			WHERE default_node_id = $node_id
		");
		
		$package_ids = explode(',', $package_ids);
		foreach($package_ids as $package_id){
			db::insert("contracts.default_nodes_packages", array(
				'default_node_id' => $node_id,
				'package_id' => $package_id
			));
		}
	}
	
}
?>
