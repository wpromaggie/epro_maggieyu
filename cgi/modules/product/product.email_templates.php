<?php

class mod_product_email_templates extends mod_product
{
	// template key, template and templates list
	private $tpl, $tpls;
	
	// this will be pushed to window history
	private $new_tkey;
	
	// true if user has seleted at least 1 option from each section
	private $all_options;
	
	// enumerations for each of the options
	private $all_depts, $all_actions, $all_plans;
	
	// which options are selected
	private $depts, $actions, $plans;
	
	// mapping id, account id
	private $mid, $aid;
	
	// options for types of email
	private static $options = array('depts', 'actions', 'plans');
	
	public function pre_output()
	{
		parent::pre_output();
		
		if ($_REQUEST['tkey'])
		{
			$this->tpl = new email_template(array('tkey' => $_REQUEST['tkey']));
		}
		
		list($this->mid, $this->aid) = util::list_assoc($_REQUEST, 'mid', 'aid');
		
		$this->set_depts();
		$this->set_actions();
		$this->set_plans();
	}
	
	// we just want to send back html for the template iframe, not all e2
	// so exit when done
	public function pre_output_sample_html_iframe()
	{
		$mapping = new email_template_mapping(array('id' => $this->mid));
		$account = product::get_account($mapping->department, $this->aid);
		$external_data = array(
			'[first_month_charge]' => util::format_dollars(sbs_lib::get_recurring_amount($account))
		);
		$fields = $this->tpl->apply($account, $mapping, $external_data, true);
		echo $fields['html'];
		exit;
	}
	
	public function display_index()
	{
		// we always want to do this unless we are first arriving at page
		$this->action_get_templates();
		?>
		<h1>Email Templates</h1>
		<?php
		$this->print_checkbox_menu();
		$this->print_templates();
		if ($this->new_tkey) echo '<input type="hidden" id="new_tkey" value="'.$this->new_tkey.'" />';
	}
	
	private function any_options()
	{
		if (!isset($this->any_options))
		{
			$this->any_options = ($this->depts || $this->actions || $this->plans);
		}
		return $this->any_options;
	}
	
	private function print_checkbox_menu()
	{
		$cboxes_opts = array('separator' => ' &nbsp; ', 'toggle_all' => false);
		?>
		<table class="section">
			<tbody id="template_options">
				<tr>
					<td class="option_label">Depts</td>
					<td><?php echo cgi::html_checkboxes('depts', $this->all_depts, $this->depts, $cboxes_opts); ?></td>
				</tr>
				<tr>
					<td class="option_label">Actions</td>
					<td><?php echo cgi::html_checkboxes('actions', $this->all_actions, $this->actions, $cboxes_opts); ?></td>
				</tr>
				<tr>
					<td class="option_label">Plans</td>
					<td><?php echo cgi::html_checkboxes('plans', $this->all_plans, $this->plans, $cboxes_opts); ?></td>
				</tr>
			</tbody>
		</table>
		<div class="section">
			<div id="get_options_buttons">
				<input type="submit" a0="action_get_templates" value="Get Templates" />
				<input type="submit" id="clear_all_options" value="Clear Options" />
			</div>
			<div id="change_options_buttons">
				<input type="submit" a0="action_change_template_options" value="Submit Changes" />
				<input type="submit" value="Cancel" />
			</div>
		</div>
		<?php
	}
	
	private function set_depts()
	{
		$this->all_depts = email_template_mapping::$department_options;
		$this->depts = util::unnull($_POST['depts'], '');
	}
	
	private function set_actions()
	{
		$this->all_actions = email_template_mapping::$actions;
		$this->actions = util::unnull($_POST['actions'], '');
	}
	
	private function array_from_cboxes_value($val, $all_vals)
	{
		if ($val == '*')
		{
			return $all_vals;
		}
		else
		{
			return explode("\t", $val);
		}
	}
	
	// must be called after set_depts so we have depts
	private function set_plans()
	{
		$ignore_plans = array(
			// some random plans that aren't around anymore
			'ql-Basic', 'ql-GoldQL', 'ql-SMOplat',
			// bo plans
			'ql-Bo1', 'ql-Bo2', 'ql-Bo3', 'ql-Bo4',
			// lal plans
			'ql-LAL', 'ql-LAL1', 'ql-LALgold', 'ql-LALplatinum', 'ql-LALsilver'
		);
		$this->all_plans = array();
		foreach ($this->all_depts as $dept)
		{
			$ac_table = 'ap_'.$dept;
			
			if (class_exists($ac_table))
			{
				// get it, filter it, unique it, map it, diff it, sort it. technologic
				$dept_plans = $ac_table::get_enum_vals('plan');
				$dept_plans = array_filter($dept_plans);
				$dept_plans = array_unique($dept_plans);
				$dept_plans = array_map(create_function('$x', 'return "'.$dept.'-".$x;'), $dept_plans);
				$dept_plans = array_diff($dept_plans, $ignore_plans);
				sort($dept_plans);
				
				$this->all_plans = array_merge($this->all_plans, $dept_plans);
			}
		}
		
		$this->plans = util::unnull($_POST['plans'], '');
	}
	
	private function option_to_db_col($option)
	{
		switch ($option)
		{
			case ('depts'): return 'department';
			case ('actions'): return 'action';
			case ('plans'): return 'plan';
		}
	}
	
	public function action_get_templates()
	{
		if ($_POST)
		{
			$this->all_options = true;
			$where = array();
			foreach (self::$options as $option)
			{
				$selected = $this->$option;
				if ($selected)
				{
					// user selected all options, don't need to add to where
					if ($selected == '*')
					{
					}
					else
					{
						$where[] = $this->option_to_db_col($option)." in ('".str_replace("\t", "','", $selected)."')";
					}
				}
				else
				{
					$this->all_options = false;
				}
			}
			
			$tmp = db::select("
				select distinct tkey
				from eppctwo.email_template_mapping
				".(($where) ? "where ".implode(" && ", $where) : "")."
			");
			
			$this->tpls = db::select("
				select tkey, group_concat(distinct department separator ', ') dept, group_concat(distinct action separator ', ') action, group_concat(distinct plan separator ', ') plan
				from eppctwo.email_template_mapping
				where tkey in ('".implode("','", $tmp)."')
				group by tkey
				order by dept asc, action asc, plan asc
			");
		}
	}
	
	private function print_templates()
	{
		if (isset($this->tpls))
		{
			// show templates that match selections
			if ($this->tpls)
			{
				$ml = '';
				for ($i = 0; list($tkey, $depts, $actions, $plans) = $this->tpls[$i]; ++$i)
				{
					if ($this->tpl && $tkey == $this->tpl->tkey)
					{
						$ml_current_edit = '
							<p>
								<b>Currently Editing:</b>
								<span class="option_display" type="depts">'.$depts.'</span> --
								<span class="option_display" type="actions">'.$actions.'</span> --
								<span class="option_display" type="plans">'.$plans.'</span>
							</p>
							<p>
								<a href="" id="change_options_link">Change Template Options</a> &bull;
								<a href="'.cgi::href('product/email_templates/sample?tkey='.$this->tpl->tkey).'" target="_blank">Generate Sample Email</a>
							</p>
						';
					}
					else
					{
						$ml .= '<p><a class="tpl_link" href="" tkey="'.$tkey.'">'.$depts.' &bull; &bull; '.$actions.' &bull; &bull; '.$plans.'</a></p>';
					}
				}
				$this->ml_templates = '
					<h2>Select template to edit</h2>
					<div id="tpl_list">
						'.$ml.'
					</div>
					<div id="current_edit">
						'.$ml_current_edit.'
					</div>
					<div class="clr"></div>
				';
			}
			// no templates matched
			else
			{
				$this->ml_templates = '<div>No templates match selection.</div>';
				
				// show link to create new template if at least 1 checkbox from each section selected
				if ($this->all_options)
				{
					$this->ml_templates .= '<div><a href="" id="new_tpl_link">Create New Template</a></div>';
				}
				else
				{
					$this->ml_templates .= '<div>Select at least 1 checkbox from each section to create a new template.</div>';
				}
			}
		}
		
		// template form
		if ($this->tpl)
		{
			$ml_form = $this->tpl->html_form();
		}
		else if ($this->all_options)
		{
			$ml_form = email_template::html_form_new();
		}
		$ml_form .= $this->ml_dynamic_replacment_description();
		
		// values for input field autocompletes
		if ($this->all_options || $this->tpl)
		{
			$autocomplete_cols = array('from', 'to', 'cc', 'bcc', 'subject');
			$q_select = '';
			foreach ($autocomplete_cols as $col)
			{
				$q_select .= (($q_select) ? ', ' : '')."group_concat(distinct `$col` separator ',') `$col`";
			}
			$autocomplete_data = db::select_row("
				select $q_select
				from eppctwo.email_template
			", 'ASSOC');
			foreach ($autocomplete_data as &$d)
			{
				$d = array_unique(array_filter(array_map('trim', explode(",", $d))));
				sort($d);
			}
			cgi::add_js_var('autocomplete_data', $autocomplete_data);
		}
		
		echo '
			'.$this->ml_templates.'
			<div id="tpl">
				'.$ml_form.'
			</div>
		';
	}
	
	public function display_sample()
	{
		?>
		<h1>Email Template Sample</h1>
		<?php $this->print_sample_mappings(); ?>
		<?php $this->print_sample_template(); ?>
		<?php
	}
	
	private function print_sample_mappings()
	{
		$mappings = email_template_mapping::get_all(array('where' => "tkey = '{$this->tpl->tkey}'"));
		$ml = '';
		foreach ($mappings as $m)
		{
			$ml_mapping = $m->department.' &bull; &bull; '.$m->action.' &bull; &bull; '.$m->plan;
			
			$do_show_mapping_link = true;
			
			if (!$this->mid || $m->id == $this->mid)
			{
				// strip department from plan
				$plan = substr($m->plan, 3);
				
				$acs = product::get_accounts($m->department, array(
					'select' => "account.id, url",
					'where' => "plan = '".db::escape($plan)."'"
				));
				if ($acs->count() > 0)
				{
					if ($this->aid)
					{
						foreach ($acs as $ac)
						{
							if ($ac->id == $this->aid)
							{
								$this->mapping = $m;
								break;
							}
						}
					}
					else
					{
						$this->mapping = $m;
						$ac = $acs->i(mt_rand(0, $acs->count() - 1));
						list($this->aid, $url) = util::list_assoc($ac->to_array(), 'id', 'url');
					}
					
					// this is our mapping, don't need to show link
					$do_show_mapping_link = false;
					$ml .= '
						<li>
							<span>Showing Sample For:</span>
							<a href="'.cgi::href($this->account_url.'?url_id='.$this->aid).'" target="_blank">'.$url.'</a>
							<span>('.$ml_mapping.')</span>
						</li>
					';
				}
				else if ($this->mid)
				{
					$do_show_mapping_link = false;
					$ml .= '
						<li>
							<span>No active accounts match options: 
							<span>'.$ml_mapping.'</span>
						</li>
					';
				}
			}
			if ($do_show_mapping_link)
			{
				$ml .= '
					<li>
						<a href="'.cgi::href('product/email_templates/sample?tkey='.$this->tpl->tkey.'&mid='.$m->id).'">'.$ml_mapping.'</a>
					</li>
				';
			}
		}
		echo '
			<h2>Options for this template</h2>
			<ul>
				'.$ml.'
			</ul>
		';
	}
	
	private function print_sample_template()
	{
		if ($this->aid)
		{
			if ($this->mapping)
			{
				$account = product::get_account($this->mapping->department, $this->aid);
				$external_data = array(
					'[first_month_charge]' => util::format_dollars(sbs_lib::get_recurring_amount($account->plan))
				);
				$fields = $this->tpl->apply($account, $this->mapping, $external_data, true);
				
				$ml_notes = '';
				if (!$account->account_rep)
				{
					$ml_notes .= '<p>* Account does not have a rep set, rep randomly selected.</p>';
				}
				if (strpos($this->tpl->body, '[first_month_charge]') !== false)
				{
					$ml_notes .= '<p>* [recur_charge] used in place of [first_month_charge] for sample.</p>';
				}
				
				$ml = '';
				foreach ($fields as $key => $val)
				{
					if ($key == 'html')
					{
						$val = ($val) ?
							'<iframe id="html_sample" src="'.cgi::href('product/email_templates/sample_html_iframe?tkey='.$this->tpl->tkey.'&aid='.$this->aid.'&mid='.$this->mapping->id).'"></iframe>'
							:
							''
						;
					}
					else
					{
						$val = str_replace("\n", "<br>\n", $val);
					}
					$ml .= '
						<tr>
							<td><b>'.util::display_text($key).':</b></td>
							<td>'.$val.'</td>
						</tr>
					';
				}
				
				// only show send button if we have an account id as part of the request
				if ($_REQUEST['aid'])
				{
					$ml_send = '
						<div>
							<input type="submit" a0="action_send_email" value="Actually Send Email" />
						</div>
					';
				}
				else
				{
					$ml_send = '';
				}
				
				echo '
					<h2>Sample Email Fields</h2>
					'.$ml_send.'
					<div id="sample_notes">
						'.$ml_notes.'
					</div>
					<table>
						<tbody>
							'.$ml.'
						</tbody>
					</table>
				';
			}
			else
			{
				echo '<div>Template options do not match account '.$this->aid.'</div>';
			}
		}
	}
	
	public function action_send_email()
	{
		if (!$this->aid || !$this->mid)
		{
			feedback::add_error_msg('Cannot send email without account');
		}
		$mapping = new email_template_mapping(array('id' => $this->mid));
		$account = product::get_account($mapping->department, $this->aid);
		if (sbs_lib::send_email($account, $mapping->action, array()))
		{
			feedback::add_success_msg('Email sent');
		}
	}
	
	private function ml_dynamic_replacment_description()
	{
		$email_vars = $this->ml_dynamic_replacment_description_vars('addrs');
		$content_vars = $this->ml_dynamic_replacment_description_vars('content');
		
		return '
			<fieldset>
				<legend>Template Variables</legend>
				<table>
					<tbody>
						<tr>
							<td><b>Email Addresses</b></td>
							<td>'.$email_vars.'</td>
						</tr>
						<tr>
							<td><b>Subject and Body</b></td>
							<td>'.$content_vars.'</td>
						</tr>
					</tbody>
				</table>
			</fieldset>
			<div class="clr"></div>
		';
	}
	
	private function ml_dynamic_replacment_description_vars($key)
	{
		$ml = '';
		foreach (email_template::$template_vars[$key]['vars'] as $group => $vars)
		{
			$ml .= '
				<p>
					<span>'.strtoupper($group).':</span>
					<span>'.implode(', ', $vars).'</span>
				</p>
			';
		}
		return $ml;
	}
	
	public function action_email_template_submit()
	{
		// updating an existing temlpate
		if ($this->tpl)
		{
			$updates = $this->tpl->put_from_post();
			feedback::add_success_msg('Temlpate updated ('.implode(', ', $updates).')');
		}
		else
		{
			$this->tpl = email_template::new_with_tkey();
			
			// this will be pushed to window history
			$this->new_tkey = $this->tpl->tkey;
			
			$_POST['email_template_body'] = str_replace("\r", '', $_POST['email_template_body']);
			$this->tpl->put_from_post();
			
			$this->set_mappings();
			
			feedback::add_success_msg('New template created');
		}
	}
	
	public function action_change_template_options()
	{
		$this->set_mappings(true);
	}
	
	private function set_mappings($is_change = false)
	{
		// create mappings
		$depts_array = $this->array_from_cboxes_value($this->depts, $this->all_depts);
		$actions_array = $this->array_from_cboxes_value($this->actions, $this->all_actions);
		$plans_array = $this->array_from_cboxes_value($this->plans, $this->all_plans);
		
		if ($is_change)
		{
			db::delete("eppctwo.email_template_mapping", "tkey = '{$this->tpl->tkey}'");
		}
		
		foreach ($depts_array as $dept)
		{
			foreach ($actions_array as $action)
			{
				foreach ($plans_array as $plan)
				{
					// always show mapping collisions
					$c = email_template_mapping::count(array("where" => "
						department = '".db::escape($dept)."' &&
						action = '".db::escape($action)."' &&
						plan = '".db::escape($plan)."'
					"));
					if ($c)
					{
						feedback::add_error_msg("Option collision for: $dept, $action, $plan");
					}
					else
					{
						$mapping = new email_template_mapping(array(
							'tkey' => $this->tpl->tkey,
							'department' => $dept,
							'action' => $action,
							'plan' => $plan
						));
						$mapping->put();
						
						// give feedback success if we are changing
						if ($is_change)
						{
							feedback::add_success_msg("Option added for: $dept, $action, $plan");
						}
					}
				}
			}
		}
	}
}

?>