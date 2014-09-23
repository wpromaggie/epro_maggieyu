<?php

class mod_sales_rep_breakdown extends mod_sales
{
	const GRACE_PERIOD_CUTOFF_DATE = '2011-09-27';
	
	public function pre_output()
	{
		// make sure user belongs here
		if (user::is_admin() || $this->is_user_director()) {
			$this->is_director = true;
		}
		else {
			$this->uid = user::$id;
		}
		parent::pre_output();
		
		list($this->start_date, $this->end_date, $this->do_show_all) = util::list_assoc($_REQUEST, 'start_date', 'end_date', 'do_show_all');
		if (empty($this->start_date))
		{
			$this->end_date = date(util::DATE);
			$this->start_date = substr($this->end_date, 0, 7).'-01';
		}
	}
	
	// sales is very old module, defines output itself,
	// so we override and call module base output
	public function output()
	{
		module_base::output();
	}
	
	private function get_data()
	{
		$this->management_part_types = client_payment_part::get_management_part_types();
		$qpart_type = "pp.type in ('".implode("','", $this->management_part_types)."')";

		$qselect = "
				p.client_id cl_id, p.id pid, p.date_received date, ".(($this->is_director) ? "p.sales_notes notes," : '')."
				group_concat(pp.id separator '\t') part_ids, group_concat(pp.type separator '\t') part_types, group_concat(pp.amount separator '\t') part_amounts, group_concat(pp.rep_pay_num separator '\t') rep_pay_nums, group_concat(pp.rep_comm separator '\t') rep_comms
			";
		$qwhere = array(
			"p.date_received between :start and :end",
			$qpart_type,
			"p.id = pp.client_payment_id"
		);
		$qdata = array(
			"start" => $this->start_date,
			"end" => $this->end_date
		);

		if (!$this->is_director) {
			// sales rep rather than admin, filter by clients they have sold to
			$qwhere[] = "p.client_id in (:cl_ids)";
			$qdata["cl_ids"] = db::select("
				select distinct client_id
				from eppctwo.sales_client_info
				where sales_rep = :rep
			", array(
				"rep" => $this->uid
			));
		}

		// get payments in current date range
		$payments_in_date_range = db::select("
			select $qselect
			from eppctwo.client_payment p, eppctwo.client_payment_part pp
			where ".implode(" && ", $qwhere)."
			group by p.id
			order by date asc
		", $qdata, 'ASSOC');

		// get unique client ids
		$cl_ids = array();
		foreach ($payments_in_date_range as &$p) {
			$cl_ids[$p['cl_id']] = 1;
		}
		$cl_ids = array_keys($cl_ids);

		// get account info
		$this->ac_info = db::select("
			select a.client_id, a.dept, a.id, a.name, s.sales_rep as rep, s.type as com_type
			from eac.account a, sales_client_info s
			where
				a.client_id in (:cl_ids) &&
				a.id = s.account_id
		", array(
			"cl_ids" => $cl_ids
		), 'ASSOC', array('client_id'), 'dept');

		// get all payments for clients with at least one payment in date range
		$all_client_payments_before_range = db::select("
			select $qselect
			from eppctwo.client_payment p, eppctwo.client_payment_part pp
			where
				p.client_id in (:cl_ids) &&
				p.date_received < :start &&
				{$qpart_type} &&
				p.id = pp.client_payment_id
			group by p.id
			order by date asc
		", array(
			"cl_ids" => $cl_ids,
			"start" => $this->start_date
		), 'ASSOC');

		// order matters here
		$this->payments = array_merge($all_client_payments_before_range, $payments_in_date_range);

		// get sales rep info
		$this->cur_sales_reps = db::select("
			select distinct u.id, u.realname
			from eppctwo.users u, eppctwo.sales_client_info s
			where
				s.client_id in (:cl_ids) &&
				u.id = s.sales_rep
			order by realname asc
		", array("cl_ids" => $cl_ids), 'NUM', 0);
		
		$this->com_vals = db::select("
			select dept, com_type, percent
			from eppctwo.sales_commission
		", 'NUM', array(0), 1);
		
		$this->type_to_dept = $this->get_pay_type_to_dept_map();
	}

	public function action_download()
	{
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment;filename="sales-rep-data_'.$this->start_date.'_'.$this->end_date.'.csv"');
		echo $_POST['dldata'];
		exit;
	}

	public function display_index()
	{
		$this->get_data();
		?>
		<table class="date_range_picker">
			<tbody>
				<?php echo cgi::date_range_picker($this->start_date, $this->end_date, array('table' => false)); ?>
				<?php echo $this->ml_do_show_all_payments(); ?>
				<tr>
					<td></td>
					<td><input type="submit" value="Set Dates" /></td>
				</tr>
			</tbody>
		</table>
		<?= (($this->is_director) ? '<a id="download" href="">Download CSV</a>' : '') ?>
		<div id="breakdown"></div>
		<input type="hidden" name="dldata" id="dldata" />
		<?php
		//e($this->ac_info);
		cgi::add_js_var('grace_period_cutoff_date', self::GRACE_PERIOD_CUTOFF_DATE);
		cgi::add_js_var('payments', $this->payments);
		cgi::add_js_var('ac_info', $this->ac_info);
		cgi::add_js_var('type_to_dept', $this->type_to_dept);
		cgi::add_js_var('management_part_types', $this->management_part_types);
		cgi::add_js_var('do_show_all', $this->do_show_all);
		cgi::add_js_var('sales_reps', $this->cur_sales_reps);
		cgi::add_js_var('com_vals', $this->com_vals);
		if ($this->is_director) {
			cgi::add_js_var('is_multiview', 1);
		}
	}
	
	private function ml_do_show_all_payments()
	{
		if ($this->is_director) {
			return '
				<tr>
					<td>Show All Payments</td>
					<td><input type="checkbox" id="do_show_all" name="do_show_all" value="1"'.(($this->do_show_all) ? ' checked' : '').' /></td>
				</tr>
			';
		}
		else {
			return '';
		}
	}
	
	public function ajax_set_new_pay_num()
	{
		$cpp = new client_payment_part($_POST);
		$cpp->put();
	}

	public function ajax_set_new_pay_comm()
	{
		$_POST['rep_comm'] = preg_replace("/[^0-9\.-]/", '', $_POST['rep_comm']);
		$cpp = new client_payment_part($_POST);
		$r = $cpp->update();
		echo ($r) ? 1 : 0;
	}

	public function ajax_set_notes()
	{
		$cp = new client_payment($_POST);
		$cp->put();
	}
}

?>