<?php
class layouts extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'account_tasks';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'       ,'int'     ,null,null   ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY | rs::AUTO_INCREMENT),
			new rs_col('ac_type'  ,'varchar' ,16  ,''     ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('users_id' ,'int'     ,null,0      ,rs::NOT_NULL | rs::UNSIGNED | rs::READ_ONLY),
			new rs_col('title'    ,'varchar' ,128 ,''     ,rs::NOT_NULL),
			new rs_col('dt'       ,'datetime',null,rs::DDT,rs::NOT_NULL)
		);
	}
	
	public static function get_all()
	{
		$l = db::select("SELECT * FROM account_tasks.layouts", 'ASSOC');
		return $l;
	}
	
	public function add_section($ac_id=0)
	{
		$table = self::$db.'.';
		$child_order_where = "
			layout_id = $this->id AND 
			type = 'section'
		";
		$data = array(
			'layout_id'	=> $this->id,
			'parent_id'	=> 0,
			'type'		=> 'section',
			'struct'	=> 'branch',
			'text'		=> 'New Section'
		);
		
		if($ac_id){
			$table .= 'client_nodes';
			$child_order_where .= " AND ac_id = '$ac_id'";
			$data['ac_id'] = $ac_id;
		} else {
			$table .= 'default_nodes';
		}
		
		$child_order = db::count_select("
			SELECT id
			FROM $table
			WHERE $child_order_where
		");
		$data['child_order'] = $child_order;
		
		db::insert($table, $data);
	}
	
	public static function delete_node($node_id, $ac_id=null)
	{
		$table = self::$db.'.';
		$child_order_where = "";
		if(!empty($ac_id)){
			$table .= 'client_nodes';
			$child_order_where .= "ac_id = '$ac_id' AND ";
		} else {
			$table .= 'default_nodes';
		}
		
		//update sibling child orders
		list($child_order, $parent_id) = db::select_row("select child_order, parent_id from $table where id = $node_id");
		$child_order_where .= "child_order>$child_order AND parent_id=$parent_id";
		db::exec("update $table set child_order=child_order-1 where $child_order_where");
		
		//delete children
		db::exec("delete from $table where parent_id = $node_id");
		
		//delete node
		db::exec("delete from $table where id = $node_id");
	}
}

class tasks {
	
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

class default_tasks extends tasks {
	
	
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

abstract class at_node 
{
	protected $parent_id;
	protected $node_id;
	protected $node_type;
	protected $text;

	public abstract function isLeafNode();
	public abstract function printNode();
	
	public function getParentID(){ return $this->parent_id; }
	public function getNodeID(){ return $this->node_id; }
	public function getNodeType(){ return $this->node_type; }
	
	static private $node_type_structure = array(
		'section' => 'branch',
		'item' => 'leaf'
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
	
	public function getInnerText() { return $this->text; }
	
	public function getInnerNote()
	{ 
		return nl2br($this->note);
	}
}

abstract class at_branch_node extends at_node
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

abstract class at_leaf_node extends at_node 
{
	protected $note;
	protected $status;

	public function isLeafNode() { return true; }

}


class at_basic_branch extends at_branch_node
{
	public function __construct($parent_id, $node_id, $node_type, $text) {
		$this->parent_id = $parent_id;
		$this->node_id = $node_id;
		$this->node_type = $node_type;
		$this->text = $text;
	}

	public function printBeforeChildren()
	{
		echo "<div id='branch_{$this->getNodeID()}' class='node branch {$this->getNodeType()}' node_type='{$this->getNodeType()}'>";
		
		if($this->node_type!='default_root')
		{
			echo "	<div class='title'>";
			echo "	<div class='icons'>";
			echo "		<img class='toggle_items expanded' src='".cgi::href('img/story_expanded.png')."' />";
			echo "		<img class='toggle_items collapsed' src='".cgi::href('img/story_collapsed.png')."' />";
			echo "	</div>";
			echo "	<div class='text'>{$this->getInnerText()}</div> <span class='count'>(0)</span>";
			echo "	<div class='edit_title_wrapper'><textarea class='title_input'>{$this->getInnerText()}</textarea></div>";
			
			echo "  <input type='submit' class='delete-section' value='Delete'/>";
			
			echo "  <div class='clear'></div>";
			echo "	</div>";
			
			echo "	<div class='item-list'>";
		}
	}
	
	public function printAfterChildren()
	{
		//add item row
		if($this->node_type!='default_root')
		{
			//close item list
			echo "	</div>";
			
			echo "	<div class='add item'>";
			echo "		<input type='submit' value='Add' class='add_task' />";
			echo "		<div class='edit_task_wrapper'>";
			echo "			<textarea class='add_input' placeholder='Add a task'></textarea>";
			echo "		</div>";
			echo "	</div>";
		}
		
		//close branch node
		echo "</div>";
	}
	
}

class at_basic_leaf extends at_leaf_node 
{
	public function __construct($parent_id, $node_id, $node_type, $text, $note='', $status='') {
		$this->parent_id = $parent_id;
		$this->node_id = $node_id;
		$this->node_type = $node_type;
		$this->text = $text;
		$this->note = $note;
		$this->status = $status;
	}

	public function printNode() {
		$class = "node leaf {$this->getNodeType()}";
		if($this->status=="complete"){
			$class .= ' complete';
		}
		echo "<div id='leaf_{$this->getNodeID()}' class='$class' node_type='{$this->getNodeType()}'>";
		
		echo " <input type='submit' value='Save' class='save_task' />";
		
		echo "  <div class='actions'>";
		echo "		<a href='#' class='edit'>edit</a>";
		echo "		<a href='#' class='delete'>delete</a>";
		echo "	</div>";
		
		echo " <input class='checkbox' type='checkbox'";
		if($this->status == 'complete'){
			echo " checked=checked";
		}
		echo " />";
		
		echo "  <div class='display-text'>";
		echo "	<div class='text'>{$this->getInnerText()}</div>";
		echo "  <div class='note'>{$this->getInnerNote()}</div>";
		echo "  </div>";
		
		echo "	<div class='edit_task_wrapper'>";
		
		echo "		<textarea class='edit_input'>".$this->getInnerText()."</textarea>";
		echo "		<textarea class='edit_input_note' placeholder='notes'>".$this->note."</textarea>";
		
		echo "	</div>";
		
		echo "<div class='clear'></div>";

		echo "</div>";
	}
	
}

class at_default_branch extends at_branch_node
{
	public function __construct($parent_id, $node_id, $node_type, $text) {
		$this->parent_id = $parent_id;
		$this->node_id = $node_id;
		$this->node_type = $node_type;
		$this->text = $text;
	}

	public function printBeforeChildren()
	{
		echo "<div id='branch_{$this->getNodeID()}' class='node branch {$this->getNodeType()}' node_type='{$this->getNodeType()}'>";
		
		if($this->node_type!='default_root')
		{
			echo "	<div class='title'>";
			echo "	<div class='icons'>";
			echo "		<img class='toggle_items expanded' src='".cgi::href('img/story_expanded.png')."' />";
			echo "		<img class='toggle_items collapsed' src='".cgi::href('img/story_collapsed.png')."' />";
			echo "	</div>";
			
			echo "	<div class='text'>{$this->getInnerText()}</div> <span class='count'>(0)</span>";
			
			echo "	<div class='edit_title_wrapper'><textarea class='title_input'>{$this->getInnerText()}</textarea></div>";
			
			echo "  <input type='submit' class='delete-section' value='Delete'/>";
			
			echo "  <div class='clear'></div>";
			echo "	</div>";
			
			echo "	<div class='item-list'>";
		}
	}
	
	public function printAfterChildren()
	{
		//add item row
		if($this->node_type!='default_root')
		{
			//close item list
			echo "	</div>";
			
			echo "	<div class='add item'>";
			echo "		<input type='submit' value='Add' class='add_task' />";
			echo "		<div class='edit_task_wrapper'>";
			echo "			<textarea class='add_input' placeholder='Add a task'></textarea>";
			echo "		</div>";
			echo "	</div>";
		}
		
		//close branch node
		echo "</div>";
	}
	
}

class at_default_leaf extends at_leaf_node 
{
	public function __construct($parent_id, $node_id, $node_type, $text, $note=NULL, $status=NULL) {
		$this->parent_id = $parent_id;
		$this->node_id = $node_id;
		$this->node_type = $node_type;
		$this->text = $text;
		$this->note = $note;
		$this->status = $status;
	}

	public function printNode() {
		echo "<div id='leaf_{$this->getNodeID()}' class='node leaf {$this->getNodeType()}' node_type='{$this->getNodeType()}'>";
		
		echo " <input type='submit' value='Save' class='save_task' />";
		
		echo "  <div class='actions'>";
		echo "		<a href='#' class='edit'>edit</a>";
		echo "		<a href='#' class='delete'>delete</a>";
		echo "	</div>";
		
		echo "  <div class='display-text'>";
		echo "	<div class='text'>{$this->getInnerText()}</div>";
		echo "  </div>";
		
		echo "	<div class='edit_task_wrapper'>";
		echo "		<textarea class='edit_input'>".$this->getInnerText()."</textarea>";
		echo "	</div>";
		
		echo "<div class='clear'></div>";

		echo "</div>";
	}
	
}

?>