<?php
/**
 * Classes and functions for dealing with SAP prospects.
 */
class prospect
{

	protected $prospect_id;
	private $default_contract_length = 3;

	/**
	 * Constructor. Pulls all associated variables for this prospect.
	 */
	public function __construct($prospect_id) {
		$this->prospect_id = $prospect_id;
	}

	public function get_id() {
		return $this->prospect_id;
	}

	public function get_info($var_names=array()) {
		$prospect_info = array();
		$select_clause = 'SELECT ';
		if(!empty($var_names)){
			$select_clause .= implode(', ', $var_names);
		} else {
			$select_clause .= '*';
		}
		return db::select_row("
			$select_clause FROM contracts.prospects
			WHERE id={$this->prospect_id}
		", 'ASSOC');
	}

	public function edit_info($prospect_info) {
		return db::update(
			'contracts.prospects',
			$prospect_info,
			"id={$this->prospect_id}"
		);
	}

	public function get_package_vars($package_id, $tabel_vars_only=FALSE) {
		
		$where_cond = "WHERE prospect_id={$this->prospect_id} AND package_id=$package_id";
		if($tabel_vars_only){
			$where_cond .= " AND type <> 'contract_length'";
		}
		
		$package_vars = array();
		$package_vars = db::select("
			SELECT *
			FROM contracts.package_vars
			$where_cond
			ORDER BY row_order ASC, value DESC
		", 'ASSOC');
		return $package_vars;
	}
	
	
	public function get_contract_length($package_id){
		
		$length = db::select_one("
			SELECT value
			FROM contracts.package_vars
			WHERE prospect_id={$this->prospect_id} AND package_id=$package_id AND type='contract_length'
			LIMIT 1
		");
			
		if(!$length){
			return $this->default_contract_length;
		}
		
		return $length;
	}
	
	
	public function get_var_by_key($var_key){
		
		list($base_name, $var_name) = explode('.', trim($var_key, '{}'));
		
		if($base_name=="prospect"){
			$value = db::select_one("
				SELECT $var_name
				FROM contracts.prospects
				WHERE prospect_id={$this->prospect_id}
			");
		} else {
			$value = db::select_one("
				SELECT package_vars.value 
				FROM contracts.package_vars package_vars
				LEFT JOIN contracts.packages packages
					ON packages.name='$package_name'
				WHERE package_vars.prospect_id={$this->prospect_id} AND package_vars.name='$var_name'
			");
		}
		
		if(empty($value)){
			return "<span class='error'>(missing $var_key)</span>";
		}
		
		return $value;
	}
	
	public function get_package($package_id) {
		return db::select_row("
			SELECT *
			FROM contracts.packages
			WHERE id=$package_id
		", 'ASSOC');
	}
	
	public function get_service_package($service){
		$package = db::select_row("
			SELECT packages.id, packages.name, packages.service, packages.deleted
			FROM contracts.packages packages
				LEFT JOIN contracts.package_vars package_vars
				ON packages.id = package_vars.package_id
			WHERE package_vars.prospect_id={$this->prospect_id} AND packages.service='$service'
		", 'ASSOC');
		return $package;
	}

	public function get_packages() {
		$packages = array();
		$packages = db::select("
			SELECT packages.id, packages.name, packages.service, packages.deleted
			FROM contracts.packages packages
				LEFT JOIN contracts.package_vars package_vars
				ON packages.id = package_vars.package_id
			WHERE package_vars.prospect_id={$this->prospect_id}
		", 'ASSOC');
		return $packages;
	}

	public function add_var($pkg, $name, $value, $is_required, $is_charge) {
		$is_success = db::insert("contracts.package_vars", array(
			'prospect_id' => $this->prospect_id,
			'package' => $pkg,
			'var_name' => $name,
			'var_value' => $value,
			'required' => $is_required,
			'charge' => $is_charge
		), false);
		return $is_success;
	}
	
	public function edit_var($package_id, $name, $value) {
		$num_rows_edit = db::exec("
			UPDATE contracts.package_vars
			SET value='$value'
			WHERE prospect_id={$this->prospect_id} AND package_id='$package_id' AND name='$name'
		");
		return ($num_rows_edit == 1);
	}
	
	public function update_var($var){
		$update_cols = array('value', 'charge', 'description', 'order_table', 'row_order');
		$data = array();
		foreach($update_cols as $col){
			if(isset($var[$col])){
				$data[$col] = $var[$col];
			}
		}
		return db::update(
			"contracts.package_vars",
			$data,
			"prospect_id={$this->prospect_id} AND package_id={$var['package_id']} AND name='{$var['name']}'"
		);
	}
	
	public function add_package_vars($package_id=0){
		
		$package_vars = db::select("
			SELECT * FROM contracts.package_vars
			WHERE prospect_id=0 AND package_id=$package_id
		", 'ASSOC');
		
		if($package_vars){
			foreach($package_vars as $var){
				$var['prospect_id']=$this->prospect_id;
				if (db::insert('contracts.package_vars', $var)===FALSE) return false;
			}
		}
		
		return true;
	}
	
	public function delete_package_vars($package_id){
		$num_rows_del = db::exec("
			DELETE FROM contracts.package_vars
			WHERE prospect_id={$this->prospect_id} AND package_id=$package_id
		");
		return $num_rows_del;
	}

	public function delete_var($pkg, $name) {
		$num_rows_del = db::exec("
			DELETE FROM contracts.package_vars
			WHERE prospect_id={$this->prospect_id} AND package='$pkg' AND var_name='$name'
			LIMIT 1
		");
		return ($num_rows_del == 1);
	}

	/**
	 * Check if the given prospect_id is a valid prospect_id.
	 * @param mixed $prospect_id The prospect_id to check.
	 * @return bool TRUE if valid id, FALSE otherwise.
	 */
	public static function exists($prospect_id) {
		if (is_numeric($prospect_id) && $prospect_id > 0) {
			$count = db::select_one("
				SELECT COUNT(*) FROM contracts.prospects
				WHERE id = $prospect_id
			");
			return ($count > 0);
		} else {
			return FALSE;
		}
	}

	/**
	 * Create a new prospect with the given prospect info.
	 */
	public static function create_new($prospect_info) {
		$new_id = db::insert("contracts.prospects", array(
			'name' => $prospect_info['prospect_name'],
			'company' => $prospect_info['prospect_company'],
			'email' => $prospect_info['prospect_email'],
			'url' => $prospect_info['prospect_url']
		));
		return $new_id;
	}
	
	/**
	 * Create a new prospect with the given prospect info.
	 */
	public static function destroy($prospect_id) {
		
		//Delete package vars
		db::exec("DELETE FROM contracts.package_vars WHERE prospect_id=$prospect_id");
		
		//Delete prospect nodes and node text
		$nodes = db::select("SELECT id FROM contracts.prospect_nodes WHERE prospect_id=$prospect_id");
		
		$nodes_str = implode(',', $nodes);
		db::exec("DELETE FROM contracts.prospect_node_text WHERE prospect_node_id IN ($nodes_str)");
		db::exec("DELETE FROM contracts.prospect_nodes WHERE prospect_id=$prospect_id");
		
		db::exec("DELETE FROM contracts.prospects WHERE id=$prospect_id LIMIT 1");
		
	}
}
?>
