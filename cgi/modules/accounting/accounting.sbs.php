<?php

class mod_accounting_sbs extends mod_accounting
{
	private static $amount_keys = array('charged', 'deferred', 'pseudo', 'origin', 'allocated');
	private static $depts = array('ql', 'sb', 'gs');
	
	public function pre_output()
	{
		parent::pre_output();
		util::load_lib('sbs');
	}
	
	public function display_index()
	{
		list($start_date, $end_date, $include_checks, $pseudo_origin, $origin_start, $origin_end) = util::list_assoc($_POST, 'start_date', 'end_date', 'include_checks', 'pseudo_origin', 'origin_start', 'origin_end');
		if (empty($start_date))
		{
			$end_date = date(util::DATE);
			$start_date = substr($end_date, 0, 7).'-01';
			$include_checks = false;
		}
		
		$payments = payment::get_all(array(
			"select" => array(
				"payment" => array("id as pid", "date_attributed as date", "event", "notes"),
				"payment_part" => array("id as ppid", "dept", "account_id as ac_id", "type", "amount"),
				"account" => array("prepay_paid_months", "prepay_free_months")
			),
			"join_many" => array(
				"payment_part" => "payment.id = payment_part.payment_id",
				"account" => "payment_part.account_id = account.id"
			),
			"where" => "
				account.division = 'product' &&
				payment.date_attributed between '$start_date' and '$end_date'
				".(($include_checks) ? "" : " && payment.pay_method <> 'check'")."
			",
			"flatten" => array(
				"account" => "payment_part"
			)
		));

		$by_dept = array();
		foreach (self::$depts as $dept) {
			$by_dept[$dept] = array_combine(self::$amount_keys, array_fill(0, count(self::$amount_keys), 0));
		}
		
		for ($i = 0, $ci = $payments->count(); $i < $ci; ++$i) {
			$payment = $payments->i($i);
			$pps = $payment->payment_part;
			for ($j = 0, $cj = $pps->count(); $j < $cj; ++$j) {
				$pp = $pps->i($j);
				if (!array_key_exists($pp->dept, $by_dept)) {
					continue;
				}
				$is_multi_month = (($pp->prepay_paid_months + $pp->prepay_free_months) > 1);
				$amounts = array_combine(self::$amount_keys, array_fill(0, count(self::$amount_keys), 0));
				if ($is_multi_month) {
					// first payment in installment, we actually charged client!
					if (stripos($payment->notes, 'ai1 ') === 0) {
						if (preg_match("/ \((.\d*)/", $payment->notes, $matches)) {
							$amounts['charged'] = $matches[1];
						}
						else {
							$amounts['charged'] = $pp->amount;
							$this->give_bad_note_feedback($pp->dept, $pp->ac_id, $payment->pid);
						}
						$amounts['deferred'] = $amounts['charged'] - $pp->amount;
						$amounts['pseudo'] = 0;
					}
					else {
						$amounts['charged'] = 0;
						$amounts['deferred'] = 0;
						$amounts['pseudo'] = $pp->amount;
						if ($pseudo_origin) {
							if (!preg_match("/^ai(\d+)/i", $payment->notes, $matches)) {
								$this->give_bad_note_feedback($pp->dept, $pp->ac_id, $payment->pid);
							}
							else {
								$ai_num = $matches[1];
								$first_payment_date = util::delta_month($payment->date, 1 - $ai_num);
								if ($first_payment_date >= $origin_start && $first_payment_date <= $origin_end) {
									$amounts['origin'] = $pp->amount;
								}
								else {
									// first payment doesn't land in our origin time frame
								}
							}
						}
					}
				}
				// "normal" non multi-month payment
				else {
					$amounts['charged'] = $pp->amount;
					$amounts['deferred'] = 0;
					$amounts['pseudo'] = 0;
				}
				// allocated should match the sbr accounting area
				$amounts['allocated'] = ($amounts['charged'] - $amounts['deferred']) + $amounts['pseudo'];
				
				foreach (self::$amount_keys as $key) {
					$by_dept[$pp->dept][$key] += $amounts[$key];
				}
			}
		}
		
		$ml = '';
		foreach ($by_dept as $dept => $dept_vals)
		{
			$ml .= $this->ml_amounts_row($dept, $dept_vals, $pseudo_origin);
		}
		?>
		<h1>Accounting :: SBS</h1>
		
		<!-- form -->
		<table id="payments_form">
			<tbody>
				<?php echo cgi::date_range_picker($start_date, $end_date, array('table' => false)); ?>
				<tr>
					<td>Include Checks</td>
					<td><input type="checkbox" id="include_checks" name="include_checks" value="1"<?php echo (($include_checks) ? ' checked' : ''); ?> /></td>
				</tr>
				<tr>
					<td>Pseudo Origin</td>
					<td><input type="checkbox" id="pseudo_origin" name="pseudo_origin" value="1"<?php echo (($pseudo_origin) ? ' checked' : ''); ?> /></td>
				</tr>
				<?php echo cgi::date_range_picker($origin_start, $origin_end, array('table' => false, 'start_date_key' => 'origin_start', 'end_date_key' => 'origin_end')); ?>
				<tr>
					<td></td>
					<td><input type="submit" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		
		<!-- data -->
		<table>
			<thead>
				<tr>
					<th>Dept</th>
					<th>Charged</th>
					<th>Deferred</th>
					<th>Pseudo</th>
					<?php echo (($pseudo_origin) ? '<th>Origin</th>' : ''); ?>
					<th>Allocated</th>
				</tr>
			</thead>
			<tbody>
				<?php echo $ml; ?>
			</tbody>
			<tfoot>
				<?php $this->print_totals($by_dept, $pseudo_origin); ?>
			</tfoot>
		</table>
		<?php
	}
	
	private function give_bad_note_feedback($dept, $ac_id, $pid)
	{
		$url = cgi::href('/account/product/'.$dept.'/billing?aid='.$ac_id.'&pid='.$pid.'"');
		$bad_note_link = '<a target="_blank" href="'.$url.'">Link</a>';
		feedback::add_error_msg('Non standard note for amortized payment: '.$bad_note_link);
	}
	
	private function print_origin_pseudos($pseudo_origin, $pseudo_origin_amount)
	{
		echo '
			<div>
				<strong>Pseudo Origin Amount: </strong>
				<span>'.util::format_dollars($pseudo_origin_amount).'</span>
			</div>
		';
	}
	
	private function ml_amounts_row($header, $vals, $pseudo_origin)
	{
		return '
			<tr>
				<td>'.$header.'</td>
				<td>'.util::format_dollars($vals['charged']).'</td>
				<td>'.util::format_dollars($vals['deferred']).'</td>
				<td>'.util::format_dollars($vals['pseudo']).'</td>
				'.(($pseudo_origin) ? '<td>'.util::format_dollars($vals['origin']).'</td>' : '').'
				<td>'.util::format_dollars($vals['allocated']).'</td>
			</tr>
		';
	}
	
	private function print_totals($by_dept, $pseudo_origin)
	{
		$totals = array_fill(0, count(self::$amount_keys), 0);
		foreach ($by_dept as $dept => $dept_vals)
		{
			foreach ($dept_vals as $k => $v)
			{
				$totals[$k] += $v;
			}
		}
		echo $this->ml_amounts_row('Totals', $totals, $pseudo_origin);
	}
}

?>