<?php

class mod_product_process_payments extends mod_product
{
	public function pre_output()
	{
		parent::pre_output();
	}
	
	public function display_index()
	{
		// default status: Active or Long Term NonRenewing
		$status = util::unempty($_POST['status'], 'Active/LTNR');
		$bill_date = util::unempty($_POST['bill_date'], date(util::DATE));
		
		if ($status == '*') {
			$q_status = '';
		}
		else {
			$q_status = "&& a.status ".(($status == 'Active/LTNR') ? "in ('Active', 'NonRenewing')" : "= '$status'");
		}
		$url_concat = "'<a target=\"_blank\" href=\"".cgi::href('account/product/')."', a.dept, '/billing?aid=', a.id, '\">', a.url, '</a>'";
		$tmp_accounts = db::select("
			select a.id _id, a.url, concat({$url_concat}) _display_url, upper(a.dept) dept, a.status, a.plan, a.partner, a.prepay_paid_months, a.cancel_date, a.de_activation_date, p.date_attributed last_date, p.amount last_amount
			from eac.account as a
			left outer join eac.payment_part as pp on a.id = pp.account_id
			left outer join eac.payment as p on pp.payment_id = p.id
			where
				a.next_bill_date = '$bill_date' &&
				a.division = 'product'
				{$q_status}
			order by last_date desc
		", 'ASSOC');
		
		$ac_ids = array();
		$accounts = array();
		foreach ($tmp_accounts as $account) {
			$ac_id = $account['_id'];
			if (!array_key_exists($ac_id, $ac_ids)) {
				$ac_ids[$ac_id] = 1;
				if ($status == 'Active/LTNR' && $account['status'] == 'NonRenewing') {
					// de activation over 10 days past bill date we are checking, ok go
					if ((strtotime($account['de_activation_date']) - strtotime($bill_date)) > 864000) {
						$accounts[] = $account;
					}
				}
				else {
					$accounts[] = $account;
				}
			}
		}
		
		$status_options = array(array('*', ' - All'), array('Active/LTNR', 'Active/LTNR'));
		foreach (account::$status_options as $status_option) {
			$status_options[] = array($status_option, $status_option);
		}
		?>
		<h1>Process Payments</h1>
		<table>
			<tbody>
				<tr>
					<td>Bill Date</td>
					<td><input type="text" class="date_input" name="bill_date" value="<?php echo $bill_date; ?>" /></td>
				</tr>
				<tr>
					<td>Status</td>
					<td><?php echo cgi::html_select('status', $status_options, $status); ?></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		<div id="payments_table" ejo></div>
		<?php
		cgi::add_js_var('accounts', $accounts);
	}
}
