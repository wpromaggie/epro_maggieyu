<?php
util::load_lib('e2', 'as', 'log', 'billing');

class worker_seo_billing extends worker
{
	const DEV_EMAIL = 'chimdi@wpromote.com';

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
			select c.id, c.name, s.manager, s.billing_reminder, s.bill_day, s.prev_bill_date, s.next_bill_date
			from eppctwo.clients c, eppctwo.clients_seo s
			where
				s.next_bill_date in (:target_dates) &&
				s.status in ('Active', 'On') &&
				c.id = s.client
			order by next_bill_date asc
		", array('target_dates' => $target_dates), 'ASSOC', array('manager'));
		
		$other_headers = array();
		if (array_key_exists('g', cli::$args)) {
			$other_headers['Bcc'] = self::DEV_EMAIL;
		}
		foreach ($clients as $manager => $man_clients) {
			$reminders = array();
			foreach ($man_clients as $client) {
				// add to reminders
				if ($client['billing_reminder']) {
					$reminders[] = "{$client['name']} ({$client['next_bill_date']})";
				}

				// update bill dates
				db::update("eppctwo.clients_seo", array(
					'prev_bill_date' => $client['next_bill_date'],
					'next_bill_date' => util::delta_month($client['next_bill_date'], 1, $client['bill_day'])
				), "client = {$client['id']}");
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