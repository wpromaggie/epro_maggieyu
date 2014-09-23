<?php
require_once 'proposal_default.php';

/**
 * Package functions
 */
class package
{
	
	protected $package_id;
	
	//The order of this array determines the default display order for the order details table
	protected static $services = array(
		'PPC' => 'ppc',
		'SEO' => 'seo',
		'SMO' => 'smo',
		'Infographic' => 'ingra',
		'Web Dev' => 'web_dev'
	);
	protected static $var_types = array(
	    'Monthly Cost' => 'monthly_cost',
	    'Discount' => 'discount',
	    'Split Pay' => 'split_pay',
	    'Other' => 'other'
	);

	/**
	 * Constructor. Pulls all associated variables for this package.
	 */
	public function __construct($package_id) {
		$this->package_id = $package_id;
	}
	
	public function create_new_var($var, $prospect_id){
		$var['prospect_id'] = $prospect_id;
		$var['package_id'] = $this->package_id;
		$package_id = db::insert('contracts.package_vars', $var);
	}
	
	public function add_var($var, $prospect_id=0){
		
		//Check for valid var name
		if(empty($var['name'])) return false;
		
		//Remove white space and special characters from the var name...
		$var['name'] = preg_replace("/[^a-z0-9\s]/", "", strtolower($var['name']));
		$var['name'] = preg_replace("/\s/", "_", $var['name']);
		
		//check for duplicate var name
		$duplicate = db::select_one("SELECT name FROM contracts.package_vars WHERE package_id={$this->package_id} AND name='{$var['name']}'");
		if($duplicate){
			return false;
		}
		
		//Remove non numeric characters from the value
		$var['value'] = preg_replace("/[^0-9]/", "", $var['value']);
		
		$save_cols = array('name', 'value', 'required', 'charge', 'description', 'type', 'order_table');
		$data = array(
		    'package_id' => $this->package_id
		);
		foreach($save_cols as $col){
			if(isset($var[$col])){
				$data[$col] = $var[$col];
			}
		}
		
		if(db::insert("contracts.package_vars", $data)!==FALSE){
			return $data;
		}
		
		return false;
	}
	
	public function delete_var($name, $prospect_id=0){
		return db::exec("
			DELETE FROM contracts.package_vars
			WHERE prospect_id=$prospect_id AND package_id={$this->package_id} AND name='$name'"
		);
	}
	
	public function save_var($var, $prospect_id=0){
		//db::dbg();
		
		//Remove non numeric characters from the value
		if(isset($var['value'])){
			$var['value'] = preg_replace("/[^0-9]/", "", $var['value']);
		}
		
		$update_cols = array('value', 'required', 'charge', 'description', 'type', 'order_table');
		$data = array();
		foreach($update_cols as $col){
			if(isset($var[$col])){
				$data[$col] = $var[$col];
			}
		}
		return db::update(
			"contracts.package_vars",
			$data,
			"prospect_id=$prospect_id AND package_id={$this->package_id} AND name='{$var['name']}'"
		);
	}
	
	public static function delete($package_id){
		//find default node ids related to this package
		$related_node_ids = db::select("
			SELECT default_node_id
			FROM contracts.default_nodes_packages 
			WHERE package_id=$package_id
		");
		
		if($related_node_ids){
			
			//find default node ids that are ALSO related to other packages by checking for records with different package_ids
			$related_node_id_str = implode(',', $related_node_ids);
			$safe_node_ids = db::select("
				SELECT DISTINCT default_node_id
				FROM contracts.default_nodes_packages 
				WHERE default_node_id IN ($related_node_id_str) AND package_id<>$package_id
			");
			
			$delete_node_ids = array();
			if($safe_node_ids){
				foreach($related_node_ids as $id){
					if(!in_array($id, $safe_node_ids)) $delete_node_ids[] = $id;
				}
			} else {
				//no one is safe!
				$delete_node_ids = $related_node_ids;
			}

			if(!empty($delete_node_ids)){
				$proposal = new proposal_default(0);
				foreach($delete_node_ids as $node_id){
					$proposal->delete_node($node_id);
				}
			}
		
		}
		
		//delete the package and its default vars...
		db::exec("
			DELETE FROM contracts.package_vars 
			WHERE package_id=$package_id AND prospect_id=0
		");
		db::exec("
			UPDATE contracts.packages
			SET deleted=1
			WHERE id=$package_id
		");
		
		
	}
	
	public static function create_new($package) {
		
		//package name and service is required
		if(empty($package['name']) || empty($package['service'])) return false;
		
		$package_id = db::insert('contracts.packages', $package);
		$package = new package($package_id);
		
		//Packages MUST HAVE an associated contract length
		$package->create_new_var(array(
		    'name' => 'contract_length',
		    'type' => 'contract_length',
		    'value' => 3,
		    'required' => 1
		), 0);
		
		return $package;
		
	}
	
	/**
	 * Get an array of the possible valid packages for SAP nodes.
	 * @return array The valid package names.
	 */
	public static function get_all($ignore_deleted=TRUE) {
		$where = ($ignore_deleted)?" WHERE deleted=0":'';
		$packages = db::select('SELECT id, name, service, deleted FROM contracts.packages'.$where, 'ASSOC', array('service'));
		return $packages;
	}
	
	public static function get_all_by_service($service, $ignore_deleted=TRUE){
		$where  = " WHERE service='$service'";
		$where .= ($ignore_deleted)?" AND deleted=0":'';
		$packages = db::select('SELECT id, name, service, deleted FROM contracts.packages'.$where, 'ASSOC');
		return $packages;
	}
	
	public static function get_services() {
		return self::$services;
	}
	
	public static function get_var_types() {
		return self::$var_types;
	}
	
	public static function save_package_vars($prospect_id, $package_vars) {
		foreach($package_vars as $package_id => $var){
			foreach($var as $v){
				db::insert_update('contracts.package_vars', array('prospect_id', 'package_id', 'name'), array(
				    'prospect_id' => $prospect_id,
				    'package_id' => $package_id,
				    'name' => $v['name'],
				    'value' => $v['value'],
				    'required' => $v['required'],
				    'charge' => $v['charge']
				));
			}
		}
	}
	
	/**
	 * Common HTML Stuff
	 */
	public static function build_var_type_options($default=""){
		$var_type_options = "";
		foreach(self::get_var_types() as $name => $value){
			$selected = "";
			if($value==$default){ $selected = " selected"; }
			$var_type_options .= "<option value='$value'$selected>$name</option>";
		}
		return $var_type_options;
	}
	
	public static function build_default_var_form($var){
		?>
		<div class="package_var">
			
			<div class="package_head">
				<?php echo $var['name'] ?>
				<div class="package_var_actions">
					<input type="submit" class="save_package_var" value="Save"/>
					<?php if($var['type']!='contract_length'){ ?>
						<input type="submit" class="delete_package_var" value="Delete"/>
					<?php } ?>
				</div>
			</div>
			<input type="hidden" class="var_name" value="<?php echo $var['name'];?>"/>
			
			<?php
			if($var['type']!='contract_length'){
			?>
			<div class="var_info">
				<div class="col left">
					<div>
						<label class="tlabel">Description</label>
						<textarea class="edit_desc"><?php echo $var['description'];?></textarea>
					</div>
					<div>
						<label class="tlabel">Type</label>
						<select class="edit_type">
							<?php echo self::build_var_type_options($var['type']); ?>
						</select>
					</div>
					<div>
						<label class="tlabel">Value</label>
						<input type="text" class="edit_val" value="<?php echo $var['value'];?>"/>
					</div>
				</div>
				<div class="col left">
					<div>
						<input type="checkbox" class="edit_req" <?php if ($var['required']) { echo 'checked="checked"'; } ?>/>
						<label class="clabel">required</label>
					</div>
					<div>
						<input type="checkbox" class="edit_chrg" <?php if ($var['charge']) { echo 'checked=checked'; } ?>/>
						<label class="clabel">charge</label>
					</div>
					<div>
						<input type="checkbox" class="edit_ord_tbl" <?php if ($var['order_table']) { echo 'checked=checked'; } ?>/>
						<label class="clabel">order table</label>
					</div>
				
				</div>
			</div>
			
			<?php
			} else {
			?>
			<div class="var_info">
				<div class="col left">
					<div>
						<label class="tlabel"># of Months</label>
						<input type="text" class="edit_val" value="<?php echo $var['value'];?>"/>
					</div>
				</div>
			</div>
			<?php
			}
			?>
			
			<div class="clear"></div>
		</div>
		<?php
	}
	
	public static function build_prospect_var_form($var){
		?>
		<div class="package_var">
			<label class="tlabel"><?php echo $var['name']; ?></label>
			<input type="text" var_name="<?php echo $var['name']; ?>" class="edit_val<?php if($var['required']) echo " required"; ?>" value="<?php echo $var['value'];?>"/>
			<?php if($var['required']) echo "*"; ?>
		</div>
		<?php
	}

}
?>
