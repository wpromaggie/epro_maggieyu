<?php
class mod_account_tasks_layouts extends rs_object
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
?>