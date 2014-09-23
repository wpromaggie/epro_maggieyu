<?php
util::load_lib('as', 'billing');

class worker_partner_auto_bill extends worker
{
	// track what happens with each client
	// to be emailed to leader of partner dept
	private $email_log;
	
	public function run()
	{
		$today = date(util::DATE);
		$target_date = (array_key_exists('d', cli::$args)) ? cli::$args['d'] : $today;
		$accounts = as_partner::get_all(array(
			'where' => "account.next_bill_date = :target_date && account.status = 'Active'",
			'data' => array("target_date" => $target_date)
		));
		
		// set partner default amount keys
		$payment_types = array();
		foreach (client_payment_part::$part_types as $type => $dept) {
			if (
				($dept == 'partner') &&
				(in_array($type, as_partner::$recurring_payment_types))
			) {
				$payment_types[] = util::simple_text($type);
			}
		}

		$this->email_log = array();
		foreach ($accounts as $account) {
			if (!$account->cc_id) {
				$this->log_error($account, 'No default credit card set');
			}
			else {
				// payment data for record_payment()
				$data = array(
					'pay_id' => $account->cc_id,
					'date_received' => $target_date,
					'date_attributed' => $target_date
				);
				$total = 0;
				foreach ($payment_types as $type) {
					$partner_type_key = as_partner::get_account_payment_type_key($type);
					$amount = str_replace(array('$', ','), '', $account->$partner_type_key);
					if (is_numeric($amount) && $amount > 0) {
						// add to data
						$data['amount_'.$type] = $amount;
						$total += $amount;
					}
				}

				// no partner payment amounts set for this client
				if ($total == 0) {
					$this->log_error($account, 'No default payment amounts set');
				}
				else {
					if (as_lib::record_payment($account, $data)) {
						$this->log_success($account, 'Successfully charged ('.util::format_dollars($total).')');
						
						// update last/next bill date
						$account->update_from_array(array(
							'prev_bill_date' => $account->next_bill_date,
							'next_bill_date' => util::delta_month($account->next_bill_date, 1, $account->bill_day)
						));
					}
					else {
						$this->log_error($account, 'Charge failed - '.billing::get_error());
					}
				}
			}
		}

		// exec gets all messages
		$exec_email = db::select_one("
			select u.username
			from eppctwo.users u, eppctwo.user_role r
			where
				r.guild = 'partner' &&
				r.role = 'Auto-Billing Monitor' &&
				r.user = u.id
		");
		
		$managers = db::select("
			select id, username
			from eppctwo.users
			where
				primary_dept = 'partner' &&
				username <> :username
		", array('username' => $exec_email));
		
		$all_msgs = '';
		for ($i = 0, $ci = count($managers); $i < $ci; ++$i) {
			list($uid, $manager_email) = $managers[$i];
			if (array_key_exists($uid, $this->email_log)) {
				$msgs = $this->email_log[$uid];
				$manager_msg_str = implode("\n", $msgs);
			}
			else { 
				$manager_msg_str = 'No clients to be billed';
			}
			$all_msgs .= "\n---\n{$manager_email}\n---\n{$manager_msg_str}\n\n";
			util::mail('auto-billing@wpromote.com', $manager_email, 'Partner Auto-Billing '.$target_date, $manager_msg_str);
		}
		
		if ($exec_email) {
			util::mail('auto-billing@wpromote.com', $exec_email, 'Partner Auto-Billing Summary '.$target_date, $all_msgs);
		}
	}
	
	private function log($account, $msg)
	{
		$this->email_log[$account->manager][] = $account->name.' (http://'.\epro\DOMAIN.'/account/service/partner/billing?aid='.$account->id.'): '.$msg;
	}
	
	private function log_error($account, $msg)
	{
		$this->log($account, $msg);
		payment_error::create(array(
			'user' => 0,
			'dept' => 'partner',
			'client_id' => $account->client_id,
			'account_id' => $account->id,
			'msg' => $msg
		));
	}
	
	private function log_success($account, $msg)
	{
		$this->log($account, $msg);
		payment_error::create(array(
			'user' => 0,
			'dept' => 'partner',
			'client_id' => $account->client_id,
			'account_id' => $account->id,
			'msg' => $msg
		));
	}
}

?>