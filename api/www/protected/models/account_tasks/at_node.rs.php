<?php
abstract class mod_account_tasks_at_node 
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
?>