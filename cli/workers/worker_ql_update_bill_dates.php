<?php
util::load_lib('ql', 'sbs');

// todo: prepay roll dates are all 0
// this was used to send multi month clients monthly reports
class worker_ql_update_bill_dates extends worker
{
	public function run()
	{
		$date = (array_key_exists('d', cli::$args)) ? cli::$args['d'] : date(util::DATE, time() - 86400);
		$accounts = ap_ql::get_all(array(
			'select' => "ap_ql.id, bill_day, prepay_roll_date",
			'where' => "
				prepay_paid_months > 0 &&
				bill_day > 0 && bill_day < 32 &&
				prepay_roll_date = '$date'
			"
		));
		foreach ($accounts as $account) {
			$account->update_from_array(array(
				'prepay_roll_date' => util::delta_month($account->prepay_roll_date, 1, $account->bill_day)
			));
		}
	}
}

?>