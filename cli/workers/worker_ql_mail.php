<?php
util::load_lib('sbs', 'ql');

class worker_ql_mail extends worker
{
	// number of days prior to rollover when we send multi month reminder
	const MULTI_MONTH_REMINDER_DAYS = 15;

	public function run()
	{
		if (array_key_exists('m', cli::$args)) {
			$method = cli::$args['m'];
			if (method_exists($this, $method)) {
				$this->$method();
			}
		}
	}
	
	public function monthly_report()
	{
		$date = (array_key_exists('d', cli::$args)) ? cli::$args['d'] : date(util::DATE);
		$do_report_date = date(util::DATE, strtotime($date.' +'.ql_lib::EMAIL_REPORTING_DAYS.' days'));
		
		$accounts = ap_ql::get_all(array('where' => "
			do_report &&
			(status in ('Active', 'NonRenewing')) &&
			(next_bill_date = '$do_report_date' || (prepay_paid_months > 0 && prepay_roll_date = '$do_report_date'))
		"));
		foreach ($accounts as $account) {
			sbs_lib::send_email($account, 'Report');
		}
	}
	
	public function multi_month_reminder()
	{
		$date = (array_key_exists('d', cli::$args)) ? cli::$args['d'] : date(UTIL::DATE, time() + ((self::MULTI_MONTH_REMINDER_DAYS) * 86400));
		$accounts = ap_ql::get_all(array('where' => "
			status = 'Active' &&
			next_bill_date = '$date' &&
			prepay_paid_months > 0
		"));
		foreach ($accounts as $account) {
			sbs_lib::send_email($account, 'Multi-Reminder');
		}
	}
}

?>