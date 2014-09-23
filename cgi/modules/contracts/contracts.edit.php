<?php

class mod_contracts_edit extends mod_contracts
{
	
	public function display_index()
	{
		$root = $this->proposal->build_contract();
		?>

		<div id="save_cancel">
			<input type="submit" id="save_edits" value="Save" a0="action_save_edits"/>
			<input type="submit" id="cancel_edits" value="Cancel" a0="action_cancel_edits"/>
		</div>

		<?php $this->print_edit_controls(); ?>

		<div id="proposal">
			<?php $root->printNode(); ?>
		</div>

		<div class="clear"></div>
			
		<?php
	}
	
	private function get_default_terms($layout){
		return db::select_row("
			SELECT * 
			FROM contracts.prospect_terms_text 
			WHERE prospect_id=0 AND layout = '$layout'
		", 'ASSOC');
	}
	
	public function display_terms(){
		
		$layout = "";
		
		if($_GET['pid']){
			
			$layout = $this->prospect->layout;
			
			$terms = db::select_row("
				SELECT * 
				FROM contracts.prospect_terms_text 
				WHERE prospect_id={$_GET['pid']} AND layout = '$layout'
			", 'ASSOC');
				
			if(empty($terms)){
				$terms = $this->get_default_terms($layout);
			}
			
				
		} else {
			
			$layout = (empty($_POST['layout'])) ? 'Agency Services' : $_POST['layout'];
			$layout_options = "";
			foreach(proposal::get_layouts() as $l){
				$selected = ($layout==$l) ? ' selected' : '';
				$layout_options .= "<option value='$l'$selected>$l</option>";
			}
			
			$terms = $this->get_default_terms($layout);
			
		}
		
	?>
		<?php if(!empty($layout_options)){ ?>
		
			<div style="margin-bottom: 10px;">
				<select id="layout_select" name="layout">
					<?php echo $layout_options; ?>
				</select>
			</div>
		
		<?php } else { ?>
			
			<input type="hidden" name="layout" value="<?php echo $layout ?>" />
		
		<?php } ?>
		
		<div id="contract_terms">
			<textarea name="text"><?php echo $terms['text'] ?></textarea>
			<input type="submit" a0="save_display_terms" value="Save" />
			<?php if($_GET['pid']){ ?><input type="submit" a0="reset_display_terms" value="Reset" /> <?php } ?>
		</div>
		
	<?php	
	}
	
	protected function save_display_terms(){
		$porspect_id = isset($_GET['pid'])?$_GET['pid']:0;
		db::insert_update(
			'contracts.prospect_terms_text',
			array('prospect_id', 'layout'),
			array(
			    'prospect_id' => $porspect_id,
			    'text' => $_POST['text'],
			    'layout' => $_POST['layout']
			)
		);
	}
	
	protected function reset_display_terms(){
		$text = db::select_one("SELECT text FROM contracts.prospect_terms_text WHERE prospect_id=0", 'ASSOC');
		db::update(
			"contracts.prospect_terms_text",
			array("text" => $text),
			"prospect_id = {$_GET['pid']}"
		);
	}

	protected function print_edit_controls()
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
					foreach (node::get_node_structures() as $node_type => $node_structure) {
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
			$blank_branch = $this->proposal->make_branch(0,'','');
			$blank_leaf = $this->proposal->make_leaf(0,'','','');
			$blank_branch->printNode();
			$blank_leaf->printNode();
			?>

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
		cgi::redirect('contracts/edit/?pid='.$_GET['pid']);
	}
}
?>