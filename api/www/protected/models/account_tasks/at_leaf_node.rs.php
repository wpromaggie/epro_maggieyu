<?php

abstract class mod_account_tasks_at_leaf_node extends mod_account_tasks_at_node 
{
	protected $note;
	protected $status;

	public function isLeafNode() { return true; }

}
?>