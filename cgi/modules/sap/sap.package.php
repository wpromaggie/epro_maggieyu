<?php
require_once \epro\COMMON_PATH.'sap/prospect.php';
require_once \epro\COMMON_PATH.'sap/package.php';

/**
 * Edit SAP packages
 */
class mod_sap_package extends mod_sap
{
	public function display_index() {

		//Get default package variables by package
		$default = new prospect(0);
		$package_vars = $default->get_packages();


		//Get all packages that can have variables
		$package_names = package::get_non_default();

		//Display Stuff
		?>
		<div class="title">Edit Package Variables</div>

		<div id="package_add">
		</div>

		<div id="package_edit">
		<?php foreach($package_names as $package) { ?>
			<div class="package">

				<div class="title"><?php echo $package;?></div>
				<input type="hidden" class="pkg_name" value="<?php echo $package;?>"/>

				<?php
				if (isset($package_vars[$package])) {
					foreach($package_vars[$package] as $var) {
				?>
				<div class="package_var">
					<label class="tlabel"><?php echo $var['var_name'];?></label>
					<input type="hidden" class="edit_name" value="<?php echo $var['var_name'];?>"/>
					<input type="text" class="edit_value" value="<?php echo $var['var_value'];?>"/>

					<input type="checkbox" class="edit_req" <?php if ($var['required']) { echo 'checked="checked"'; } ?>/>
					<label class="clabel">required</label>

					<input type="checkbox" class="edit_chrg" <?php if ($var['charge']) { echo 'checked=checked'; } ?>/>
					<label class="clabel">charge</label>

					<button class="edit_var_btn">Edit</button>
					<button class="delete_var_btn">Delete</button>
				</div>
				<?php
					}
				}
				?>

				<div class="package_var_add">
					<div class="var_info">
						<div>
							<label class="tlabel">Variable Name</label>
							<input type="text" class="add_name"/>

							<input type="checkbox" class="add_required"/>
							<label class="clabel">required</label>
						</div>
						<div>
							<label class="tlabel">Default Value</label>
							<input type="text" class="add_value"/>

							<input type="checkbox" class="add_charge"/>
							<label class="clabel">charge</label>
						</div>
					</div>
					<button class="add_var_btn">Add Variable</button>
					<div class="clear"></div>
				</div>

			</div>
		<?php } ?>
		</div>

		<?php
	}

	public function ajax_add_var()
	{
		//Get argument values
		$arg_names = array('pkg','name','value','required','charge');
		$args = cgi::get_post_vars($arg_names, TRUE);

		//Check name
		if ($args['name'] == '' || preg_match('/\\s/', $args['name'])) {
			echo "Illegal name: '{$args['name']}'.";
			return;
		}

		//Attempt to add var to default package
		$default = new prospect(0);
		$result = $default->add_var($args['pkg'], $args['name'], $args['value'], $args['required'], $args['charge']);

		//Return useful info
		if (!$result) {
			echo 'Error adding variable';
			return;
		}
		echo 'TRUE';
		return;
	}

	public function ajax_edit_var()
	{
		//Get arg values
		$arg_names = array('pkg','name','value','required','charge');
		$args = cgi::get_post_vars($arg_names, TRUE);

		//Attempt to edit
		$default = new prospect(0);
		$result = $default->edit_var($args['pkg'], $args['name'], $args['value'], $args['required'], $args['charge']);

		//Return useful info
		if (!$result) {
			echo 'Error editing variable';
			return;
		}
		echo 'TRUE';
		return;
	}

	public function ajax_delete_var()
	{
		//Get arg values
		$arg_names = array('pkg','name');
		$args = cgi::get_post_vars($arg_names, TRUE);

		//Attempt to delete
		$default = new prospect(0);
		$result = $default->delete_var($args['pkg'], $args['name']);

		//Return useful info
		if (!$result) {
			echo 'Error deleting variable';
			print_r($args);
			return;
		}
		echo 'TRUE';
		return;
	}
}
?>
