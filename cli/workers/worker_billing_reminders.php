<?php
util::load_lib('e2', 'as', 'log', 'billing');

class worker_billing_reminders extends worker
{
	const DEV_EMAIL = 'ryan@wpromote.com';

	public function run()
	{
		if (isset(cli::$args['g'])) {
			db::dbg();
		}
		$today = date(util::DATE);
		$target_date = (array_key_exists('d', cli::$args)) ? cli::$args['d'] : $today;
		$time = strtotime($target_date);

		// on monday, also send reminders for the weekend
		if (date('w', $time) == 1) {
			$target_dates = array($target_date, date(util::DATE, $time - 86400), date(util::DATE, $time - 172800));
		}
		else {
			$target_dates = array($target_date);
		}
		
		$clients = db::select("
			select 
				c.id as id, c.name,
				a.id as account_id, a.manager, a.bill_day, a.prev_bill_date, a.next_bill_date, a.dept,
				u.username
			from eac.client c, eac.account a, eppctwo.users u
			where
				a.next_bill_date in (:target_dates) &&
				a.status in ('Active', 'On') &&
				a.division = 'service' &&
				a.dept not in ('partner') &&
				c.id = a.client_id &&
				a.manager = u.id
			order by a.next_bill_date asc
		", array('target_dates' => $target_dates), 'ASSOC', array('username'));

		e($clients);
		
		$other_headers = array();
		if (array_key_exists('g', cli::$args)) {
			$other_headers['Bcc'] = self::DEV_EMAIL;
		}
		foreach ($clients as $manager => $man_clients) {
			$reminders = array();
			foreach ($man_clients as $client) {

				//maybe want to add a billing_reminder field here...
				$reminders[] = "{$client['name']} ({$client['next_bill_date']})";

				//just update seo bill dates
				if ($client['dept']=='seo'){
					db::update("eac.account", array(
						'prev_bill_date' => $client['next_bill_date'],
						'next_bill_date' => util::delta_month($client['next_bill_date'], 1, $client['bill_day'])
					), "id = '{$client['account_id']}'");
				}
			}
			
			// send reminders
			if (util::is_email_address($manager) && $reminders) {
				$msg_body = "Clients up for billing today:\n\n".implode("\n", $reminders);
				util::mail('auto-billing@wpromote.com', $manager, 'Billing Reminder - '.$target_date, $msg_body, $other_headers);
			}
		}
	}
}

?>