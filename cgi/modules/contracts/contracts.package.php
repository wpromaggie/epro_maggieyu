<?php

class mod_contracts_package extends mod_contracts
{
	protected $package;
	
	public function display_index() {
		
		$layout = (empty($_POST['layout'])) ? 'Agency Services' : $_POST['layout'];
		$layout_options = "";
		foreach(proposal::get_layouts() as $l){
			$selected = ($layout==$l) ? ' selected' : '';
			$layout_options .= "<option value='$l'$selected>$l</option>";
		}

		//Get default package variables by package
		$default_prospect = new prospects(array('prospect_id' => 0));
		$services = package::get_all();
		
		$package = array();
		$selected_service_add = (empty($_POST['package']['service'])) ? "" : $_POST['package']['service'];
		$package_select_options = "";
		
		if($this->package){
			$package = db::select_row("
				SELECT *
				FROM contracts.packages
				WHERE id={$this->package->get_id()}
				LIMIT 1
			", 'ASSOC');
			$selected_service_edit = $package['service'];
			$package_select_options = self::print_package_select_options($selected_service_edit, $this->package->get_id());
		}
		
		//Define service options
		$service_options_add = $service_options_edit = "";
		
		foreach(package::get_services($layout) as $name => $value){
			
			$selected_add = $selected_edit = "";
			
			if($value==$selected_service_add) $selected_add = " selected";
			if($value==$selected_service_edit) $selected_edit = " selected";
			
			$service_options_add .= "<option value='$value'$selected_add>$name</option>";
			$service_options_edit .= "<option value='$value'$selected_edit>$name</option>";
		}

		//Display Stuff
		?>
		<div id="msg_dst"></div>
		
		<h2>Select Layout</h2>
		<select id="layout_select" name="layout">
			<?php echo $layout_options ?>
		</select>
		
		<h2>Add New Package</h2>
		<div id="package_add">
			<label class="tlabel">Package Name</label>
			<input type="text" name="package[name]" value="<?php echo $_POST['package']['name'] ?>" />
			
			<select name="package[service]">
				<option value="">--- Select Service ---</option>
				<?php echo $service_options_add ?>
			</select>
			
			<input type="submit" a0="add_package" name="add_package" value="Add Package"/>
		</div>
		
		
		<h2>Edit Package Variables</h2>
		<select id="service_select">
			<option value="">--- Select Service ---</option>
			<?php echo $service_options_edit ?>
		</select>
		
		<select id="package_select"<?php if(empty($package_select_options)) echo " style='display: none;'"?>>
			<?php echo $package_select_options ?>
		</select>
			
		<div id="package_edit">
			
			<?php if($package){
				self::print_package_edit($package);
			} ?>
			
		</div>
		
		<div id="package_actions">
			<input type="submit" a0="delete_package" value="Delete Package" class="delete_package_btn" />
			<div class="clear"></div>
		</div>

		<?php
	}

	public function add_package(){
		if(empty($_POST['package']['name'])){
			feedback::add_error_msg("You must name the package.");
			return;
		}
		
		if(empty($_POST['package']['service'])){
			feedback::add_error_msg("You must select a service.");
			return;
		}
		$this->package = package::create_new($_POST['package']);
		if($this->package){
			feedback::add_success_msg('New package created.');
			unset($_POST['package']);
		} else {
			feedback::add_error_msg("Error creating new package");
		}
		
	}
	
	public function delete_package(){
		package::delete($_POST['package']['id']);
	}
	
	private static function print_package_edit($package){
	?>
		<h3 class="package_title">
			<span class="title_display">
				<span class="package_name"><?php echo $package['name']; ?></span>
				<button class="edit_package_name">Edit</button>
			</span>
			<span class="title_edit hidden">
				<input type="text" value="<?php echo $package['name']; ?>" />
				<button class="save_package_name">Save</button>
				<button class="cancel_package_name">Cancel</button>
			</span>
		</h3>
		
		<input type="hidden" name="package[id]" class="package_id" value="<?php echo $package['id']; ?>"/>

		<?php
		$length_var = db::select_row("
			SELECT * FROM contracts.package_vars
			WHERE package_id={$package['id']}
			AND prospect_id=0
			AND type='contract_length' 
		", 'ASSOC');
		
		$package_vars = db::select("
			SELECT * FROM contracts.package_vars
			WHERE package_id={$package['id']}
			AND prospect_id=0
			AND type<>'contract_length'
			ORDER BY name
		", 'ASSOC');
		?>
		
		<div class="package_vars">
		<?php
			package::build_default_var_form($length_var);
			if ($package_vars) {
				foreach($package_vars as $var) {
					package::build_default_var_form($var);
				}
			}
		?>
		</div>
		
		<div class="package_var_add package_var">

			<div class="package_head">
				New Package Var
				<div class="package_var_actions">
					<input type="submit" value="Add Variable" class="add_var_btn" />
				</div>
			</div>

			<div class="var_info">

				<div class="col left">
					<div>
						<label class="tlabel">Name</label>
						<input type="text" class="add_name"/>
					</div>
					<div>
						<label class="tlabel">Description</label>
						<textarea class="add_description"></textarea>
					</div>
					<div>
						<label class="tlabel">Charge Method</label>
						<select class="add_charge_method">
							<?php echo package::build_var_type_options() ?>
						</select>
					</div>
					<div>
						<label class="tlabel">Payment Type</label>
						<select class="add_payment_type">
							<?php echo package::build_payment_type_options(); ?>
						</select>
					</div>
					<div>
						<label class="tlabel">Value</label>
						<input type="text" class="add_value"/>
					</div>
				</div>

				<div class="col left">
					<div>
						<input type="checkbox" class="add_required" checked="checked" />
						<label class="clabel">required</label>
					</div>
					<div>
						<input type="checkbox" class="add_charge" checked="checked" />
						<label class="clabel">charge</label>
					</div>
					<div>
						<input type="checkbox" class="add_order_table" checked="checked" />
						<label class="clabel">order table</label>
					</div>
				</div>

			</div>

			<div class="clear"></div>
		</div>
	<?php
	}
	
	private static function print_package_select_options($service, $package_id=0){
		$packages = db::select("
			SELECT id, name
			FROM contracts.packages
			WHERE service='$service' AND deleted=0
		", 'ASSOC');
		
		$ml = "<option value=''>--- Select ".strtoupper($service)." Package ---</option>";
		foreach($packages as $p){
			$selected = "";
			if($p['id']==$package_id) $selected = " selected";
			$ml .= "<option value='{$p['id']}' $selected>{$p['name']}</option>";
		}
		return $ml;
	}
	
	public function ajax_save_package(){
		$package = new package($_POST['package_id']);
		$package->save($_POST);
	}
	
	public function ajax_add_package_var(){
		$package = new package($_POST['package_id']);
		if($var=$package->add_var($_POST)){
			$package::build_default_var_form($var);
		}
	}
	
	public function ajax_delete_package_var(){
		$package = new package($_POST['package_id']);
		$package->delete_var($_POST['name']);
	}
	
	public function ajax_save_package_var(){
		$package = new package($_POST['package_id']);
		$success = $package->save_var($_POST);
		
		//Return useful info
		if($success===FALSE) {
			echo 'Error saving package variable.';
			return;
		}
		echo 'TRUE';
		return;
	}
	
	public function ajax_show_package_edit(){
		$package_id = $_POST['package_id'];
		$package = db::select_row("
			SELECT *
			FROM contracts.packages
			WHERE id=$package_id
			LIMIT 1
		", 'ASSOC');
		
		if($package){
			self::print_package_edit($package);
		}
	}
	
	//Used to populate the package options when a service is selected
	public function ajax_show_package_select(){
		$ml = self::print_package_select_options($_POST['service']);
		echo $ml;
	}
}
?>
