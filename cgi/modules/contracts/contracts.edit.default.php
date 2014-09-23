<?php

class mod_contracts_edit_default extends mod_contracts_edit
{
	public function pre_output()
	{
		$layout = (empty($_POST['layout'])) ? 'Agency Services' : $_POST['layout'];
		$this->proposal = new proposal_default($layout);
	}
	
	public function display_index()
	{	
		$default_root = $this->proposal->build_contract();
		
		$layout_options = "";
		foreach(proposal::get_layouts() as $l){
			$selected_layout = ($this->proposal->get_layout()==$l) ? ' selected' : '';
			$layout_options .= "<option value='$l'$selected_layout>$l</option>";
		}
		
		?>
		<div id="save_cancel">
			<input type="submit" id="save_edits" value="Save" a0="action_save_edits"/>
			<input type="submit" id="cancel_edits" value="Cancel" a0="action_cancel_edits"/>
		</div>

		<?php $this->print_edit_controls(); ?>
		<?php $this->print_edit_package(); ?>
		
		<div id="proposal">
			Layout:
			<select id="layout_select" name="layout">
				<?php echo $layout_options ?>
			</select>
			<br /><br />
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
					foreach (package::get_all_by_layout($this->proposal->get_layout()) as $package) {
							echo "<option value='{$package['id']}:{$package['name']}'>{$package['name']} ({$package['service']})</option>";
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
		$this->proposal->perform_operations($ops_array);
	}

	public function action_cancel_edits()
	{
		//Clears post vars in case the user refreshes the page later
		cgi::redirect('contracts/edit/default');
	}
}
?>
