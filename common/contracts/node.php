<?php
require_once 'node_types.php';

/**
 * 
 */
abstract class node {
	
	protected $parent_id;
	protected $node_id;
	protected $node_type;

	public abstract function isLeafNode();
	public abstract function printNode();
	
	public function getParentID(){ return $this->parent_id; }
	public function getNodeID(){ return $this->node_id; }
	public function getNodeType(){ return $this->node_type; }
	
	static private $node_type_structure = array(
		'section' => 'branch',
		'col' => 'branch',
		'list' => 'branch',
		'li' => 'leaf',
		'par' => 'leaf',
		'title' => 'leaf',
		'title-header' => 'leaf',
		'title-month' => 'leaf',
	);

	public static function get_node_types() {
		return array_keys(self::$node_type_structure);
	}
	public static function get_node_structures() {
		return self::$node_type_structure;
	}
	public static function is_type_leaf($node_type) {
		return (self::$node_type_structure[$node_type] == 'leaf');
	}
	public static function decode_id($node_id) {
		if(is_numeric($node_id)) return array('', $node_id);
		$node_id = db::escape($node_id);
		return explode('_', $node_id);
	}
	public static function get_package_names($package_ids){
		if(!$package_ids) return '';
		$package_names = array();
		foreach(explode(',',$package_ids) as $package_id){
			$package_names[] = db::select_one('SELECT name FROM contracts.packages WHERE id='.$package_id);
		}
		return implode(',',$package_names);
	}
	
}
/**
 * 
 */
abstract class branch_node extends node {

	protected $child_nodes = array();
	
	public abstract function printBeforeChildren();
	public abstract function printAfterChildren();
	
	public function isLeafNode() { return false; }
	
	public function printNode() {
		$this->printBeforeChildren();
		ksort($this->child_nodes);
		foreach ($this->getChildNodes() as $child_node) {
			$child_node->printNode();
		}
		$this->printAfterChildren();
	}

	public function getChildNodes() {
		//ksort($this->child_nodes);
		return $this->child_nodes;
	}
	
	public function addChildNode(&$node, $order) {
		$this->child_nodes[$order] = $node;
	}
}

/**
 * 
 */
abstract class leaf_node extends node {
	
	protected $text;

	public function isLeafNode() { return true; }

	public function getInnerText() { return $this->text; }
}
?>