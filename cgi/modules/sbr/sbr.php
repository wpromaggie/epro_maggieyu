<?php

class mod_sbr extends module_base
{
	public function pre_output()
	{
		util::load_lib('sbs');
	}
	
	public function get_menu()
	{
		return new Menu(
			array(
				new MenuItem('New Order', 'new_order'),
				new MenuItem('Reps', 'reps', array('role' => 'Leader')),
				new MenuItem('Partners &amp; Sources', 'partners_and_sources', array('role' => 'Leader')),
				new MenuItem('Upload Leads', 'upload_leads', array('role' => 'Leader')),
				new MenuItem('Accounting', 'accounting', array('role' => 'Leader'))
			),
			'sbr'
		);
	}
	
	public function display_index()
	{
		$protocol = (util::is_dev()) ? 'http' : 'https';
		$base_url = $protocol.'://'.\epro\WPRO_DOMAIN.'/';
		?>
		<h1>Small Business Rep Home</h1>
		<p>My Rep ID: <?php echo user::$id; ?></p>
		<p>QL Order Form: <?php echo $base_url.'quicklist/order?sbrid='.user::$id; ?></p>
		<p>SB Order Form: <?php echo $base_url.'socialboost/order?sbrid='.user::$id; ?></p>
		<p>GS Order Form: <?php echo $base_url.'goseo/order?sbrid='.user::$id; ?></p>
		<?php
		$this->print_ql_pro_link();
	}
	
	private function print_ql_pro_link()
	{
		if (user::has_role('QL Pro', 'sales'))
		{
			echo '
				<p>
					<a href="'.cgi::href('sales/add_ql_pro').'">QL Pro</a>
				</p>
			';
		}
	}
	
	public function pre_output_partners_and_sources()
	{
		$this->partners = sbr_partner::get_all(array(
			'select' => array(
				'sbr_partner' => array('id', 'status'),
				'sbr_source' => array('sbr_partner_id', 'id as sid', 'status as sstatus')
			),
			'left_join_many' => array('sbr_source' => 'sbr_partner.id = sbr_source.sbr_partner_id'),
			'where' => "sbr_partner.status = 'On'",
			'order_by' => 'sbr_partner.id asc',
			'key_col' => 'id',
			'de_alias' => true
		));

		$this->partners->merge(sbr_partner::get_all(array(
			'select' => array(
				'sbr_partner' => array('id', 'status'),
				'sbr_source' => array('sbr_partner_id', 'id as sid', 'status as sstatus')
			),
			'left_join_many' => array('sbr_source' => 'sbr_partner.id = sbr_source.sbr_partner_id'),
			'where' => "sbr_partner.status = 'Off'",
			'order_by' => 'sbr_partner.id asc',
			'key_col' => 'id',
			'de_alias' => true
		)));
	}
	
	private function get_status_link($obj)
	{
		$action = ($obj->status == 'On') ? 'pause' : 'activate';
		return '<a href="" class="status_link">'.ucwords($action).'</a>';
	}
	
	public function display_partners_and_sources()
	{
		if (!$this->is_user_leader()){
			$this->display_index();
			return;
		}
		$ml_partners = '';
		foreach ($this->partners as &$p){

			$ml_sources = '';

			if (!empty($p->sbr_source)){
				foreach ($p->sbr_source as &$s){
					$ml_sources .= '
						<li class="container" type="source">
							<div status="'.$s->status.'">
								<span class="source_id">'.$s->id.'</span>
								<span class="obj_links">
									<a href="" class="edit_link">Edit</a>
									'.$this->get_status_link($s).'
								</span>
							</div>
						</li>
					';
				}
			}
			
			$ml_partners .= '
				<li class="container" type="partner">
					<div status="'.$p->status.'">
						<span class="partner_id"><a href="">'.$p->id.'</a></span>
						<span class="obj_links">
							<a href="" class="edit_link">Edit</a>
							'.$this->get_status_link($p).'
						</span>
					</div>
					<div class="sources_wrapper">
						<div>
							<label for="new_source_'.$p->id.'"> + New Source:</label>
							<input type="text" id="new_source_'.$p->id.'" name="new_source_'.$p->id.'" />
							<input type="submit" class="small_button new_source_submit" a0="action_new_source_submit" value="Submit" />
						</div>
						<ul class="sources">
							'.$ml_sources.'
						</ul>
					</div>
				</li>
			';
		}
		?>
		<h1>Partners &amp; Sources</h1>
		
		<div>
			<label for="new_partner"> + New Partner:</label>
			<input type="text" id="new_partner" name="new_partner" />
			<input type="submit" class="small_button new_partner_submit" a0="action_new_partner_submit" value="Submit" />
		</div>
		
		<ul class="partners">
			<?php echo $ml_partners; ?>
		</ul>
		<input type="hidden" id="source_partner_id" name="source_partner_id" value="<?php echo htmlentities($_POST['source_partner_id']); ?>" />
		<input type="hidden" id="action_id" name="action_id" value="" />
		<input type="hidden" id="action_val" name="action_val" value="" />
		<?php
	}
	
	public function action_new_partner_submit()
	{
		$pid = $_POST['new_partner'];
		if ($this->is_valid_id($pid, 'partner'))
		{
			$p = new sbr_partner(array(
				'id' => $pid,
				'status' => 'On'
			));
			
			if ($p->put())
			{
				// create empty sbr_source rs array
				$p->sbr_source = sbr_source::new_array();
				$this->partners->push($p);
				feedback::add_success_msg('New partner added');
			}
			else
			{
				feedback::add_error_msg('Error adding new partner. '.db::last_error());
			}
		}
	}
	
	private function is_valid_id($id, $type, $parent_id = '')
	{
		if (preg_match("/[^\w-]/", $id, $matches))
		{
			feedback::add_error_msg('Please enter a valid name (letters, numbers, underscores and dashes). Offending character: '.str_replace(' ', '[space]', $matches[0]));
			return false;
		}
		if ($id == '')
		{
			feedback::add_error_msg('Name appears to be empty.');
			return false;
		}
		$class = 'sbr_'.$type;
		$count = $class::count(array(
			"where" => "
				binary id = '".db::escape($id)."'
				".(($type == 'source') ? (" && binary sbr_partner_id = '".db::escape($parent_id)."'") : '')."
			"
		));
		if ($count)
		{
			feedback::add_error_msg('Name <i>'.$id.'</i> already exists.');
			return false;
		}
		return true;
	}
	
	public function action_new_source_submit()
	{
		$pid = $_POST['source_partner_id'];
		$sid = $_POST['new_source_'.$pid];
		
		if ($this->is_valid_id($sid, 'source', $pid))
		{
			$s = new sbr_source(array(
				'sbr_partner_id' => $pid,
				'id' => $sid,
				'status' => 'On'
			));
			
			if ($s->put())
			{
				$p = &$this->partners->i($pid);
				$p->sbr_source->push($s);
				feedback::add_success_msg('New source added');
			}
			else
			{
				feedback::add_error_msg('Error adding new source. '.db::last_error());
			}
		}
	}
	
	private function do_update_partner($pid, $field, $new_val)
	{
		if ($field != 'id' || $this->is_valid_id($new_val, 'partner'))
		{
			$p = &$this->partners->i($pid);
			
			// update sources with new pid
			if ($field == 'id')
			{
				$p->sbr_source->sbr_partner_id = $new_val;
				$p->sbr_source->put();
				$p->update_primary_key(array($field => $new_val));
			}
			else
			{
				$p->update_from_array(array($field => $new_val));
			}
			
			feedback::add_success_msg($p->id.' updated.');
		}
	}
	
	private function do_update_source($pid, $sid, $field, $new_val)
	{
		//e($this->partners);
		//e($pid);
		$p = &$this->partners->i($pid);

		if ($field != 'id' || $this->is_valid_id($new_val, 'source', $pid))
		{
			$s = $p->sbr_source->find('id', $sid);
			if ($field == 'id')
			{
				$s->update_primary_key(array($field => $new_val));
			}
			else
			{
				$s->update_from_array(array($field => $new_val));
			}
			
			feedback::add_success_msg($s->id.' updated.');
		}
	}
	
	public function action_partner_pause()
	{
		$this->do_update_partner($_POST['action_id'], 'status', 'Off');
	}
	
	public function action_partner_activate()
	{
		$this->do_update_partner($_POST['action_id'], 'status', 'On');
	}
	
	public function action_partner_edit()
	{
		$this->do_update_partner($_POST['action_id'], 'id', $_POST['action_val']);
	}
	
	public function action_source_pause()
	{
		$this->do_update_source($_POST['source_partner_id'], $_POST['action_id'], 'status', 'Off');
	}
	
	public function action_source_activate()
	{
		$this->do_update_source($_POST['source_partner_id'], $_POST['action_id'], 'status', 'On');
	}
	
	public function action_source_edit()
	{
		$this->do_update_source($_POST['source_partner_id'], $_POST['action_id'], 'id', $_POST['action_val']);
	}
	
	public function display_reps()
	{
		if (!$this->is_user_leader())
		{
			$this->display_index();
			return;
		}
		$users = db::select("
			select u.id, u.realname, ar.users_id
			from eppctwo.users u
				left outer join eppctwo.sbr_active_rep ar on u.id = ar.users_id
			where u.password <> ''
			group by id, realname
			order by u.realname asc
		");
		$num_users = count($users);
		$num_cols = 6;
		$num_rows = ceil($num_users / $num_cols);
		$ml = '';
		for ($i = 0; $i < $num_rows; ++$i)
		{
			$ml_row = '';
			for ($j = 0; $j < $num_cols; ++$j)
			{
				$index = ($j * $num_rows) + $i;
				if ($index < $num_users)
				{
					list($uid, $name, $is_active) = $users[$index];
					$input_id = 'u'.$uid;
					$ml_row .= '
						<td>
							<input type="checkbox" class="rep" rid="'.$uid.'" id="'.$input_id.'"'.(($is_active) ? ' checked' : '').' />
							<label for="'.$input_id.'">'.$name.' ('.$uid.')</label>
						</td>
					';
				}
			}
			$ml .= '
				<tr>
					'.$ml_row.'
				</tr>
			';
		}
		?>
		<h1>Reps</h1>
		<table>
			<tbody>
				<?php echo $ml; ?>
			</tbody>
		</table>
		<input type="submit" id="reps_submit" a0="action_reps_submit" value="Submit" />
		<input type="hidden" id="changes" name="changes" value="" />
		<?php
	}
	
	public function action_reps_submit($a = array())
	{
		$changes = json_decode($_POST['changes'], true);
		foreach ($changes as $uid => $on_or_off)
		{
			if ($on_or_off == 'On')
			{
				db::insert("eppctwo.sbr_active_rep", array('users_id' => $uid));
				$is_in_sbr = db::select_one("
					select user_id
					from eppctwo.user_guilds
					where user_id = '$uid' && guild_id = 'sbr'
				");
				if (!$is_in_sbr)
				{
					db::insert("eppctwo.user_guilds", array(
						'user_id' => $uid,
						'guild_id' => 'sbr',
						'role' => 'Member'
					));
				}
			}
			else
			{
				db::delete("eppctwo.sbr_active_rep", "users_id = :id", array('id' => $uid));
				// do not want to remove from guild so reps who are no longer active
				// still remain in account page drop downs
			}
		}
		
		feedback::add_success_msg('Reps updated.');
	}
	
	public function display_upload_leads()
	{
		?>
		<h1>Upload Leads</h1>
		<table>
			<tbody>
				<?php $this->print_partner_source_input(); ?>
				<tr>
					<td>File</td>
					<td><input type="file" id="leads_file" name="leads_file" /></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" id="leads_submit" a0="action_upload_leads" value="Upload" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	
	public function action_upload_leads()
	{
		util::load_lib('excel');
		util::load_lib('sales');
		require(\epro\WPROPHP_PATH.'excel/PHPExcel/PHPExcel/Reader/Excel2007.php');
		
		// partner and source of sbr map to referer and source of contact
		list($partner, $source) = util::list_assoc($_POST, 'partner', 'source');
		$now = date(util::DATE_TIME);
		
		$reps = db::select("
			select u.realname, u.id
			from eppctwo.users u, eppctwo.sbr_active_rep ar
			where u.password <> '' && u.id = ar.users_id
		", 'NUM', 0);
		
		$reader = new PHPExcel_Reader_Excel2007();
		$reader->setReadDataOnly(true);
		$xls = $reader->load($_FILES['leads_file']['tmp_name']);
		
		$num_dups = $num_new = $skipped_intl = 0;
		$all_contacts = array();
		
		// only look at first sheet so other sheets can be used for whatever user wants
		$sheet = $xls->getSheet(0);
		$title = $sheet->getTitle();
		$rows = $sheet->getRowIterator();
		foreach ($rows as $j => $row)
		{
			// check this first, skip if "Foreign"
			$us_or_intl = $sheet->getCell('I'.$j)->getFormattedValue();
			if ($us_or_intl == 'Foreign')
			{
				$skipped_intl++;
				continue;
			}
			$excel_date = $sheet->getCell('A'.$j)->getFormattedValue();
			$php_timestamp = mktime(0, 0, 0, 1, $excel_date - 1, 1900);
			$file_date = date('Y-m-d', $php_timestamp);
			
			// ignore file date, use the day upload was processed
			$date = $now;
			
			$name = $sheet->getCell('B'.$j)->getFormattedValue();
			$phone = preg_replace("/\D/", '', $sheet->getCell('C'.$j)->getFormattedValue());
			
			// doesn't matter
			//$avv_source = $sheet->getCell('D'.$j)->getFormattedValue();
			
			// doesn't matter
			//$sic = $sheet->getCell('E'.$j)->getFormattedValue();
			
			$email = $sheet->getCell('F'.$j)->getFormattedValue();
			$url = $sheet->getCell('G'.$j)->getFormattedValue();
			// H blank
			
			// blank row?
			if (!$email && !$phone)
			{
				continue;
			}
			
			$contact = array(
				'name' => $name,
				'email' => $email,
				'phone' => $phone,
				'url' => $url,
				'created' => $now,
				'referer' => $partner,
				'source' => $source
			);
			
			// slow :'(
			$is_dup = db::select_one("select count(*) from eppctwo.sbs_contacts where email = '".db::escape($email)."' && phone = '".db::escape($phone)."'");
			if ($is_dup)
			{
				$num_dups++;
			}
			else
			{
				$num_new++;
				db::insert("eppctwo.sbs_contacts", $contact);
			}
			// avv handles dups in their own way, send to avv whether it is a dup or not
			$all_contacts[] = $contact;
		}
		$r = sales_lib::post_to_avv($all_contacts);
		feedback::add_msg('Number of new: '.$num_new, 'alert');
		feedback::add_msg('Number of skipped (international): '.$skipped_intl, 'alert');
		feedback::add_msg('Number of duplicates: '.$num_dups, 'alert');
	}
	
	public function pre_output_new_ww_account()
	{
		util::load_lib('ww');
	}
	
	public function display_new_ww_account()
	{
		$year = date('Y');
		$years = range($year, $year + 15);
		?>
		<h1>Create New WebWorks Client Account</h1>
		<table>
			<tbody>
				
				<?php
				$this->print_header('Lead Info');
				$this->print_partner_source_input();
				
				$this->print_header('Purchase Options');
				?>
				<tr>
					<td>Plan</td>
					<td><?php echo cgi::html_select('plan', ww_account::$plan_options, null, array('class' => 'purchase_option')); ?></td>
				</tr>
				<tr>
					<td>Contract Length</td>
					<td><?php echo cgi::html_select('contract_length', ww_account::$contract_length_options, null, array('class' => 'purchase_option')); ?></td>
				</tr>
				<tr>
					<td>Landing Page</td>
					<td><?php echo cgi::html_select('landing_page', ww_account::$landing_page_options, null, array('class' => 'purchase_option')); ?></td>
				</tr>
				<tr>
					<td>Extra Pages</td>
					<td><?php echo cgi::html_select('extra_pages', range(0, 50), null, array('class' => 'purchase_option')); ?></td>
				</tr>
				<?php
				
				$this->print_header('Personal');
				$this->print_text_input_row('name');
				$this->print_text_input_row('email');
				$this->print_text_input_row('phone');
				
				$this->print_header('Site');
				$this->print_text_input_row('url');
				$this->print_text_input_row('comments');
				
				$this->print_header('Billing');
				?>
				<tr>
					<td>Monthly Total</td>
					<td id="monthly_total">$0.00</td>
				</tr>
				<tr>
					<td>Today's Total</td>
					<td id="today_total">$0.00</td>
				</tr>
				<tr>
					<td>Cc Type</td>
					<td><?php echo cgi::html_select('cc_type', array('amex', 'visa', 'mc', 'disc')); ?></td>
				</tr>
				<?php
				$this->print_text_input_row('cc_name', 'Billing Name');
				$this->print_text_input_row('cc_number_text', 'Cc Number');
				?>
				<tr>
					<td>Exp Month</td>
					<td>
						<select name="cc_exp_month">
							<option value="01">01 - Jan</option>
							<option value="02">02 - Feb</option>
							<option value="03">03 - Mar</option>
							<option value="04">04 - Apr</option>
							<option value="05">05 - May</option>
							<option value="06">06 - Jun</option>
							<option value="07">07 - Jul</option>
							<option value="08">08 - Aug</option>
							<option value="09">09 - Sep</option>
							<option value="10">10 - Oct</option>
							<option value="11">11 - Nov</option>
							<option value="12">12 - Dec</option>
						</select>
					</td>
				</tr>
				<tr>
					<td>Exp Year</td>
					<td><?php echo cgi::html_select('cc_exp_year', $years); ?></td>
				</tr>
				<?php
				$this->print_text_input_row('jk_security_code', 'Security Code');
				$this->print_country_select();
				$this->print_text_input_row('cc_zip');
				
				if (user::is_developer()) $this->print_dbg_row();
				
				?>
				<tr>
					<td></td>
					<td><input type="submit" a0="action_new_ww_account" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		
		<!-- some fields that are normally set by wpro -->
		<input type="hidden" name="dept" value="ww" />
		<input type="hidden" name="pay_option" value="standard" />
		<input type="hidden" name="setup_fee" value="0" />
		<input type="hidden" name="sales_rep" value="<?php echo user::$id; ?>" />
		<input type="hidden" name="ip" value="<?php echo cgi::$ip ?>" />
		<input type="hidden" name="browser" value="<?php echo htmlentities($_SERVER['HTTP_USER_AGENT']); ?>" />
		<input type="hidden" name="referer" value="e2" />
		<?php
		cgi::add_js_var('pricing', ww_account::$pricing);
	}
	
	public function ajax_get_billing_totals()
	{
		list($plan, $contract_length, $landing_page, $extra_pages) = util::list_assoc($_POST, 'plan', 'contract_length', 'landing_page', 'extra_pages');
		
		$amounts = ww_account::get_billing_amounts($plan, $contract_length, $landing_page, $extra_pages);
		echo json_encode(array(
			'first_month' => $amounts['first_month'],
			'monthly' => $amounts['recurring']
		));
	}
}

?>