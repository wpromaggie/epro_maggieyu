<?php

class mod_sbr_accounting extends mod_sbr
{
	protected static $depts = array('ql', 'sb', 'gs');
	
	protected static $bucket_options = array('dept', 'partner', 'source', 'plan', 'sales_rep');
	
	protected static $grouped_types = array(
		'refund new'   => 'refund',
		'refund old'   => 'refund',
		'optimization' => 'misc',
		'upgrade'      => 'misc',
		'reseller'     => 'misc',
		'other'        => 'misc',
		''             => 'misc'
	);
	
	public function pre_output()
	{
		parent::pre_output();
		$this->display_default = 'index';
	}
	
	public function get_page_menu()
	{
		return (g::$p3 == 'account_list') ? array() : array(
			array('', 'Breakdown'),
			array('report', 'Download Report'),
			array('avv_upload', 'AVV Upload')
		);
	}
	
	public function output()
	{
		if (!$this->is_user_leader())
		{
			$this->display_index();
			return;
		}
		parent::output();
	}
	
	public function head()
	{
		$pages = array_slice(g::$pages, 1);
		if (empty(g::$p3))
		{
			$pages[] = $this->display_default;
		}
		echo '
			<h1>
				'.implode(' :: ', array_map(array('util', 'display_text'), $pages)).'
			</h1>
			'.$this->page_menu($this->get_page_menu(), 'sbr/accounting/').'
		';
	}
	
	// example: you want checkboxes for
	// var string key
	// var string default tab separated string of default values
	// var array options possible values
	protected function init_cboxes($key, $default, $options)
	{
		$options_key = "{$key}_options";
		$selected_key = "{$key}_selected";
		$ml_key = "ml_{$key}";
		
		$this->$selected_key = $_REQUEST[$key];
		if (!$this->$selected_key) {
			$this->$selected_key = $default;
		}
		$this->$key = ($this->$selected_key == '*') ? $options : explode("\t", $this->$selected_key);
		$this->$options_key = $options;
		$this->$ml_key = '
			<tr>
				<td>'.ucwords($key).'</td>
				<td>'.cgi::html_checkboxes($key, $options, $this->$selected_key, array('separator' => ' &nbsp; ')).'</td>
			</tr>
		';
	}
	
	public function display_index()
	{
		list($start_date, $end_date) = util::list_assoc($_REQUEST, 'start_date', 'end_date');

		// todo: time period? show payment types?

		if (!$end_date)
		{
			$end_date = date(util::DATE);
			$start_date = substr($end_date, 0, 7).'-01';
		}
		
		$this->init_cboxes('buckets', "dept", self::$bucket_options);
		
		if (in_array('sales_rep', $this->buckets)) {
			$reps = db::select("
				select distinct u.id, u.realname
				from eppctwo.users u, eppctwo.user_guilds g
				where
					g.guild_id = 'sbr' &&
					g.user_id = u.id
			", 'NUM', 0);
			cgi::add_js_var('reps', $reps);
		}

		$data = payment::get_all(array(
			"select" => array(
				"payment" => array("id as pid", "date_attributed as date", "event", "notes"),
				"payment_part" => array("id as ppid", "account_id", "type", "amount"),
				"account" => array_merge($this->buckets, array("contract_length as contract", "substring(signup_dt, 1, 10) as signup"))
			),
			"join_many" => array(
				"payment_part" => "payment.id = payment_part.payment_id",
				"account" => "payment_part.account_id = account.id"
			),
			"where" => "
				account.division = 'product' &&
				payment.date_attributed between '$start_date' and '$end_date'
			",
			"flatten" => array(
				"account" => "payment_part"
			)
		));
		
		$de_activated = account::get_all(array(
			"select" => array("account" => array_merge($this->buckets, array("id as account_id", "(1) as de_activated"))),
			"where" => "
				account.division = 'product' &&
				account.de_activation_date between '$start_date' and '$end_date'
			"
		));
		
		cgi::add_js_var('buckets', $this->buckets);
		cgi::add_js_var('data', $data);
		cgi::add_js_var('de_activated', $de_activated);
		?>
		<table id="form_table">
			<tbody>
				<?php echo cgi::date_range_picker($start_date, $end_date, array('table' => false)); ?>
				<!--
				<tr>
					<td>Time Period</td>
					<td><?php echo cgi::html_radio('time_period', array('Off', 'Yearly', 'Quarterly', 'Monthly', 'Weekly'), util::unempty($time_period, 'Off'), array('separator' => ' &nbsp;')); ?></td>
				</tr>
				-->
				<?= $this->ml_buckets ?>
				<tr>
					<td></td>
					<td><input id="form_submit" type="submit" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		<div id="atable" ejo></div>
		<?php
	}
	
	public function display_avv_upload()
	{
		echo 'coming soon';
		return;
		?>
		<div>
			<label for="avv_file">AVV CSV:</label>
			<input type="file" name="avv_file" id="avv_file" />
		</div>
		
		<div>
			<input type="submit" a0="action_avv_upload" value="Upload" />
		</div>
		<?php
	}
	
	public function display_account_list()
	{
		list($col, $start_date, $end_date) = util::list_assoc($_GET, 'col', 'start_date', 'end_date');
		
		$account_select = array("id", "dept", "url", "plan", "signup_dt", "source", "de_activation_date");
		$contact_select = array("name", "email", "phone");
		
		$ml_buckets = '';
		$account_where = array();
		foreach (self::$bucket_options as $bucket) {
			if (array_key_exists($bucket, $_GET)) {
				$account_where[] = "account.$bucket = '".db::escape($_GET[$bucket])."'";
				$ml_buckets .= '
					<div>
						<b>'.util::display_text($bucket).':</b>
						<span>'.$_GET[$bucket].'</span>
					</div>
				';
			}
		}
		
		// special case, based on de activation, not payment info
		if ($col == 'Lost') {
			$data = account::get_all(array(
				"select" => array(
					"account" => $account_select,
					"contacts" => $contact_select
				),
				"join" => array(
					"contacts" => "account.client_id = contacts.client_id"
				),
				"where" => "
					".implode(" && ", $account_where)." &&
					account.division = 'product' &&
					account.de_activation_date between '$start_date' and '$end_date'
				",
				"flatten" => true
			));
		}
		else {
			$col = substr($col, 0, strpos($col, ' #'));
			$tmpdata = payment::get_all(array(
				"select" => array(
					"account" => $account_select,
					"contacts" => $contact_select
				),
				"join_many" => array(
					"payment_part" => "payment.id = payment_part.payment_id",
					"account" => "payment_part.account_id = account.id",
					"contacts" => "account.client_id = contacts.client_id"
				),
				"where" => "
					".implode(" && ", $account_where)." &&
					account.division = 'product' &&
					payment.event = '".db::escape($col)."' &&
					payment.date_attributed between '$start_date' and '$end_date'
				",
				"flatten" => array(
					"account" => "payment_part",
					"contacts" => "payment_part"
				)
			));
			$data = payment_part::new_array();
			foreach ($tmpdata as $payment) {
				$data->push($payment->payment_part->current());
			}
		}
		
		$seconds_per_month = 2628000;
		$ml = '';
		$now = time();
		for ($i = 0, $ci = $data->count(); $i < $ci; ++$i)
		{
			$account = $data->i($i);
			if (util::empty_date($account->cancel_date))
			{
				$ml_cancel = '';
				$cancel_time = $now;
			}
			else
			{
				$ml_cancel = $cancel_date;
				$cancel_time = strtotime($account->cancel_date);
			}
			$start_time = strtotime($account->signup_dt);
			$seconds_active = $cancel_time - $start_time;
			$months_active = round($seconds_active  / $seconds_per_month);
			$href = 'http://'.\epro\DOMAIN.cgi::href('account/product/'.$account->dept.'?aid='.$account->id);
			$ml .= '
				<tr>
					<td>'.($i + 1).'</td>
					<td>'.$account->dept.'</td>
					<td>'.$account->signup_dt.'</td>
					<td>'.$ml_cancel.'</td>
					<td>'.$months_active.'</td>
					<td><a target="_blank" href="'.$href.'">'.(($account->url) ? $account->url : '(none)').'</a></td>
					<td>'.$account->plan.'</td>
					<td>'.$account->source.'</td>
					<td>'.$account->name.'</td>
					<td>'.$account->email.'</td>
					<td>'.$account->phone.'</td>
				</tr>
			';
		}
		?>
		<div class="breakdown">
			<div>
				<b>Start Date:</b>
				<span><?= $start_date; ?></span>
			</div>
			<div>
				<b>End Date:</b>
				<span><?= $end_date; ?></span>
			</div>
			<div>
				<b>Column:</b>
				<span><?= $col; ?></span>
			</div>
			<?= $ml_buckets ?>
		</div>
		<table>
			<thead>
				<tr>
					<th></td>
					<th>Dept</th>
					<th>Signup</th>
					<th>Cancelled</th>
					<th>Months</th>
					<th>URL</th>
					<th>Plan</th>
					<th>Source</th>
					<th>Name</th>
					<th>Email</th>
					<th>Phone</th>
				</tr>
			</thead>
			<tbody>
				<?= $ml; ?>
			</tbody>
		</table>
		<?php
	}

	public function pre_output_report()
	{
		$keys = array('start_date', 'end_date', 'signup_date_filter', 'signup_start_date', 'signup_end_date');
		foreach ($keys as $key) {
			$this->$key = $_REQUEST[$key];
		}
	}


	public function display_report()
	{
		if (!$this->end_date)
		{
			$this->end_date = date(util::DATE);
			$this->start_date = substr($this->end_date, 0, 7).'-01';
		}
		?>
		<table id="form_table">
			<tbody>
				<?= cgi::date_range_picker($this->start_date, $this->end_date, array('table' => false)); ?>
				<tr>
					<td><label for="signup_date_filter">Signup Date Filter</label></td>
					<td><input type="checkbox" name="signup_date_filter" id="signup_date_filter" value="1"<?= (($this->signup_date_filter) ? ' checked' : '') ?> /></td>
				</tr>
				<?= cgi::date_range_picker($this->signup_start_date, $this->signup_end_date, array('table' => false, 'start_date_key' => 'signup_start_date', 'end_date_key' => 'signup_end_date')) ?>
				<tr>
					<td></td>
					<td><input type="submit" a0="action_report_submit" value="Download" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	public function action_report_submit()
	{
		$common_account_select = array("url", "dept", "partner", "source", "plan", "sales_rep", "contract_length as contract", "substring(signup_dt, 1, 10) as signup");
		$this->reps = db::select("
			select distinct u.id, u.realname
			from eppctwo.users u, eppctwo.user_guilds g
			where
				g.guild_id = 'sbr' &&
				g.user_id = u.id
		", 'NUM', 0);

		$signup_where = ($this->signup_date_filter) ? "&& account.signup_dt between '{$this->signup_start_date} 00:00:00' and '{$this->signup_end_date} 23:59:59'" : "";
		$data = payment::get_all(array(
			"select" => array(
				"payment" => array("id as pid", "date_attributed", "date_received", "event"),
				"payment_part" => array("account_id as aid", "type", "amount"),
				"account" => array_merge($common_account_select, array("('') as de_activated"))
			),
			"join_many" => array(
				"payment_part" => "payment.id = payment_part.payment_id",
				"account" => "payment_part.account_id = account.id"
			),
			"where" => "
				account.division = 'product' &&
				payment.date_attributed between '$this->start_date' and '$this->end_date'
				{$signup_where}
			",
			"flatten" => array(
				"account" => "payment_part"
			)
		));
		
		$de_activated = account::get_all(array(
			"select" => array("account" => array_merge($common_account_select, array("id as aid", "de_activation_date as de_activated"))),
			"where" => "
				account.division = 'product' &&
				account.de_activation_date between '$this->start_date' and '$this->end_date'
				{$signup_where}
			"
		));

		$cols = array(
			array('d' ,'pid'),
			array('d' ,'date_received'),
			array('d' ,'date_attributed'),
			array('d' ,'event'),
			
			array('pp','aid'),
			array('pp','url'),
			array('pp','signup'),
			array('pp','de_activated'),
			array('pp','contract'),
			array('pp','dept'),
			array('pp','partner'),
			array('pp','source'),
			array('pp','plan'),
			array('pp','sales_rep',1),
			array('pp','type'),
			array('pp','amount'),
			array('pp','pay_edit_url', 1)
		);
		$headers = '';
		for ($i = 0, $ci = count($cols); $i < $ci; ++$i) {
			list($obj_name, $col, $is_func) = $cols[$i];
			$headers .= (($i) ? ',' : '').util::display_text($col);
		}

		$out = '';
		foreach ($data as $i => $d) {
			foreach ($d->payment_part as $j => $pp) {
				$out .= $this->report_build_row($d, $pp, $cols);
			}
		}
		foreach ($de_activated as $i => $pp) {
			$d = new stdClass();
			$out .= $this->report_build_row($d, $pp, $cols);
		}

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment;filename="product-data_'.$this->start_date.'_'.$this->end_date.'.csv"');
		echo "$headers\n$out";
		exit;
	}

	private function report_build_row($d, $pp, &$cols)
	{
		$row = '';
		for ($i = 0, $ci = count($cols); $i < $ci; ++$i) {
			list($obj_name, $col, $is_func) = $cols[$i];
			if ($is_func) {
				$func = 'report_col_'.$col;
				$val = $this->$func($d, $pp);
			}
			else {
				$obj = ($obj_name == 'd') ? $d : $pp;
				$val = $obj->$col;
			}
			$row .= (($i) ? ',' : '').$val;
		}
		return $row."\n";
	}

	private function report_col_sales_rep($d, $pp)
	{
		return (array_key_exists($pp->sales_rep, $this->reps) ? $this->reps[$pp->sales_rep] :'');
	}

	private function report_col_pay_edit_url($d, $pp)
	{
		$base_url = 'http://'.\epro\DOMAIN.cgi::href('account/product/'.$pp->dept);
		return ($d->pid) ? $base_url.'/billing?aid='.$pp->aid.'&pid='.$d->pid : $base_url.'?aid='.$pp->aid;
	}
}

?>