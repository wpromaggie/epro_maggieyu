<?php
require_once \epro\COMMON_PATH.'sap/proposal_default.php';
require_once \epro\COMMON_PATH.'sap/package.php';

/**
 * Functions for viewing and editing the default SAP.
 */
class mod_sap_edit_default extends mod_sap_edit
{
	public function display_index()
	{
		$sap_default = new proposal_default();
		$default_root = $sap_default->build_sap();

		?>
		<div id="save_cancel">
			<input type="submit" id="save_edits" value="Save" a0="action_save_edits"/>
			<input type="submit" id="cancel_edits" value="Cancel" a0="action_cancel_edits"/>
		</div>

		<?php $this->print_edit_controls($sap_default); ?>
		<?php $this->print_edit_package(); ?>

		<div id="proposal">
			<?php $default_root->printNode(); ?>
		</div>

		<div class="clear"></div>
		<?php
	}

	public function print_edit_package()
	{
		?>
		<div id="default_edit_controls">

			<div class="edit_package">
				<select class="pkg_select" multiple="multiple" size="5">
					<?php
					foreach (package::get_all() as $package) {
						echo "<option value='$package'>$package</option>";
					}
					?>
				</select>
				<div>
					<button type="button" class="save_pkg_btn">Save</button>
					<button type="button" class="cancel_pkg_btn">Cancel</button>
				</div>
			</div>

		</div>
		<?php
	}

	public function action_save_edits()
	{
		$ops_array = $_POST['ops_array'];
		//print_r($ops_array);

		//db::dbg();
		$sap_default = new proposal_default();
		$sap_default->perform_operations($ops_array);
		//db::dbg_off();

		cgi::redirect('sap/edit/default');
	}

	public function action_cancel_edits()
	{
		//Clears post vars in case the user refreshes the page later
		cgi::redirect('sap/edit/default');
	}

}
?>
