<?php
require_once 'node.php';

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