<?php
require_once \epro\COMMON_PATH.'sap/proposal_preview.php';
require_once \epro\COMMON_PATH.'sap/prospect.php';
require_once \epro\COMMON_PATH.'sap/package.php';

/**
 * SAP Prospect Stuff
 */
class mod_sap_prospect extends mod_sap
{
	private $preview;

	public function display_index()
	{
		$packages = package::get_non_default();

		$var_names = array('prospect_name', 'prospect_company', 'prospect_email', 'prospect_url');
		list($p_name, $p_company, $p_email, $p_url) = array_values(cgi::get_post_vars($var_names, TRUE));
		?>
		<div id="prospect_options">

			<div class="title">Add Prospect</div>

			<div id="prospect_parts">
				<div>
					<label for="prospect_name">Name</label>
					<input id="prospect_name" name="prospect_name" type="text" value="<?php echo $_POST['prospect_name']; ?>"/>
				</div>
				<div>
					<label for="prospect_company">Company</label>
					<input id="prospect_company" name="prospect_company" type="text" value="<?php echo $p_company; ?>"/>
				</div>
				<div>
					<label for="prospect_email">Email</label>
					<input id="prospect_email" name="prospect_email" type="text" value="<?php echo $p_email; ?>"/>
				</div>
				<div>
					<label for="prospect_url">URL</label>
					<input id="prospect_url" name="prospect_url" type="text" value="<?php echo $p_url; ?>"/>
				</div>
			</div>

			<div id="package_chooser">
				<?php
				$selected_pkgs = array();
				if (isset ($_POST['prospect_package'])) {
					$selected_pkgs = $_POST['prospect_package'];
				}
				foreach ($packages as $pkg) {
					$checked = in_array($pkg, $selected_pkgs)?"checked='checked'":'';
					echo "<input type='checkbox' class='pkg_chk' name='prospect_package[]' value='$pkg' $checked />";
					echo "<label>$pkg</label><br />";
				}
				?>
			</div>

			<div class="clear"></div>

			<div id="package_vars">
				<?php foreach($packages as $pkg) { ?>
				<div id="<?php echo "pkg_$pkg" ; ?>">
					<?php
					if (isset($_POST['pkg_vars'][$pkg])) {
						$this->print_package_edit($pkg, $_POST['pkg_vars'][$pkg]);
					}
					?>
				</div>
				<?php } ?>
			</div>

			<div id="prospect_buttons">
				<!--input type="submit" id="preview_prospect" value="Preview" a0="action_prospect_preview"/-->
				<input type="submit" id="save_prospect" value="Save" a0="action_prospect_save"/>
				<input type="submit" id="cancel_prospect" value="Cancel" a0="action_prospect_cancel"/>
			</div>

		</div>

		<?php
		if ($this->preview) {
			echo '<div id="preview">';
			echo $this->preview;
			echo '</div>';
		}
		?>

		<?php
	}

	public function ajax_show_package_edit()
	{
		//Get default package info
		$pkg_name = db::escape($_POST['pkg_name']);
		$default_prospect = new prospect(0);
		$pkg_vars = $default_prospect->get_package_vars($pkg_name);

		//Format package info edit
		ob_start();
		$this->print_package_edit($pkg_name, $pkg_vars);
		$html = ob_get_clean();

		//Send it back
		echo json_encode($html);
	}

	public function print_package_edit($pkg_name, $pkg_vars)
	{
		?>
		<div class="edit_package">
			<div class="title"><?php echo $pkg_name;?></div>
			<table>
			<?php foreach ($pkg_vars as $var) { ?>
				<tr>
				<td>
					<label><?php echo $var['name'];?></label>
				</td>
				<td>
					<input type="hidden" name="<?php echo "pkg_vars[$pkg_name][{$var['name']}][name]";?>" value="<?php echo $var['name'];?>" />
					<input type="text" name="<?php echo "pkg_vars[$pkg_name][{$var['name']}][value]";?>" value="<?php echo $var['value'];?>" />
					<input type="hidden" name="<?php echo "pkg_vars[$pkg_name][{$var['name']}][required]";?>" value="<?php echo $var['required'];?>" />
					<input type="hidden" name="<?php echo "pkg_vars[$pkg_name][{$var['name']}][charge]";?>" value="<?php echo $var['charge'];?>" />
				</td>
				</tr>
			<?php } ?>
			</table>
		</div>
		<?php
	}

	public function action_prospect_preview()
	{
		if (!isset($_POST['prospect_package'])) {
			feedback::add_error_msg("Please select one or more packages");
			return;
		}

		$packages = $_POST['prospect_package'];

		$sap_preview = new proposal_preview($packages);
		$preview_root = $sap_preview->build_sap();

		ob_start();
		$preview_root->printNode();
		$this->preview = ob_get_clean();
	}

	public function action_prospect_save()
	{
		//Check fields
		$field_names = array('prospect_name','prospect_company','prospect_email','prospect_url');
		$prospect_fields = cgi::get_post_vars($field_names);
		if (count($prospect_fields) < count($field_names)) {
			feedback::add_error_msg("Missing input fields");
			return;
		}

		//Check selected packages
		if (!isset($_POST['prospect_package'])) {
			feedback::add_error_msg("Please select one or more packages");
			return;
		}
		$packages = $_POST['prospect_package'];

		//Get package vars
		$package_vars = $_POST['pkg_vars'];

		e($package_vars);
		return;

		//Create new prospect and SAP proposal
		$prospect_id = prospect::create_new($prospect_fields);
		proposal::create_new($prospect_id, $packages);

		cgi::redirect('sap/edit');
	}

	public function action_prospect_cancel()
	{
		cgi::redirect('sap');
	}
}
?>