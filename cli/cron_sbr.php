<?php
require_once('cli.php');
util::load_lib('sbs', 'sb');

#db::dbg();
cli::run();

class cron_sbr
{
	// number of days prior to rollover when we send multi month reminder
	const SBR_EXPRESS_CHECKUP_DAYS = 14;
	
	public static function sb_express_checkup()
	{
		$simulate = (array_key_exists('s', cli::$args));
		$dbg = (array_key_exists('g', cli::$args));

		// set dates
		if (array_key_exists('b', cli::$args)) {
			if (!array_key_exists('e', cli::$args)) {
				cli::error('must supply end date if giving begin date');
			}
			list($start_date, $end_date) = util::list_assoc(cli::$args, 'b', 'e');
		}
		else {
			if (array_key_exists('d', cli::$args)) {
				$date = cli::$args['d'];
			}
			else {
				$date = date(util::DATE, time() - (self::SBR_EXPRESS_CHECKUP_DAYS * 86400));
			}
			$start_date = $end_date = $date;
		}
		$date_str = ($start_date == $end_date) ? $start_date : "{$start_date} to {$end_date}";

		if ($dbg) {
			db::dbg();
		}
		$accounts = ap_sb::get_all(array(
			'select' => array(
				"account" => array("id", "client_id", "dept", "url", "signup_dt as signup", "status", "manager", "plan", "partner", "source", "subid"),
				"contacts" => array("name", "email"),
				"users" => array("realname as rep")
			),
			'left_join' => array(
			    "users" => "account.sales_rep = users.id"
			),
			'join' => array(
				"contacts" => "account.client_id = contacts.client_id"
			),
			'where' => "
				account.signup_dt between '{$start_date} 00:00:00' and '{$end_date} 23:59:59' &&
				account.plan = 'Express' &&
				account.status = 'Active'
			",
			'order_by' => "account.signup_dt desc, product.oid desc"
		));
		// write email body
		$body = '';
		if ($accounts->count() > 0) {
			foreach ($accounts as $i => $ac) {
				$e2_url = "http://e2.wpromote.com/account/product/sb?aid={$ac->id}";
				$rep = (!empty($ac->users->rep)) ? $ac->users->rep : 'No Rep';
				$body .= ($i + 1).": {$e2_url}, $rep\n";
			}
		}
		// didn't find any solo sb express accounts
		if (empty($body)) {
			$body = 'No SB Express solo account signups for '.$date_str;
		}
		$mail_opts = array();
		$other_opts = array();

		$sbr_admins = db::select("
			select users.username
			from users
			join user_guilds on
				users.id = user_guilds.user_id &&
				guild_id = 'sbr' &&
				role = 'Leader'
			where primary_guild = 'sbr'
		");
		$to = implode(', ', $sbr_admins);
		if ($dbg) {
			$mail_opts['Bcc'] = 'chimdi@wpromote.com';
		}
		if ($simulate) {
			$other_opts['dbg'] = true;
		}
		util::mail('postmaster@wpromote.com', $to, 'SB Express - '.$date_str, $body, $mail_opts, $other_opts);
	}
}

?>