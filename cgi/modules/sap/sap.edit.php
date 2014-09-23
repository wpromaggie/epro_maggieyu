<?php
require_once \epro\COMMON_PATH.'contracts/proposal.php';

/**
 * Functions for editing SAP proposals.
 */
class mod_sap_edit extends mod_sap
{
	/**
	 * Display the SAP with the given PID, or a chooser table if no PID is given.
	 */
	public function display_index()
	{
		//Get prospect ID and check if prospect exists.
		if (isset($_GET['pid']) && prospect::exists($_GET['pid'])) {

			$sap_prospect = new prospect($_GET['pid']);
			$sap_proposal = new proposal($sap_prospect);
			$sap_root = $sap_proposal->build_sap();

			?>
			<div id="save_cancel">
				<input type="hidden" name="prospect_id" value="<?php echo $_GET['pid'];?>"/>
				<input type="submit" id="save_edits" value="Save" a0="action_save_edits"/>
				<input type="submit" id="cancel_edits" value="Cancel" a0="action_cancel_edits"/>
			</div>

			<?php $this->print_edit_controls($sap_proposal); ?>

			<div id="proposal">
				<?php $sap_root->printNode(); ?>
			</div>

			<div class="clear"></div>
			<?php
		} else {
			//Get the prospects to choose from
			$prospects = array();
			$prospects = db::select("
				SELECT prospect_id, name, company, email, url
				FROM sap_sandbox.prospect WHERE prospect_id != 0
			", 'ASSOC');

			?>

			<div id='choose_prospect'>
				<table>
					<tr>
						<th>Name</th>
						<th>Company</th>
						<th>email</th>
						<th>url</th>
					</tr>
					<?php foreach ($prospects as $p) { ?>
					<tr>
						<td><?php echo '<a href="'.cgi::href('sap/edit/?pid='.$p['prospect_id']).'">'.$p['name'].'</a>';?></td>
						<td><?php echo $p['company'];?></td>
						<td><?php echo $p['email'];?></td>
						<td><?php echo $p['url'];?></td>
					</tr>
					<?php } ?>
				</table>
			</div>

			<?php
		}
	}

	protected function print_edit_controls($proposal)
	{
		?>
		<div id="edit_display"></div>

		<div id="edit_buffer"></div>

		<div id="sap_edit_controls">

			<!-- Controls for editing branch nodes -->
			<div id="branch_edit">
				<button type="button" class="add_btn">Add</button>
				<select class="node_type">
					<option value=''>Type</option>
					<?php
					foreach (sap_node::get_node_structures() as $node_type => $node_structure) {
						echo "<option value='$node_structure'>$node_type</option>";
					}
					?>
				</select>
				<button type="button" class="delete_branch_btn">Delete</button>
				<div class="clear"></div>
			</div>

			<!-- Controls for editing leaf nodes -->
			<div class="leaf_edit">
				<textarea class="edit_text"></textarea>
				<button type="button" class="delete_btn">Delete</button>
				<button type="button" class="edit_btn">Edit</button>
				<button type="button" class="cancel_btn">Cancel</button>
				<button type="button" class="save_btn">Save</button>
				<div class="clear"></div>
			</div>

			<!-- Blank nodes to copy and use when adding nodes -->
			<?php
			$blank_branch = $proposal->make_branch(0,'','');
			$blank_leaf = $proposal->make_leaf(0,'','','');
			$blank_branch->printNode();
			$blank_leaf->printNode();
			?>

		</div>
		<?php
	}

	public function action_save_edits()
	{
		$ops_array = $_POST['ops_array'];
		$prospect_id = $_POST['prospect_id'];

		$sap = new proposal(new prospect($prospect_id));
		$sap->perform_operations($ops_array);

		cgi::redirect("sap/edit/?pid=$prospect_id");
	}

	public function action_cancel_edits()
	{
		//Clears post vars in case the user refreshes the page later
		cgi::redirect('sap/edit');
	}
}
?>