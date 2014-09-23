<?php

class mod_accounting_types extends mod_accounting
{
	public function pre_output()
	{
		parent::pre_output();
		$this->display_default = 'dept_types';
	}
	
	public function head()
	{
		$page_menu = array(
			array('dept_types', 'Department Types'),
			array('define_depts', 'Define Departments'),
			array('define_types', 'Define Types'),
			array('define_events', 'Define Events')
		);
		?>
		<h1><?= implode(' :: ', array_map(array('util', 'display_text'), g::$pages)) ?></h1>
		<?php echo $this->page_menu($page_menu, 'accounting/types/'); ?>
		<?php
	}
	
	public function pre_output_dept_types()
	{
		$this->types = ppe_type::get_all(array('select' => "type"));
		$this->depts = ppe_dept::get_all(array('select' => "dept"));
		
	}
	
	public function display_dept_types()
	{
		?>
		<fieldset>
			<legend>Edit Mapping</legend>
			<table id="edit_mapping_table">
				<tbody>
					<tr>
						<td>Type</td>
						<td id="w_type"><?= cgi::html_radio('type', $this->types->type, false, array('separator' => ' &nbsp; ')) ?></td>
					</tr>
					<tr class="hide">
						<td>Depts</td>
						<td id="w_depts"><?= cgi::html_checkboxes('depts', $this->depts->dept, array(), array('separator' => ' &nbsp; ', 'toggle_all' => false)) ?></td>
					</tr>
					<tr class="hide">
						<td></td>
						<td><input type="submit" a0="action_dept_type_edit_submit" value="Submit" /></td>
					</tr>
					<tr class="hide">
						<td></td>
						<td id="dept_changes">
							<div>
								<label>New Depts:</label>
								<span class="dept_change_details" id="w_new_depts"></span>
							</div>
							<div>
								<label>Removed Depts:</label>
								<span class="dept_change_details" id="w_removed_depts"></span>
							<div>
						</td>
					</tr>
				</tbody>
			</table>
			<input type="hidden" name="posted_type" id="posted_type" value="<?= $_POST['type'] ?>" />
			<input type="hidden" name="new_depts" id="new_depts" value="" />
			<input type="hidden" name="removed_depts" id="removed_depts" value="" />
		</fieldset>
		<div class="clr"></div>
		
		<fieldset id="w_cur_mappings">
			<legend>Current Mappings</legend>
			<?= $this->ml_cur_mappings() ?>
			<div class="clr"></div>
		</fieldset>
		<div class="clr"></div>
		<?php
	}
	
	private function ml_cur_mappings()
	{
		$mappings = dept_and_payment_type::get_all(array(
			'key_col' => 'dept',
			'key_grouped' => true
		));
		
		$ml = '';
		foreach ($mappings as $dept => $dept_types)
		{
			$ml_types = '';
			foreach ($dept_types as $d_and_t)
			{
				$ml_types .= "<p class=\"type\">{$d_and_t->type}</p>";
			}
			$ml .= '
				<table class="cur_mapping">
					<tbody>
						<tr>
							<td class="dept">'.$dept.'</td>
						</tr>
						<tr>
							<td>
								'.$ml_types.'
							</tr>
						</tr>
					</tbody>
				</table>
			';
		}
		return $ml;
	}
	
	public function action_dept_type_edit_submit()
	{
		$type = $new_depts = $removed_depts = false;
		extract($_POST, EXTR_IF_EXISTS);
		
		$successfully_added = array();
		$new_depts = array_filter(array_map('trim', explode("\t", trim($new_depts))));
		foreach ($new_depts as $dept)
		{
			$r = dept_and_payment_type::create(array(
				'dept' => $dept,
				'type' => $type
			));
			if ($r)
			{
				$successfully_added[] = $dept;
			}
			else
			{
				feedback::add_error_msg('Error adding '.$dept.' for type '.$type.': '.rs::get_error());
			}
		}
		if ($successfully_added)
		{
			feedback::add_success_msg(implode(', ', $successfully_added).' added for type '.$type);
		}
		
		$successfully_removed = array();
		$removed_depts = array_filter(array_map('trim', explode("\t", trim($removed_depts))));
		foreach ($removed_depts as $dept)
		{
			$d_and_t = new dept_and_payment_type(array(
				'dept' => $dept,
				'type' => $type
			));
			if ($d_and_t->delete())
			{
				$successfully_removed[] = $dept;
			}
			else
			{
				feedback::add_error_msg('Error removing '.$dept.' for type '.$type.': '.rs::get_error());
			}
		}
		if ($successfully_removed)
		{
			feedback::add_success_msg(implode(', ', $successfully_removed).' removed for type '.$type);
		}
	}
	
	public function display_define_types()
	{
		$this->print_enum_def('ppe_type');
	}
	
	public function display_define_events()
	{
		$this->print_enum_def('pe_event');
	}
	
	public function display_define_depts()
	{
		$this->print_enum_def('ppe_dept');
	}
	
	private function print_enum_def($classname)
	{
		$items = $classname::get_all();
		cgi::add_js_var('items', $items);
		?>
		<div id="w_new_item">
			<label for="new_item">New <?= util::display_text($classname::$enum_col); ?></label>
			<input type="text" name="new_item" id="new_item" value="" focus_me="1" />
			<input type="submit" a0="action_new_item" value="Submit" />
		</div>
		<div id="items" ejo></div>
		<input type="hidden" id="item_id" name="item_id" value="" />
		<input type="hidden" id="edit_item" name="edit_item" value="" />
		<input type="hidden" id="enum_type" name="enum_type" value="<?= $classname ?>" />
		<input type="hidden" id="enum_col" name="enum_col" value="<?= $classname::$enum_col ?>" />
		<?php
	}
	
	public function action_new_item()
	{
		list($classname, $enum_col, $new_item) = util::list_assoc($_POST, 'enum_type', 'enum_col', 'new_item');
		$r = $classname::create(array($enum_col => $new_item));
		if ($r)
		{
			feedback::add_success_msg('New item <i>'.$new_item.'</i> added');
		}
		else
		{
			feedback::add_error_msg('Error creating new item');
		}
	}
	
	public function action_edit_item()
	{
		$enum_type = $enum_col = $item_id = $edit_item = null;
		extract($_POST, EXTR_IF_EXISTS);
		
		$item = new $enum_type(array('id' => $item_id));
		$prev_item_val = $item->$enum_col;
		if ($item->update_from_array(array($enum_col => $edit_item)))
		{
			feedback::add_success_msg(util::display_text($enum_type).' updated ('.$prev_item_val.' -> '.$edit_item.')');
		}
		else
		{
			feedback::add_error_msg('Could not update item: '.rs::get_error());
		}
	}
	
	public function action_delete_item()
	{
		$enum_type = $enum_col = $item_id = null;
		extract($_POST, EXTR_IF_EXISTS);
		
		$item = new $enum_type(array('id' => $item_id));
		$payments_count = payment_part::count(array("where" => "$enum_col = '".db::escape($item->$enum_col)."'"));
		if ($payments_count == 0)
		{
			if ($item->delete())
			{
				feedback::add_success_msg('<i>'.$enum_col.'</i> \''.$item->$enum_col.'\' deleted.');
			}
			else
			{
				feedback::add_error_msg('Could not delete item: '.rs::get_error());
			}
		}
		else
		{
			feedback::add_error_msg('Could not delete item: '.$payments_count.' payments have <i>'.$enum_col.'</i> \''.$item->$enum_col.'\'');
		}
	}
}

?>