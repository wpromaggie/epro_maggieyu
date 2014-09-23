<?php
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

class display_branch extends branch_node {
	
	public function __construct($parent_id, $node_id, $node_type) {
		$this->parent_id = $parent_id;
		$this->node_id = $node_id;
		$this->node_type = $node_type;
	}
	
	public function printBeforeChildren() {
		switch($this->getNodeType()){
			case 'list':
				echo "<ul id='branch_{$this->getNodeID()}' class='{$this->getNodeType()}'>";
				break;
			default:
				echo "<div id='branch_{$this->getNodeID()}' class='{$this->getNodeType()}'>";
				break;
		}
	}
	public function printAfterChildren() {
		switch($this->getNodeType()){
			case 'list':
				echo "</ul>";
				break;
			case 'section':
				echo "</div>";
				echo "<div class='clear'></div>";
				break;
			default:
				echo "</div>";
				break;
		}
	}
	
}

class display_leaf extends leaf_node {
	
	public function __construct($parent_id, $node_id, $node_type, $node_text) {
		$this->parent_id = $parent_id;
		$this->node_id = $node_id;
		$this->node_type = $node_type;
		$this->text = $node_text;
	}
	
	public function printNode() {
		
		switch($this->getNodeType()){
			case 'par':
				echo "<p id='leaf_{$this->getNodeID()}' class='{$this->getNodeType()}'>";
				echo $this->getInnerText();
				echo "</p>";
				break;
			case 'title-header':
				echo "<div id='leaf_{$this->getNodeID()}' class='{$this->getNodeType()}'><span>";
				echo $this->getInnerText();
				echo "</span></div>";
				break;
			case 'title':
				echo "<div id='leaf_{$this->getNodeID()}' class='{$this->getNodeType()}'>";
				echo $this->getInnerText();
				echo "</div>";
				break;
			case 'li':
				echo "<li id='leaf_{$this->getNodeID()}' class='{$this->getNodeType()}'>";
				echo $this->getInnerText();
				echo "</li>";
				break;
			default:
				echo "<div id='leaf_{$this->getNodeID()}' class='{$this->getNodeType()}'>";
				echo $this->getInnerText();
				echo "</div>";
				break;
		}
		
	}
	
}

class basic_branch extends branch_node {

	public function __construct($parent_id, $node_id, $node_type) {
		$this->parent_id = $parent_id;
		$this->node_id = $node_id;
		$this->node_type = $node_type;
	}

	public function printBeforeChildren() {
		switch($this->getNodeType()){
			
		}
		echo "<div id='branch_{$this->getNodeID()}' class='node branch {$this->getNodeType()}' node_type='{$this->getNodeType()}'>";
		//echo $this->getNodeID() . ' ' . $this->getNodeType();
		echo " <div class='node_type'>".$this->getNodeType()."</div>";
		//echo $this->getNodeID();
		echo " <div class='clear'></div>";
	}
	public function printAfterChildren() {
		echo "</div>";
	}
	
}

class basic_leaf extends leaf_node {

	public function __construct($parent_id, $node_id, $node_type, $node_text) {
		$this->parent_id = $parent_id;
		$this->node_id = $node_id;
		$this->node_type = $node_type;
		$this->text = $node_text;
	}

	public function printNode() {
		echo "<div id='leaf_{$this->getNodeID()}' class='node leaf {$this->getNodeType()}' node_type='{$this->getNodeType()}'>";
		echo "	<div class='text'>{$this->getInnerText()}</div>";
		echo "</div>";
	}
	
}

class default_branch extends branch_node {

	private $node_package;

	public function __construct($parent_id, $node_id, $node_type, $node_package) {
		$this->parent_id = $parent_id;
		$this->node_id = $node_id;
		$this->node_type = $node_type;
		$this->node_package = $node_package;
	}

	public function printBeforeChildren() {
		echo "<div id='branch_{$this->getNodeID()}' class='node branch {$this->getNodeType()}' node_type='{$this->getNodeType()}' node_package='{$this->node_package}' >";
		echo "	<div class='package'>".$this->get_package_names($this->node_package)."</div>";
		//echo $this->getNodeID() . ' ' . $this->getNodeType();
		echo " <div class='node_type'>";
		echo $this->getNodeType()."</div>";
		echo " <div class='clear'></div>";
	}
	public function printAfterChildren() {
		echo "</div>";
	}
}

class default_leaf extends leaf_node {

	private $node_package;

	public function __construct($parent_id, $node_id, $node_type, $node_package, $node_text) {
		$this->parent_id = $parent_id;
		$this->node_id = $node_id;
		$this->node_type = $node_type;
		$this->node_package = $node_package;
		$this->text = $node_text;
	}

	public function printNode() {
		echo "<div id='leaf_{$this->getNodeID()}' class='node leaf {$this->getNodeType()}' node_type='{$this->getNodeType()}' node_package='{$this->node_package}' >";
		echo "	<div class='package'>".$this->get_package_names($this->node_package)."</div>";
		echo "	<div class='text'>{$this->getInnerText()}</div>";
		echo "</div>";
	}
}
?>