<?php
abstract class mod_account_tasks_at_branch_node extends mod_account_tasks_at_node
{
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
?>