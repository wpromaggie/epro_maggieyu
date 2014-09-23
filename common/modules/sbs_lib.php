<?php

class sbs_lib
{
	const TRIAL_AUTH_AMOUNT = 5;
	const TRIAL_AUTH_LIKE_AMOUNT = 4;
	
	public static $departments = array('ql', 'sb', 'gs', 'ww');
	
	// returns array with number of months client has to pay for and the number they get free based on the pay option
	// current pay options: standard, 3_0, 6_1, 12_3
	public static function get_pay_option_months($pay_option)
	{
		if (preg_match("/^(\d+)_(\d+)$/", $pay_option, $matches))
		{
			return array_slice($matches, 1);
		}
		else
		{
			return array(1, 0);
		}
	}
	
	public static function get_full_department($dept)
	{
		switch ($dept) {
			case ('ql'): return 'QuickList';
			case ('sb'): return 'SocialBoost';
			case ('gs'): return 'GoSEO';
		}
	}

	public static function get_months_paid($pay_option)
	{
		$tmp = self::get_pay_option_months($pay_option);
		return $tmp[0];
	}
	
	public static function get_months_free($pay_option)
	{
		$tmp = self::get_pay_option_months($pay_option);
		return $tmp[1];
	}
	
	public static function get_months_total($pay_option)
	{
		list($months_paid, $months_free) = self::get_pay_option_months($pay_option);
		return ($months_paid + $months_free);
	}
	
	public static function get_monthly_amount($department, $plan)
	{
		// no such thing as a monthly amount without also knowing contract length/pay option
		// do our best
		if ($department == 'ww') {
			if ($plan == 'None') {
				return 0;
			}
			else {
				return ww_account::$pricing[$plan][0];
			}
		}
		else {
			$table = "{$department}_plans";
			if (db::table_exists($table)) {
				return db::select_one("
					select budget
					from eppctwo.{$table}
					where name = '".db::escape($plan)."'
				");
			}
			else {
				return 0;
			}
		}
	}
	
	public static function get_recurring_amount($account)
	{
		if ($account->dept == 'ww') {
			if ($plan == 'None') {
				$recurring_amount = 0;
			}
			else {
				$recurring_amount = ww_account::$pricing[$plan][$account->prepay_paid_months];
			}
		}
		else {
			if ($account->alt_recur_amount) {
				$recurring_amount = $account->alt_recur_amount;
			}
			else {
				$monthly_amount = sbs_lib::get_monthly_amount($account->dept, $account->plan);
				$recurring_amount = ($account->prepay_paid_months > 0) ? $monthly_amount * $account->prepay_paid_months : $monthly_amount;
				// built in 50% discount on 3_0 plan
				if ($account->prepay_paid_months == 3 && $account->prepay_free_months == 0) {
					$recurring_amount -= ceil($monthly_amount * .5);
				}
			}
		}
		return $recurring_amount;
	}
	
	public static function get_discount($discount)
	{
                //remove discount validation for now
		$valid_discounts = array(30, 50);
		return ((in_array($discount, $valid_discounts)) ? $discount : 0);
	}
        
        public static function insert_sbs_coupons($url_id=0, $department="", $coupon_ids=""){
                $coupon_ids = explode(':', $coupon_ids);
                foreach($coupon_ids as $id){
                        db::insert("eppctwo.sbs_coupons", array(
                            'account_id' => $url_id,
                            'department' => $department,
                            'coupon_id' => $id
                        ));
                }
        }
	
	public static function calc_first_month_payment($department, $plan, $pay_option, $setup_fee, $coupon_ids="")
	{
		
		$monthly_payment = sbs_lib::get_monthly_amount($department, $plan);
		
		$payment = $monthly_payment;
		
		//Check for any payment coupons dealing with the first months payment...
		if(!empty($coupon_ids)){
			
			$coupon_ids = str_replace(":", ",", $coupon_ids);
			
			$first_month_coupon = db::select_row("
				SELECT value, value_type
				FROM coupons 
				WHERE id IN ($coupon_ids) AND type='first month'
				LIMIT 1
			", 'ASSOC');
			
			if($first_month_coupon){
				
				if($first_month_coupon['value_type']=='percent'){
					
					$payment = floor($first_month_coupon['value'] / 100 * $payment);
					
				} else if($first_month_coupon['value_type']=='dollars'){
					
					$payment = $payment - $first_month_coupon['value'];
					
				}
				
			}
				
		}
		
		list($months_paid, $months_free) = sbs_lib::get_pay_option_months($pay_option);
		
		if ($months_paid > 1)
		{
			// minus 1 because we have already accounted for the first month
			$payment += floor($monthly_payment * ($months_paid - 1));
		}
		
		if($setup_fee){
			$payment += $setup_fee;
		}
                
		return $payment;
	}
	
	public static function e2_plan_to_wpro_plan($plan)
	{
		switch ($plan)
		{
			case ('Starter_149'): return 'Starter';
			case ('Core_297'): return 'Core';
		}
		return $plan;
	}
	
	public static function client_update($department, $ac_id, $type, $data)
	{
		sbs_client_update::create(array(
			'department' => $department,
			'account_id' => $ac_id,
			'dt' => date(util::DATE_TIME),
			'type' => $type,
			'data' => json_encode($data)
		));
	}
	
	// todo: cgi only, need common place for cgi only stuff
	public static function client_update_done($update_id)
	{
		// set update as procssed to remove from queue
		$update = new sbs_client_update(array('id' => $update_id));
		
		$update->update_from_array(array(
			'users_id' => user::$id,
			'processed_dt' => date(util::DATE_TIME)
		));
		
		// send to wpro to remove pending message
		$type_short = substr($update->type, strpos($update->type, '-') + 1);
		$other_data = array();
		if ($type_short == 'ad') {
			$update_data = json_decode($update->data, true);
			$other_data['ql_ad_id'] = $update_data['ql_ad_id'];
		}
		util::wpro_post('account', 'update_processed', array(
			'aid' => $update->account_id,
			'type' => $type_short,
			'other_data' => serialize($other_data)
		));
		
		if (!cgi::is_ajax()) {
			feedback::add_success_msg($type_short.' update for '.db::select_one("select url from eac.account where id = '".$update->account_id."'").' done');
		}
	}
	
	private static function accounting_dates(&$ml, &$start_date, &$end_date)
	{
		list($start_date, $end_date) = util::list_assoc($_POST, 'start_date', 'end_date');
		if (empty($start_date))
		{
			$end_date = date(util::DATE);
			$start_date = date(util::DATE, strtotime("$end_date -7 days"));
		}
		if ($end_date < $start_date)
		{
			$end_date = $start_date;
		}
		
		$ml = '
			<table>
				<tbody>
					<tr>
						<td>Start Date</td>
						<td><input type="text" class="date_input" name="start_date" value="'.$start_date.'" /></td>
					</tr>
					<tr>
						<td>End Date</td>
						<td><input type="text" class="date_input" name="end_date" value="'.$end_date.'" /></td>
					</tr>
						<td></td>
						<td><input type="submit" value="Set Dates" /></td>
					</tr>
				</tbody>
			</table>
		';
	}
	
	public static function accounting_display_by_partner($department, $department_account_table)
	{
		self::accounting_dates($ml_dates, $start_date, $end_date);
		
		$payments = db::select("
			select dat.partner partner, sum(p.amount) amount
			from eppctwo.sbs_payment p, eppctwo.{$department_account_table} dat
			where
				p.department = '{$department}' &&
				p.d >= '$start_date' &&
				p.d <= '$end_date' &&
				p.account_id = dat.id
			group by partner
		", 'ASSOC');
		
		?>
		<?php echo $ml_dates; ?>
		<div id="_partner_payments" class="payments_wrapper"></div>
		<?php
		cgi::add_js_var('payments', $payments);
	}
	
	public static function accounting_display_all($department, $department_account_table, $account_display_field)
	{
		self::accounting_dates($ml_dates, $start_date, $end_date);
		
		$payments = db::select("
			select substring(replace(dat.{$account_display_field}, 'http://', ''), 1, 64) account, dat.plan, dat.partner, concat(p.d,' ',p.t) date, p.amount
			from eppctwo.sbs_payment p, eppctwo.{$department_account_table} dat
			where
				p.department = '{$department}' &&
				p.d >= '$start_date' &&
				p.d <= '$end_date' &&
				p.account_id = dat.id
		", 'ASSOC');
		
		?>
		<?php echo $ml_dates; ?>
		<div id="_all_payments" class="payments_wrapper"></div>
		<?php
		cgi::add_js_var('payments', $payments);
	}
	
	// 10.03.2012 i don't understand what this was even trying to do
	// changed so that if last login is non-empty and they don't have
	// auth set, they get the normal account link
	// otherwise, they get the auth link
	public static function client_account_link($contact)
	{
		if (
			(is_array($contact)  && $contact['last_login'] && !$contact['authentication']) ||
			(is_object($contact) && $contact->last_login   && !$contact->authentication)
		){
			return sbs_lib::client_login_link();
		}
		else
		{
			return sbs_lib::client_pass_set_link((is_array($contact)) ? $contact['authentication'] : $contact->authentication);
		}
	}
	
	public static function client_login_link()
	{
		$protocol = (util::is_dev()) ? 'http' : 'https';
		return ($protocol.'://'.\epro\WPRO_DOMAIN.'/account/');
	}
	
	public static function client_pass_set_link($pass_set_key)
	{
		// this shouldn't happen?
		if (!$pass_set_key)
		{
			return self::client_login_link();
		}
		else
		{
			$protocol = (util::is_dev()) ? 'http' : 'https';
			return ($protocol.'://'.\epro\WPRO_DOMAIN.'/account/password-set/?k='.$pass_set_key);
		}
	}
	
	// authentication should be unique.. bleh
	public static function generate_pass_set_key()
	{
		while (1)
		{
			$key = md5(mt_rand());
			if (db::select_one("select count(*) from eppctwo.contacts where authentication = '$key'") == 0)
			{
				return $key;
			}
		}
	}
	
	/**
	 * makes the url small and pretty
	 * @param string $url a url
	 * @return string a shorter version of the url
	 */
	public static function shorten_url($url, $len = 24, $only_lal = false)
	{
		// shorten url
		if      (strpos($url, 'http://www.localadlink.com/details/') === 0) { $is_lal = true;  $url = substr(preg_replace("/\d+\//", '', substr($url, 35)), 0, $len); }
		else if (strpos($url, 'http://www.zlinked.com/details/') === 0)     { $is_lal = true;  $url = substr(preg_replace("/\d+\//", '', substr($url, 31)), 0, $len); }
		else if (!$only_lal)                                                {                  $url = substr(preg_replace("/^http(s|):\/\/(www.|)/", '', $url), 0, $len); }
		
		if (!$is_lal && $only_lal)
		{
			return $url;
		}
		
		// don't want to end on a non-alpha-numeric character
		for ($i = strlen($url) - 1; $i > 10 && !ctype_alnum($url[$i]); --$i);
		if ($i != (strlen($url) - 1))
		{
			$url = substr($url, 0, $i);
		}
		return $url;
	}
	
	/*
	 * logs emails which could not be sent
	 * returns false to signify failure
	 * 
	 * TODO: determine department. should be member of account?
	 */
	public static function email_failure($account, $email_type, $details)
	{
		$e = new email_log_entry(array(
			'department' => 'ql',
			'account_id' => $account->id,
			'created' => date(util::DATE_TIME),
			'sent_success' => 0,
			'sent_details' => $details,
			'type' => $email_type
		));
		$e->put();
		
		return false;
	}
	
	public static function sales_rep_form_input($table, $col, $val)
	{
		$options = db::select("
			select u.id u0, u.realname u1
			from users u, user_guilds ug
			where
				ug.guild_id = 'sbr' &&
				u.id = ug.user_id
			order by u1 asc
		");
		array_unshift($options, array(0, ' - None - '));
		return cgi::html_select($table.'_'.$col->name, $options, $val);
	}
	
	public static function account_rep_form_input($table, $col, $val)
	{
		$options = db::select("
			select u.id u0, u.realname u1
			from eppctwo.users u, eppctwo.sbs_account_rep ar
			where
				u.id = ar.users_id
			order by u1 asc
		");
		array_unshift($options, array('', ' - None - '));
		return cgi::html_select($table.'_'.$col->name, $options, $val);
	}
	
	public static function partner_form_input($table, $col, $val)
	{
		$options = db::select("
			select id i0, id i1
			from eppctwo.sbr_partner
			where status = 'On'
			order by i0 asc
		");
		array_unshift($options, array('', ' - None - '));
		return cgi::html_select($table.'_'.$col->name, $options, $val);
	}
	
	public static function source_form_input($table, $col, $val)
	{
		$options = db::select("
			select distinct id i0, id i1
			from eppctwo.sbr_source
			where status = 'On'
			order by i0 asc
		");
		array_unshift($options, array('', ' - None - '));
		return cgi::html_select($table.'_'.$col->name, $options, $val);
	}
	
	public static function get_client_department_links($cl_id, $opts = array())
	{
		util::set_opt_defaults($opts, array(
			'aid' => false,
			'do_include_empty' => true
		));

		$acs = product::get_all(array(
			'select' => array("account" => array("id", "dept", "url", "status")),
			'where' => "account.client_id = '{$cl_id}'",
			'order_by' => "dept asc, url asc",
			'key_col' => 'dept',
			'key_grouped' => true
		));

		$ml = '';
		foreach (sbs_lib::$departments as $dept) {
			$ml_dept = '';
			if ($acs->key_exists($dept)) {
				$dept_acs = $acs->i($dept);
				$ac_stati = array();
				foreach ($dept_acs as $account) {
					$ac_stati[$account->id] = $account->status;
					$ml_active = ($account->id == $opts['aid']) ? ' on' : '';
					$ml_dept .= '<div><a href="'.cgi::href('account/product/'.$dept.'?aid='.$account->id).'" class="'.util::simple_text($account->status).$ml_active.'">'.$account->url.'</a></div>';
				}
				// check for link to agency ppc
				if ($dept == 'ql') {
					$ppc_accounts = account::get_all(array(
						'select' => array("account" => array("id", "name", "status")),
						'where' => "dept = 'ppc' && client_id = :cid",
						'data' => array("cid" => $cl_id),
						'order_by' => "name asc"
					));
					foreach ($ppc_accounts as $acnt) {
						$ml_dept .= '<div><a href="'.cgi::href('/account/service/ppc?aid='.$acnt->id).'" class="'.util::simple_text($acnt->status).'" target="_blank">QL PRO: '.$acnt->name.'</a></div>';
					}
				}
			}
			
			if ($ml_dept || $opts['do_include_empty']) {
				$ml .= '
					<tr>
						<td>'.strtoupper($dept).' URLs:</td>
						<td>
							'.$ml_dept.'
							<div class="clear"></div>
						</tdd>
					</tr>
				';
			}
		}
		return ($ml || $opts['do_include_empty']) ? '
			<table id="head_urls">
				<tbody>
					'.$ml.'
				</tbody>
			</table>
		' : '';
	}
	
	public static function get_department_info($dept)
	{
		switch ($dept)
		{
			case ('ql'): return array('account_table' => 'ql_url',    'account_url' => 'sbs/ql/url',    'wpro_update_func' => 'ql_update_url');
			case ('sb'): return array('account_table' => 'sb_groups', 'account_url' => 'sbs/sb/account','wpro_update_func' => 'sb_update_group');
			case ('gs'): return array('account_table' => 'gs_urls',   'account_url' => 'sbs/gs/url',    'wpro_update_func' => 'gs_update_url');
			case ('ww'): return array('account_table' => 'ww_account','account_url' => 'sbs/ww/account','wpro_update_func' => 'ww_jk');
		}
		return false;
	}
	
	public static function get_dept_from_account($account)
	{
		$class = get_class($account);
		return substr($class, 0, strpos($class, '_'));
	}
	
	// itf: move to account_sbs
	public static function send_email($account, $type, $data = array(), $opts = array())
	{
		$map_info = array('action' => $type);
		if (rs::is_rs_array($account))
		{
			$dept = 'combined';
		}
		else
		{
			$dept = $account->dept;
			$map_info['plan'] = $account->dept.'-'.$account->plan;
		}
		$map_info['department'] = $dept;
			
		$map = new email_template_mapping($map_info);
		$map->get();
		if (!$map->tkey)
		{
			if (class_exists('feedback'))
			{
				feedback::add_error_msg("Could not find email template for: action={$map->action}, department={$map->department}, plan={$map->plan}");
			}
			return false;
		}
		$tpl = new email_template(array('tkey' => $map->tkey));
		return $tpl->send_email($account, $map, $data, $opts);
	}
	
	public static function update_all_active_urls_data($dept, $market, $date, $opts = array())
	{
		util::set_opt_defaults($opts, array(
			'verbose' => false,
			'do_update_status' => false
		));
		$lib = $dept.'_lib';
		
		if (!in_array($market, $lib::$markets)) {
			return;
		}

		if (!method_exists($lib, 'update_url_data')) {
			return;
		}
		
		$table = 'ap_'.$dept;
		$urls = $table::get_all(array('where' => "account.status in ('Active', 'NonRenewing')"));
		for ($i = 0, $ci = $urls->count(); $i < $ci; ++$i) {
			if ($i % 10 == 0) {
				if ($opts['verbose']) {
					echo "$market, $date: $i / $ci\n";
				}
				if ($opts['do_update_status'])
				{
					db::exec("
						update eppctwo.market_data_status
						set status = 'Updating $dept Client ".($i + 1)." of {$ci}', t = '".date('H:i:s')."'
						where market = '$market' && d = '$date'
					");
				}
			}
			$url = $urls->i($i);
			$lib::update_url_data($url, $market, $date, $opts);
		}
	}
	
	/*
	 * can either be array representing an account or account object
	 */
	public static function get_contract_end_date($account)
	{
		if (is_array($account)) {
			list($first_bill_date, $contract_length) = util::list_assoc($account, 'signup_date', 'contract_length');
		}
		else {
			$first_bill_date = $account->signup_dt;
			$contract_length = $account->contract_length;
		}
		$first_bill_date = date(util::DATE, strtotime($first_bill_date));
		if (util::empty_date($first_bill_date) || !is_numeric($contract_length) || $contract_length == 0) {
			return false;
		}
		return util::delta_month($first_bill_date, $contract_length);
	}
	
	public static function amortize_multi_month_payment($p, $months)
	{
		// this payment has already been amortized
		if (preg_match("/^ai\d+/i", $p->notes)) {
			if (class_exists('feedback')) {
				feedback::add_error_msg('Payment already amortized?');
			}
			return false;
		}
		
		// set amounts
		$pp_amounts = array();
		$first_month_total = $ai_total = 0;
		foreach ($p->payment_part as $i => $pp) {
			$ai_amount = round($pp->amount / $months, 2);
			$leftovers = round($pp->amount - ($ai_amount * $months), 2);
			
			// update src part amounts as we go along
			$pp->update_from_array(array(
				'amount' => round($ai_amount + $leftovers, 2)
			));

			$pp_amounts[] = $ai_amount;
			$ai_total += $ai_amount;
			$first_month_total += $pp->amount;
		}
		$p->update_from_array(array(
			'notes' => 'AI1'.(($p->notes) ? ' '.$p->notes : '').' ('.$p->amount.')',
			'amount' => $first_month_total
		));

		$ts = date(util::DATE_TIME);
		for ($i = 1; $i < $months; ++$i) {
			// clone payment, unset id, set ts, update date attributed, insert
			$pclone = clone($p);
			unset($pclone->id);
			$pclone->date_attributed = util::delta_month($p->date_attributed, $i);
			$pclone->ts = $ts;
			$pclone->amount = $ai_total;
			$pclone->notes = 'AI'.($i + 1);
			$pclone->insert();

			// loop over payment parts, create clones
			foreach ($p->payment_part as $j => $pp) {
				$ppclone = clone($pp);
				unset($ppclone->id);
				$ppclone->payment_id = $pclone->id;
				$ppclone->amount = $pp_amounts[$j];
				$ppclone->insert();
			}

		}
		return true;
	}

	// can either pass in
	// 1. an array reprenting a payment
	// 2. sbs_payment object
	// 3. a payment id
	// todo: obsolete, get rid of
	public static function zamortize_multi_month_payment($mixed)
	{
		if (is_array($mixed))
		{
			$d = $mixed;
		}
		else if (is_object($mixed) && get_class($mixed) == 'sbs_payment')
		{
			$is_payment_obj = true;
			$d = array();
			$cols = sbs_payment::get_cols();
			foreach ($cols as $k)
			{
				$d[$k] = $mixed->$k;
			}
		}
		else if (is_scalar($mixed))
		{
			$d = db::select_row("
				select *
				from eppctwo.sbs_payment
				where id = '".db::escape($mixed)."'
			", 'ASSOC');
			if (!$d)
			{
				return false;
			}
		}
		else
		{
			return false;
		}
		list($id, $pay_option, $amount, $notes) = util::list_assoc($d, 'id', 'pay_option', 'amount', 'notes');
		$months = sbs_lib::get_months_total($pay_option);
		if ($months < 2)
		{
			return false;
		}
		
		// this payment has already been amortized
		if (preg_match("/^ai\d+/i", $notes))
		{
			return false;
		}
		
		// amortized payment notes:
		// AIX = Amortized Installed X
		// if payment had notes, append them to first AIX note
		$notes = 'AI1'.(($notes) ? ' '.$notes : '').' ('.$amount.')';
		
		$ai_amount = round($amount / $months, 2);
		$leftovers = round($amount - ($ai_amount * $months), 2);
		
		$ai_first_month_amount = round($ai_amount + $leftovers, 2);
		
		db::update("eppctwo.sbs_payment", array(
			'amount' => $ai_first_month_amount,
			'pay_option' => $pay_option,
			'notes' => $notes
		), "id = {$d['id']}");
		// if an object was passed in, update it
		if ($is_payment_obj)
		{
			$mixed->amount = $ai_first_month_amount;
			$mixed->notes = $notes;
		}
		// don't want id for new AIs
		unset($d['id']);
		
		// make sure sb_payment_id is non-null
		if (!$d['sb_payment_id'])
		{
			$d['sb_payment_id'] = 0;
		}
		// insert rest of ais
		for ($j = 2; $j <= $months; ++$j)
		{
			$d['d'] = util::delta_month($d['d'], 1);
			$d['amount'] = $ai_amount;
			$d['notes'] = 'AI'.$j;
			db::insert("eppctwo.sbs_payment", $d);
		}
		return true;
	}
}

?>