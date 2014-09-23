<?php

class email_template extends rs_object
{
	public static $db, $cols, $primary_key;
	
	// 1st level = type
	// vars' keys = group
	// var names can appear in different types, but should be unique within a given type
	public static $template_vars = array(
		'addrs' => array(
			'fields' => array('from', 'to', 'cc', 'bcc'),
			'vars' => array(
				'all' => array(
					'[client_email]',
					'[rep_email]'
				)
			)
		),
		'content' => array(
			'fields' => array('subject', 'plain', 'html'),
			'vars' => array(
				'all' => array(
					'[client_name]',
					'[client_email]',
					'[url]',
					'[plan]',
					'[oid]',
					'[contract_length]',
					'[prev_bill_date]',
					'[next_bill_date]',
					'[today]',
					'[login_link]',
					'[recur_charge]',
					'[months]',
					'[months_minus_1]',
					'[savings_text]',
					'[savings_amount]',
					'[rep_name]',
					'[rep_email]',
					'[rep_phone]'
				),
				'ql' => array(
					'[ql_ads_html]',
					'[ql_ads_text]',
					'[title]',
					'[description]',
					'[disp_url]',
					'[dest_url]',
					'[keywords]'
				),
				'sb' => array(
					'[all_ads_html]',
					'[all_ads_plain]',
					'[sb_exp_link]'
				),
				'ql,report' => array(
					'[30_days_ago]',
					'[imps]',
					'[clicks]',
					'[ctr]',
					'[ave_position]'
				),
				'sb,report' => array(
					'[30_days_ago]',
					'[imps]',
					'[clicks]'
				),
				'signup' => array(
					'[first_month_charge]'
				)
			)
		)
	);
	
	// account and map we are applying template to
	private $account, $tpl_map, $contact;
	
	// are we sending an email for more than one account?
	private $is_combined;
	
	// do not set anything as we generate temlpate
	// values that can be set: account_rep
	private $read_only;
	
	// override who email would normally be sent to
	// makes easy to test
	private $to_override;
	
	// var data from various sources
	private $misc_data;

	// ql data sources
	private $data_sources = false;
	
	public static function set_table_definition()
	{
		
		self::$db = 'eppctwo';
		self::$primary_key = array('tkey');
		self::$cols = self::init_cols(
			new rs_col('tkey'   ,'char'   ,32  ,''  ,rs::NOT_NULL | rs::READ_ONLY),
			new rs_col('from'   ,'varchar',256 ,''  ,rs::NOT_NULL),
			new rs_col('to'     ,'varchar',256 ,''  ,rs::NOT_NULL),
			new rs_col('cc'     ,'varchar',256 ,''  ,rs::NOT_NULL),
			new rs_col('bcc'    ,'varchar',256 ,''  ,rs::NOT_NULL),
			new rs_col('subject','varchar',256 ,''  ,rs::NOT_NULL),
			new rs_col('plain'  ,'text'   ,null,''  ,rs::NOT_NULL),
			new rs_col('html'   ,'text'   ,null,''  ,rs::NOT_NULL)
		);
	}
	
	// create a new email_template with unique key
	public static function new_with_tkey()
	{
		$tpl = new email_template();
		while (1)
		{
			$tpl->tkey = md5(mt_rand());
			if ($tpl->put())
			{
				return $tpl;
			}
		}
	}
	
	/*
	 * todo: opts currently applied here and sent to util::mail = not good
	 */
	public function send_email($account, $tpl_map, $external_data = array(), $opts = array())
	{
		$data = $this->apply($account, $tpl_map, $external_data, $opts);
		if ($data === false)
		{
			return false;
		}
		if (trim($data['html']))
		{
			$body = &$data;
		}
		else
		{
			$body = $data['plain'];
		}
		$other_header_keys = array('cc', 'bcc');
		$other_header_vals = array();
		foreach ($other_header_keys as $k)
		{
			if ($data[$k])
			{
				$other_header_vals[ucfirst($k)] = $data[$k];
			}
		}
		
		$r = util::mail($data['from'], $data['to'], $data['subject'], $body, $other_header_vals, $opts);
		$log_ac_id = $account->id;
		if (is_array($log_ac_id))
		{
			$log_ac_id = $log_ac_id[0];
		}
		$e = new email_log_entry(array(
			'department' => $tpl_map->department,
			'account_id' => $log_ac_id,
			'created' => date(util::DATE_TIME),
			'sent_success' => ($r) ? 1 : 0,
			'type' => $tpl_map->action,
			'from' => $data['from'],
			'to' => $data['to'],
			'subject' => $data['subject'],
			'headers' => str_replace('&', "\n", util::array_to_query($other_header_vals, false)),
			'body' => (is_array($body)) ? $body['plain'] : $body
		));
		$e->insert();
		return $r;
	}
	
	// apply an email template to an account with map
	public function apply($account, $tpl_map, $external_data = array(), $opts = array())
	{
		// set opt defaults, then set member variables
		util::set_opt_defaults($opts, array(
			'read_only' => false,
			'to_override' => false
		));
		foreach ($opts as $k => $v)
		{
			$this->$k = $v;
		}
		
		// set this before we check the account type
		// we need it for some combo stuff
		$this->tpl_map = $tpl_map;
		if (rs::is_rs_array($account))
		{
			if ($account->count() == 0)
			{
				return false;
			}
			$this->is_combined = true;
			
			// loop over accounts, check for combined fields, apply tpl map
			$this->combined_apply($account, $external_data, $opts);
			
			// just use the first account for setting everything.
			// once that is done, we will check for combined placeholders
			// and deal with them accordingly
			$this->account = $account->reset();
		}
		else
		{
			$this->is_combined = false;
			$this->account = $account;
			
			util::load_lib($tpl_map->department);
		}
		$this->contact = new contacts(array('client_id' => $this->account->client_id));
		$this->contact->get();
		
		$tpl_map_data = $this->get_tpl_map_data();
		$rep_data = $this->get_rep_data() or array();
		$charge_data = $this->get_charge_data();
		$this->misc_data = array_merge($tpl_map_data, $rep_data, $charge_data, $external_data);
		
		$email_data = array();
		foreach (email_template::$template_vars as $var_type => &$var_info)
		{
			$search_replace = $this->get_var_vals($var_info['vars']);
			foreach ($var_info['fields'] as $template_field)
			{
				if ($template_field == 'to' && $this->to_override)
				{
					$email_data['to'] = $this->to_override;
				}
				else
				{
					$email_data[$template_field] = str_replace(array_keys($search_replace), array_values($search_replace), $this->$template_field);
				}
			}
		}
		
		// if it is a QL Report but account does not have reporting turned on,
		// clear html so it is not sent
		// do this here (rather than in send_email()) so email samples reflect html will not be sent
		if ($tpl_map->action == 'Report' && $tpl_map->department == 'ql' && !$account->do_report)
		{
			$email_data['html'] = false;
		}
		
		return $email_data;
	}
	
	private function combined_apply($accounts, $external_data, $opts)
	{
		$combined_keys = array('loop', 'one-time');
		$body_fields = array('plain', 'html');
		foreach ($body_fields as $field)
		{
			$field_val_new = $this->$field;
			foreach ($combined_keys as $combined_key)
			{
				// loop over $field_val_new, look for $combined_key
				// until we reach the end of the string
				$combined_key_start = '{'.$combined_key;
				for ($key_start_pos = strpos($field_val_new, $combined_key_start); $key_start_pos !== false; $key_start_pos = strpos($field_val_new, $combined_key_start, $key_start_pos + strlen($combined_key)))
				{
					// find out where this combine field ends
					$end_key = '{/'.$combined_key.'}';
					$key_end_pos = strpos($field_val_new, $end_key, $key_start_pos);
					if ($key_end_pos === false)
					{
						return $this->set_error('Could not find loop end key');
					}
					$next_newline_pos = strpos($field_val_new, "\n", $key_end_pos);
					$effective_end_pos = ($next_newline_pos) ? $next_newline_pos + 1 : $key_end_pos + strlen($end_key);
					
					// don't need all the stuff from $key_end_pos to $effective_end_pos
					$key_str = substr($field_val_new, $key_start_pos, $key_end_pos - $key_start_pos);
					
					// see what type of loop we are
					if (!preg_match("/{".$combined_key."\s+type=\"(\w+)\"/", $key_str, $matches)) {
							return $this->set_error('Could not find loop type at: '.substr($key_str, 0, 64));
					}
					
					$loop_type = $matches[1];
					switch ($loop_type) {
						case ('account'):
							$replace_str = $this->apply_loop_to_account($combined_key, $key_str, $external_data, $opts, $accounts, $field);
							break;
						
						default:
							return $this->set_error('Unrecognized loop: '.$loop_type);
					}
					$before = substr($field_val_new, 0, $key_start_pos);
					$after = substr($field_val_new, $effective_end_pos);
					$field_val_new = "{$before}{$replace_str}{$after}";
				}
			}
			$this->$field = $field_val_new;
		}
	}
	
	private function set_error($str)
	{
		$this->error = $str;
		return false;
	}
	
	private function apply_loop_to_account($combined_key, $key_str, $external_data, $opts, $accounts, $field)
	{
		$replace_str = '';
		$dept_replace_strs = array();
		$depts_replaced = array();
		foreach ($accounts as $account) {
			// get replacement string for this department
			$dept_replace_str = $this->combined_get_dept_replace_str($dept_replace_strs, $key_str, $account);
			if (
				($dept_replace_str !== false) &&
				// one time and already replaced this department
				(!($combined_key == 'one-time' && in_array($account->dept, $depts_replaced)))
			) {
				$this->combined_set_tpl_for_account($account, $ac_tpl, $ac_tpl_map);
				
				// override values for body fields loaded from database
				// with the substring from this body field
				$ac_tpl->$field = $dept_replace_str;
				
				// apply template
				$ac_data = $ac_tpl->apply($account, $ac_tpl_map, $external_data, $opts);
				
				// append to string
				$replace_str .= $ac_data[$field]."\n";
				
				$depts_replaced[] = $account->dept;
			}
		}
		return $replace_str;
	}
	
	private function combined_set_tpl_for_account($account, &$ac_tpl, &$ac_tpl_map)
	{
		// should we even bother trying to get a new tpl map?
		$ac_tpl_map = new email_template_mapping(array(
			'action' => $this->tpl_map->action,
			'department' => $account->dept,
			'plan' => $account->dept.'-'.$account->plan
		));
		$ac_tpl_map->get();
		if (!$ac_tpl_map->tkey) {
			// doesn't really matter, we just need an email template so we can
			// apply it to the account, just use the one we already have
			$ac_tpl_map = $this->tpl_map;
		}
		$ac_tpl = new email_template(array('tkey' => $ac_tpl_map->tkey));
	}
	
	private function combined_get_dept_replace_str(&$dept_replace_strs, &$key_str, $account)
	{
		// 2013-01-23: why is this check necessary?
		if (!array_key_exists($account->dept, $dept_replace_strs)) {
			$match_count = preg_match_all('/{'.$account->dept.'(.*?)}(.*?){\/'.$account->dept.'}/ms', $key_str, $matches);
			if ($match_count) {
				for ($i = 0; $i < $match_count; ++$i) {
					$dept_attrs = trim($matches[1][$i]);
					if ($this->combined_is_account_dept_match($account, $dept_attrs)) {
						$replace_str = ltrim($matches[2][$i]);

						// should we break here? or allow more than one match and append replace str as we go along?
						break;
					}
				}
			}
			// this department does not have definition in loop, set replace str to false
			// so caller knows to skip
			else {
				$replace_str = false;
			}
			$dept_replace_strs[$account->dept] = $replace_str;
		}
		return $dept_replace_strs[$account->dept];
	}

	private function combined_is_account_dept_match($account, $dept_attrs)
	{
		// no conditions, return true
		if (!$dept_attrs) {
			return true;
		}
		else {
			// only supporting one attribute!
			if (preg_match("/^(\w+)\s*(=|!=)\s*\"(.*?)\"$/", $dept_attrs, $matches)) {
				list($ph, $attr_key, $attr_cmp, $attr_val) = $matches;
				switch ($attr_cmp) {
					case ('='):
						return ($account->$attr_key == $attr_val);

					case ('!='):
						return ($account->$attr_key != $attr_val);

					// ?? unrecognized cmp
					default:
						return false;
				}
			}
			// ?? unrecognized attrs
			else {
				return false;
			}
		}
	}

	private function get_tpl_map_data()
	{
		$dept = $this->tpl_map->department;
		$action = strtolower($this->tpl_map->action);
		$data_funcs = array(
			'get_tpl_map_data_'.$dept,
			'get_tpl_map_data_'.$action,
			'get_tpl_map_data_'.$dept.'_'.$action
		);
		$d = array();
		foreach ($data_funcs as $func)
		{
			if (method_exists($this, $func))
			{
				$d = array_merge($d, $this->$func());
			}
		}
		return $d;
	}
	
	private function get_rep_data()
	{
		// kind of weird place to check this, but if we are sending an email to a client
		// we want to make sure we have a valid manager
		if (!$this->account->manager || !db::select_one("select count(*) from eppctwo.sbs_account_rep where users_id = '{$this->account->manager}'")) {
			$client = new client(array('id' => $this->account->client_id));
			$this->account->update_from_array(array(
				'manager' => $client->get_or_assign_product_manager(array('force_new' => true))
			));
		}
		return db::select_row("
			select name '[rep_name]', email '[rep_email]', phone '[rep_phone]'
			from eppctwo.sbs_account_rep
			where users_id = {$this->account->manager}
		", 'ASSOC');
	}
	
	private function get_charge_data()
	{
		// no charge data for combined
		if ($this->is_combined) {
			return array();
		}
		$pay_option_months = $this->account->prepay_paid_months + $this->account->prepay_free_months;
		if ($this->account->alt_recur_amount) {
			$charge_amount = $this->account->alt_recur_amount;
		}
		else {
			$charge_amount = sbs_lib::get_recurring_amount($this->account);
		}
		$monthly_amount = sbs_lib::get_monthly_amount($this->tpl_map->department, $this->account->plan);
		
		$savings_text = '';
		$savings_amount = 0;
		switch ($this->account->pay_option)
		{
			case ('3_0'):
				$savings_text = 'a 50% discount off your third month';
				$savings_amount = ceil($monthly_amount / 2);
				break;
			
			case ('6_1'):
				$savings_text = 'a free month';
				$savings_amount = $monthly_amount;
				break;
			
			case ('12_3'):
				$savings_text = '3 free months';
				$savings_amount = $monthly_amount * 3;
				break;
		};
		return array(
			'[recur_charge]' => util::format_dollars($charge_amount),
			'[months]' => $pay_option_months,
			'[months_minus_1]' => $pay_option_months - 1,
			'[savings_text]' => $savings_text,
			'[savings_amount]' => util::format_dollars($savings_amount)
		);
	}
	
	private function get_tpl_map_data_ql_report()
	{
		if (empty($this->data_sources)) {
			$this->data_sources = $this->account->get_data_sources();
		}
		$tmp_data = $this->get_tpl_map_report_data('ql', array('pos_sum', 'imps', 'clicks'));
		return array(
			'[30_days_ago]' => date('n/j/y', time() - 2592000),
			'[imps]' => util::n0($tmp_data['imps']),
			'[clicks]' => util::n0($tmp_data['clicks']),
			'[ctr]' => util::format_percent(util::safe_div($tmp_data['clicks'], $tmp_data['imps']) * 100),
			'[ave_position]' => util::n1(util::safe_div($tmp_data['pos_sum'], $tmp_data['imps']))
		);
	}
	
	// kw: 2013-10-03, doesn't look like we need to worry about this
	private function get_tpl_map_data_sb_report()
	{
		$tmp_data = $this->get_tpl_map_report_data('sb', array('imps', 'clicks'));
		return array(
			'[30_days_ago]' => date('n/j/y', time() - 2592000),
			'[imps]' => util::n0($tmp_data['imps']),
			'[clicks]' => util::n0($tmp_data['clicks'])
		);
	}
	
	private function get_tpl_map_report_data($dept, $cols)
	{
		$tmp_data = array_combine($cols, array_fill(0, count($cols), 0));
		$q_select = implode(', ', array_map(create_function('$v', 'return "sum($v) $v";'), array_keys($tmp_data)));
		$lib = $dept.'_lib';
		foreach ($lib::$markets as $market) {
			// must have data source
			if (empty($this->data_sources) || !$this->data_sources->key_exists($market)) {
				continue;
			}
			$ds = $this->data_sources->a[$market];
			$d = db::select_row("
				select {$q_select}
				from {$market}_objects.all_data_Q{$ds->account}
				where
					".$ds->get_entity_query()." &&
					data_date >= '".date(util::DATE, strtotime("$today -30 days"))."'
			", 'ASSOC');
			foreach ($tmp_data as $k => $v) {
				$tmp_data[$k] = $v + $d[$k];
			}
		}
		return $tmp_data;
	}
	
	private function get_tpl_map_data_ql()
	{
		$market_info = ql_lib::get_market_info($this->account);
		$ads = $market_info['ads'];
		$kws = $market_info['keywords'];
		$num_ads = count($ads);
		$ml_ads = $txt_ads = '';
		for ($i = 0; $i < $num_ads; ++$i)
		{
			$ad = $ads[$i];
			
			if ($ad['desc_1'] && $ad['desc_2'])
			{
				$ml_desc = '<p style="padding:0;margin:0;">'.$ad['desc_1'].'</p><p style="padding:0;margin:0;">'.$ad['desc_2'].'</p>';
				$txt_desc = $ad['desc_1']."\n".$ad['desc_2'];
				$desc = $ad['desc_1'].' '.$ad['desc_2'];
			}
			else
			{
				$ml_desc = '<p style="padding:0;margin:0;">'.$ad['one_line'].'</p>';
				$txt_desc = $ad['one_line'];
				$desc = $ad['one_line'];
			}
			
			$txt_ads .= $ad['text']."\n".$txt_desc."\n".$ad['dest_url']."\n\n";
			
			$ml_ads .= '
				<table width="314" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td class="img" style="font-size:0pt; line-height:0pt; text-align:left"><img src="http://www.wpromote.com/media0/quicklist_report/box_top.jpg" width="314" height="6" alt="" border="0" /></td>
					</tr>
					<tr>
						<td>

							<table width="100%" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td class="img" style="font-size:0pt; line-height:0pt; text-align:left" width="1" bgcolor="#f6f6f6"></td>
									<td class="img" style="font-size:0pt; line-height:0pt; text-align:left" width="1" bgcolor="#e1e1e1"></td>
									<td class="img" style="font-size:0pt; line-height:0pt; text-align:left" width="1" bgcolor="#c0c0c0"></td>
									<td class="img" style="font-size:0pt; line-height:0pt; text-align:left" width="1" bgcolor="#e6e6e6"></td>
									<td bgcolor="#ffffff">
										<table width="100%" border="0" cellspacing="0" cellpadding="0">
											<tr>

												<td class="img" style="font-size:0pt; line-height:0pt; text-align:left" width="10"></td>
												<td class="ad-text" style="color:#474747; font-family:Arial; font-size:14px; line-height:18px; text-align:left">

												<div style="font-size:0pt; line-height:0pt; height:5px"><img src="http://www.wpromote.com/media0/quicklist_report/empty.gif" width="1" height="5" style="height:5px" alt="" /></div>
												<div class="ad-title" style="color:#0158b0; font-family:Arial; font-size:16px; line-height:20px; text-align:left; font-weight:bold">
													<span class="ad-title-link" style="color:#0158b0; text-decoration:none">'.$ad['text'].'</span>
												</div>
												<div style="font-size:0pt; line-height:0pt; height:5px"><img src="http://www.wpromote.com/media0/quicklist_report/empty.gif" width="1" height="5" style="height:5px" alt="" /></div>

												<div style="font-size:0pt; line-height:0pt; height:5px"><img src="http://www.wpromote.com/media0/quicklist_report/empty.gif" width="1" height="5" style="height:5px" alt="" /></div>
												'.$ml_desc.'
												<div style="font-size:0pt; line-height:0pt; height:5px"><img src="http://www.wpromote.com/media0/quicklist_report/empty.gif" width="1" height="5" style="height:5px" alt="" /></div>

												<div class="ad-url" style="color:#00af4c; font-family:Arial; font-size:14px; line-height:18px; text-align:left; font-weight:bold">
												<span class="ad-url-link" style="color:#00af4c; text-decoration:none">'.$ad['disp_url'].'</span>
												</div>

												</td>
												<td class="img" style="font-size:0pt; line-height:0pt; text-align:left" width="10"></td>

											</tr>
										</table>
									</td>
									<td class="img" style="font-size:0pt; line-height:0pt; text-align:left" width="1" bgcolor="#e6e6e6"></td>
									<td class="img" style="font-size:0pt; line-height:0pt; text-align:left" width="1" bgcolor="#c0c0c0"></td>
									<td class="img" style="font-size:0pt; line-height:0pt; text-align:left" width="1" bgcolor="#e1e1e1"></td>
									<td class="img" style="font-size:0pt; line-height:0pt; text-align:left" width="1" bgcolor="#f6f6f6"></td>
								</tr>
							</table>

						</td>
					</tr>	
					<tr>
					<td class="img" style="font-size:0pt; line-height:0pt; text-align:left"><img src="http://www.wpromote.com/media0/quicklist_report/box_bottom.jpg" width="314" height="6" alt="" border="0" /></td>
					</tr>
				</table>
			';
		}
		
		// don't show modified broad
		$kws = array_unique(array_map(create_function('$a', 'return str_replace("+", "", $a);'), $kws));
		sort($kws);
		$kw_str = implode("\n", $kws);
		
		return array(
			'[ql_ads_text]' => $txt_ads,
			'[ql_ads_html]' => '
				<div class="ad-section-title" style="color:#89002f; font-family:Arial; font-size:15px; line-height:19px; text-align:center; font-weight:bold">Your Sponsored Ad'.(($num_ads > 1) ? 's' : '').'</div>
				<div style="font-size:0pt; line-height:0pt; height:10px"><img src="http://www.wpromote.com/media0/quicklist_report/empty.gif" width="1" height="10" style="height:10px" alt="" /></div>
				'.$ml_ads,
			'[title]' => $ad['text'],
			'[disp_url]' => $ad['disp_url'],
			'[dest_url]' => $ad['dest_url'],
			'[description]' => $desc,
			'[keywords]' => $kw_str
		);
	}
	
	private function get_tpl_map_data_sb()
	{
		$ads = db::select("
			select * from eppctwo.sb_ads
			where group_id = '{$this->account->id}'
			order by id asc
		", 'ASSOC');
		
		$ml_ads = $plain = '';
		foreach ($ads as $i => $ad)
		{
			/*
			 * plain
			 */
			// set geo targeting, default to US if empty
			$geo = sb_lib::get_ad_geotargeting($ad);
			$geo = array_values(array_filter(array_values($geo)));
			$geo_str = ($geo) ? $geo[0] : (($ad['country']) ? $ad['country'] : 'US');
			
			$relationships = sb_lib::get_ad_relationships($ad['id']);
			
			list($min, $max) = util::list_assoc($ad, 'min_age', 'max_age');
			$is_min = ($min || $min != '0');
			$is_max = ($max || $max != '0');
			if (!$is_min && !$is_max)
			{
				$age_str = 'Any';
			}
			else
			{
				$age_str = (($is_min) ? $min : 'Any').' - '.(($is_max) ? $max : 'Any');
			}
			
			$plain .= 'Ad #'.($i + 1)."\n";
			$plain .= 'Title: '.$ad['title']."\n";
			$plain .= 'Description: '.$ad['body_text']."\n";
			$plain .= 'URL: '.$ad['link']."\n";
			$plain .= "\n";
			$plain .= 'Interests: '.str_replace('#', '', sb_lib::ad_get_many($ad['id'], 'sb_keywords', 'text'))."\n";
			$plain .= 'Age Range: '.$age_str."\n";
			$plain .= 'Sex: '.$ad['sex']."\n";
			$plain .= 'Relationships: '.(($relationships) ? implode(', ', $relationships) : 'Any')."\n";
			$plain .= 'Education: '.$ad['education_status']."\n";
			$plain .= 'GeoGraphic Targeting: '.$geo_str."\n";
			if ($i != count($ads) - 1)
			{
				$plain .= "\n";
			}
			
			/*
			 * html
			 */
			$ml_ads .= '
															
				<!-- ad -->
				<table width="270" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td>
						
							<div style="font-size:0pt; line-height:0pt; height:1px; background:#d1d1d1; "><img src="http://www.wpromote.com/media0/socialboost_report/empty.gif" width="1" height="1" style="height:1px" alt="" /></div>
							<table width="100%" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td class="img" style="font-size:0pt; line-height:0pt; text-align:left" width="1" bgcolor="#d1d1d1"><div style="font-size:0pt; line-height:0pt; height:1px"><img src="http://www.wpromote.com/media0/socialboost_report/empty.gif" width="1" height="31" style="height:31px" alt="" /></div></td>
									<td class="img" style="font-size:0pt; line-height:0pt; text-align:left" width="12"></td>
									
									<td>
									
										<div style="font-size:0pt; line-height:0pt; height:12px"><img src="http://www.wpromote.com/media0/socialboost_report/empty.gif" width="1" height="12" style="height:12px" alt="" /></div>
										<table width="100%" border="0" cellspacing="0" cellpadding="0">
											<tr>
												<td>
													<table>
														<tr>
															<td colspan="2" style="font-size:12px;font-weight:bold;overflow:hidden;padding-bottom:7px;color:#3B5998;">'.$ad['title'].'</td>
														</tr>
														<tr valign="top" style="vertical-align:top">
															<td style="padding-right:8px;">'.(($ad['image']) ? '<img src="http://'.\epro\WPRO_DOMAIN.'/uploads/sb/'.$ad['group_id'].'/'.$ad['image'].'" />' : '').'</td>
															<td style=\'font-size:11px;color:#333333;font-family:"lucida grande",tahoma,verdana,arial,sans-serif;\'>'.$ad['body_text'].'</td>
														</tr>
														<tr>
															<td colspan="2" style=\'font-family:"lucida grande",tahoma,verdana,arial,sans-serif;font-size:11px\'>
																<span style="color:#3B5998;">Wpromote</span> likes this.
															</td>
														</tr>
													</table>
												</td>
											</tr>
										</table>
										<div style="font-size:0pt; line-height:0pt; height:12px"><img src="http://www.wpromote.com/media0/socialboost_report/empty.gif" width="1" height="12" style="height:12px" alt="" /></div>
										
									</td>
									<td class="img" style="font-size:0pt; line-height:0pt; text-align:left" width="12"></td>
									<td class="img" style="font-size:0pt; line-height:0pt; text-align:left" width="1" bgcolor="#d1d1d1"></td>
								</tr>
							</table>
							<div style="font-size:0pt; line-height:0pt; height:1px; background:#d1d1d1; "><img src="http://www.wpromote.com/media0/socialboost_report/empty.gif" width="1" height="1" style="height:1px" alt="" /></div>
							
						</td>
					</tr>
				</table>
				<div style="font-size:0pt; line-height:0pt; height:20px"><img src="http://www.wpromote.com/media0/socialboost_report/empty.gif" width="1" height="20" style="height:20px" alt="" /></div>
			';
		}
		
		$ml = '
			<tr>
				<td align="center">

					<div class="ad-section-title" style="color:#89002f; font-family:Arial; font-size:15px; line-height:19px; text-align:center; font-weight:bold">Your Sponsored Ad'.((count($ads) > 1) ? 's' : '').'</div>
					<div style="font-size:0pt; line-height:0pt; height:10px"><img src="http://www.wpromote.com/media0/socialboost_report/empty.gif" width="1" height="10" style="height:10px" alt="" /></div>
					
					'.$ml_ads.'
					
				</td>
			</tr>
		';
		
		return array(
			'[all_ads_html]' => $ml,
			'[all_ads_plain]' => $plain
		);
	}
	
	private function get_var_vals($var_keys)
	{
		$d = array();
		foreach ($var_keys as $key_group => $keys)
		{
			foreach ($keys as $key)
			{
				switch ($key)
				{
					case ('[client_email]'):
					case ('[client_name]'):
						$contact_key = str_replace(array('[client_', ']'), '', $key);
						$val = $this->contact->$contact_key;
						break;
					
					case ('[url]'):
					case ('[plan]'):
					case ('[oid]'):
					case ('[contract_length]'):
						$account_key = substr($key, 1, strlen($key) - 2);
						$val = $this->account->$account_key;
						if ($account_key == 'url') $val = preg_replace("/^http:\/\//", '', $val);
						break;
					
					case ('[next_bill_date]'):
						$val = date('n/j/y', strtotime($this->account->next_bill_date));
						break;
						
					case ('[prev_bill_date]'):
						$val = date('n/j/y', strtotime($this->account->last_bill_date));
						break;
					
					case ('[today]'):
						$val = date('n/j/y');
						break;
					
					case ('[login_link]'):
						$val = sbs_lib::client_account_link($this->contact);
						break;
					
					case ('[sb_exp_link]'):
						util::load_lib('sb');
						$val = sb_exp_contacts::get_express_link($this->account);
						break;
					
					// following all pull from misc data
					
					// rep
					case ('[rep_name]'):
					case ('[rep_email]'):
					case ('[rep_phone]'):
					
					// charge
					case ('[recur_charge]'):
					case ('[months]'):
					case ('[months_minus_1]'):
					case ('[savings_text]'):
					case ('[savings_amount]'):
					case ('[first_month_charge]'):
					
					// sb, ql reports
					case ('[30_days_ago]'):
					case ('[imps]'):
					case ('[clicks]'):
					
					// ql
					case ('[ql_ads_html]'):
					case ('[ql_ads_text]'):
					case ('[title]'):
					case ('[description]'):
					case ('[disp_url]'):
					case ('[dest_url]'):
					case ('[keywords]'):
					case ('[ctr]'):
					case ('[ave_position]'):
					
					// sb
					case ('[all_ads_html]'):
					case ('[all_ads_plain]'):
					
					
					default:
						$val = array_key_exists($key, $this->misc_data) ? $this->misc_data[$key] : '';
						break;
				}
				$d[$key] = $val;
			}
		}
		return $d;
	}
}

class email_template_mapping extends rs_object
{
	public static $db, $cols, $primary_key, $uniques, $indexes;
	
	public static $actions = array(
		'Signup',
		'Activation',
		'Non-Renewing',
		'Report',
		'Cancel',
		'Multi-Reminder'
	);
	
	public static $department_options = array('ql','sb','gs','ww', 'combined');
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('id');
		self::$uniques = array(
			array('department', 'action', 'plan')
		);
		self::$indexes = array(
			array('tkey')
		);
		self::$cols = self::init_cols(
			new rs_col('id'        ,'bigint',null,null,rs::UNSIGNED | rs::NOT_NULL | rs::AUTO_INCREMENT),
			new rs_col('tkey'      ,'char'  ,32  ,''  ,rs::NOT_NULL),
			new rs_col('department','enum'  ,16  ,''  ,0),
			new rs_col('action'    ,'char'  ,16  ,''  ,rs::NOT_NULL),
			new rs_col('plan'      ,'char'  ,16  ,''  ,rs::NOT_NULL)
		);
	}
}

?>