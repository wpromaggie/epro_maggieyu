<?php

class mod_contracts_prospects extends rs_object
{
	public static $db, $cols, $primary_key, $indexes;
	public static $default_contract_length = 6;

	public static $payment_method_options = array(
		'',
		'credit',
		'check',
		'wire'
	);
	
	public static function set_table_definition()
	{
		self::$db = 'contracts';
		self::$primary_key = array('id');
		
		self::$cols = self::init_cols(
			new rs_col('id'			,'bigint'	,null	,null	,rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT),
			new rs_col('layout'		,'enum'	,null	,null	,rs::NOT_NULL,	array('Agency Services', 'TeleVox', 'Dealers United', 'Small Business')),
			new rs_col('client_id'  ,'char' ,16	,'',rs::NOT_NULL),
			new rs_col('parent_id'		,'bigint'	,null	,0	,rs::NOT_NULL),
			new rs_col('user_id'		,'bigint'	,null	,0	,rs::NOT_NULL),
			new rs_col('create_date'	,'date'		,null	,rs::DD ,rs::NOT_NULL),
			new rs_col('mod_date'		,'date'		,null	,rs::DD ,rs::NOT_NULL),
			new rs_col('close_date'		,'date'		,null	,rs::DD ,rs::NOT_NULL),
			new rs_col('status'		,'varchar'	,32	,''     ,rs::NOT_NULL),
			new rs_col('name'		,'varchar'	,128	,''     ,rs::NOT_NULL),
			new rs_col('company'		,'varchar'	,128	,''     ,rs::NOT_NULL),
			new rs_col('url_key'		,'varchar'	,128	,''     ,rs::NOT_NULL),
			new rs_col('title'		,'varchar'	,128	,''     ,rs::NOT_NULL),
			new rs_col('address'		,'varchar'	,128	,''     ,rs::NOT_NULL),
			new rs_col('city'		,'varchar'	,64	,''     ,rs::NOT_NULL),
			new rs_col('state'		,'varchar'	,2	,''     ,rs::NOT_NULL),
			new rs_col('country'		,'varchar'	,64	,''     ,rs::NOT_NULL),
			new rs_col('zip'		,'varchar'	,8	,''     ,rs::NOT_NULL),
			new rs_col('url'		,'varchar'	,128	,''	,rs::NOT_NULL),
			new rs_col('email'		,'varchar'	,128	,''     ,rs::NOT_NULL),
			new rs_col('phone'		,'varchar'	,32	,''     ,rs::NOT_NULL),
			new rs_col('fax'		,'varchar'	,32	,''     ,rs::NOT_NULL),
			new rs_col('revenue'		,'double'	,null	,0	,rs::NOT_NULL | rs::UNSIGNED),
			new rs_col('signature'		,'varchar'	,128	,''     ,rs::NOT_NULL),
			new rs_col('sig_title'		,'varchar'	,64	,''     ,rs::NOT_NULL),
			new rs_col('sig_month'		,'varchar'	,2	,''     ,rs::NOT_NULL),
			new rs_col('sig_day'		,'varchar'	,2	,''     ,rs::NOT_NULL),
			new rs_col('sig_year'		,'varchar'	,4	,''     ,rs::NOT_NULL),
			new rs_col('ip'			,'varchar'	,25	,''     ,rs::NOT_NULL),
			new rs_col('payment_method'	,'enum'	,16	,''     ,rs::NOT_NULL),
			new rs_col('hide_total'	,'int'	,null	,0     ,rs::NOT_NULL),
			new rs_col('partner'		,'varchar'	,128	,''     ,rs::NOT_NULL),
			new rs_col('expire_date'	,'date'		,null	,rs::DD)
		);
	}
	
	public static function country_form_input($table, $col, $val)
	{
		$countries = db::select("
			select a2, country
			from eppctwo.countries
			order by country asc
		");
		if (!$val)
		{
			$val = 'US';
		}
		return cgi::html_select($table.'_'.$col->name, $countries, $val);
	}
	
	public static function state_form_input($table, $col, $val)
	{
		$states = db::select("
			SELECT short, text
			FROM eppctwo.states
			ORDER BY text ASC
		");
		return cgi::html_select($table.'_'.$col->name, $states, $val);
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
			WHERE id={$this->id}
		", 'ASSOC');
	}
	
	public function get_charge_total(){
		$total = 0;
		$packages = $this->get_packages();
		foreach($packages as $package){
			$vars = $this->get_package_vars($package['id'], TRUE);
			foreach($vars as $var){
				if($var['charge'] && $var['type']!=="no_first"){
					$total += $var['value']-$var['discount'];
				}
			}
		}
		return $total;
	}

	public function get_package_vars($package_id, $tabel_vars_only=FALSE) {
		
		$where_cond = "WHERE prospect_id={$this->id} AND package_id=$package_id";
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
	
	
	public function get_contract_length($package_id=0){
		
		//db::dbg();
		$where = "WHERE prospect_id={$this->id} AND type='contract_length'";
		if($package_id){
			$where .= " AND package_id=$package_id"; 
		}
		
		$length = db::select_one("
			SELECT value
			FROM contracts.package_vars
			$where
			ORDER BY value DESC
		");
			
		if(!$length){
			return $this->default_contract_length;
		}
		
		return $length;
	}
	
	public function check_url_key($url_key){
		
		//db::dbg();
		$where = "WHERE url_key='$url_key'";
		if(!empty($this->id)){
			$where .= " AND id<>{$this->id}";
		}
		
		//check old prospects (may remove after merging)
		if(db::select_one("SELECT url_key from eppctwo.prospects $where LIMIT 1")){
			return FALSE;
		}
		
		if(db::select_one("SELECT url_key from contracts.prospects $where LIMIT 1")){
			return FALSE;
		}
		
		return TRUE;
	}
	
	
	public function get_var_by_key($var_key)
	{
		list($name, $format) = explode(',', trim($var_key, '{}'));
		list($base_name, $var_name) = explode('.', $name);
		
		$base_name = trim($base_name);
		if($base_name=="prospect"){
			
			if($var_name=="contract_length"){
				$value = $this->get_contract_length();
			}
			else {
				$value = db::select_one("
					SELECT $var_name
					FROM contracts.prospects
					WHERE id={$this->id}
				");
			}
			
			
		} else {
			//db::dbg();
			$value = db::select_one("
				SELECT package_vars.value 
				FROM contracts.package_vars package_vars
				LEFT JOIN contracts.packages packages
					ON packages.id = package_vars.package_id AND packages.deleted=0
				WHERE package_vars.prospect_id={$this->id} AND package_vars.name='$var_name' AND packages.name='$base_name'
			");
			//e($value);
		}
		
		if(empty($value)){
			return "<span class='error'>(missing $var_key)</span>";
		}
		
		if(!empty($format)){
			$format = trim($format);
			switch($format){
				case 'date':
					$value = date('F d, Y', strtotime($value));
					break;
				case 'dollar':
					$value = util::format_dollars($value);
					break;
				case 'percent':
					$value = util::format_percent($value);
					break;
			}
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
	
	public function get_services(){
		$services = db::select("
			SELECT DISTINCT package_vars.package_id, packages.name, packages.service
			FROM contracts.package_vars package_vars
				LEFT JOIN contracts.packages packages
				ON packages.id = package_vars.package_id
			WHERE prospect_id = {$this->id}
		", "ASSOC");
		return $services;
	}
	
	public function get_package_var_by_type($package_id, $var_type){
		return db::select_row("
			SELECT * 
			FROM contracts.package_vars 
			WHERE prospect_id={$this->id} AND package_id=$package_id AND type='$var_type'
		", "ASSOC");
	}
	
	public function get_service_package($service){
		$package = db::select_row("
			SELECT packages.id, packages.name, packages.service, packages.deleted
			FROM contracts.packages packages
				LEFT JOIN contracts.package_vars package_vars
				ON packages.id = package_vars.package_id
			WHERE package_vars.prospect_id={$this->id} AND packages.service='$service'
		", 'ASSOC');
		return $package;
	}

	public function get_packages() {
		$packages = array();
		$packages = db::select("
			SELECT DISTINCT packages.id, packages.name, packages.service, packages.deleted
			FROM contracts.packages packages
				LEFT JOIN contracts.package_vars package_vars
				ON packages.id = package_vars.package_id
			WHERE package_vars.prospect_id={$this->id}
		", 'ASSOC');
		return $packages;
	}

	public function add_var($pkg, $name, $value, $is_required, $is_charge) {

		$is_success = db::insert("contracts.package_vars", array(
			'prospect_id' => $this->id,
			'package' => $pkg,
			'var_name' => $name,
			'var_value' => $value,
			'required' => $is_required,
			'charge' => $is_charge
		));
		return $is_success;
	}
	
	public function edit_var($package_id, $name, $value, $charge=null, $discount=null) {
		
		$set = "SET value='$value'";
		if(!is_null($charge)){
			$set .= ", charge=$charge";
		}
		
		if(!is_null($discount)){
			$set .= ", discount=$discount";
		}
		
		$num_rows_edit = db::exec("
			UPDATE contracts.package_vars
			$set
			WHERE prospect_id={$this->id} AND package_id='$package_id' AND name='$name'
		");
		return ($num_rows_edit == 1);
	}
	
	public function update_var($var){
		$update_cols = array('value', 'charge', 'description', 'note', 'order_table', 'row_order');
		$data = array();
		foreach($update_cols as $col){
			if(isset($var[$col])){
				$data[$col] = $var[$col];
			}
		}
		return db::update(
			"contracts.package_vars",
			$data,
			"prospect_id={$this->id} AND package_id={$var['package_id']} AND name='{$var['name']}'"
		);
	}
	
	public function add_package_vars($package_id=0){
		
		$package_vars = db::select("
			SELECT * FROM contracts.package_vars
			WHERE prospect_id=0 AND package_id=$package_id
		", 'ASSOC');
		
		if($package_vars){
			foreach($package_vars as $var){
				$var['prospect_id']=$this->id;
				if(db::insert('contracts.package_vars', $var)===FALSE) return false;
			}
		}
		
		return true;
	}
	
	public function delete_package_vars($package_id){
		$num_rows_del = db::exec("
			DELETE FROM contracts.package_vars
			WHERE prospect_id={$this->id} AND package_id=$package_id
		");
		return $num_rows_del;
	}

	public function delete_var($pkg, $name) {
		$num_rows_del = db::exec("
			DELETE FROM contracts.package_vars
			WHERE prospect_id={$this->id} AND package='$pkg' AND var_name='$name'
			LIMIT 1
		");
		return ($num_rows_del == 1);
	}

	public function clear_billing(){
		if ($this->payment_method=='credit'){
			$cc = new ccs();
			$cc->get(array(
				'where' => 'foreign_table = "prospects" && foreign_id = '.$this->id
			));
			$cc->delete();
		}
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
	 * Completely delete all prospect data
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
