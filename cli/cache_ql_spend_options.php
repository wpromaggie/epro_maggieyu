<?php
require('cli.php');
util::load_lib('cache', 'sbs', 'ql', 'cache');

cli::run();

class cache_ql_spend_options
{
	const SPEND_ROUND = 10;

	public function cron()
	{
		// get plans
		$plans = db::select("
			select name, budget
			from eppctwo.ql_plans
		", 'NUM', 0);

		// get all active ql accounts
		$accounts = product::get_all(array(
			'select' => array(
				"account" => array("id", "plan", "prepay_paid_months", "prepay_free_months"),
				"product" => array("alt_recur_amount")
			),
			'where' => "
				account.dept = 'ql' &&
				account.status in ('Active', 'NonRenewing')
			"
		));

		// build array of amount => count
		// and maps back from amount to account ids
		$aid_maps = array();
		$amounts = array();
		foreach ($accounts as $i => $account) {
			// no alt recur amount, get from plan
			if ($account->alt_recur_amount == 0) {
				$amount = $plans[$account->plan];
			}
			// use alt recur amount, factor in number of months of prepay
			else {
				$total_months = $account->prepay_paid_months + $account->prepay_free_months;
				$amount = ($total_months > 0) ? $account->alt_recur_amount / $total_months : $account->alt_recur_amount;
			}
			$rounded = round($amount / self::SPEND_ROUND) * self::SPEND_ROUND;
			// sum counts for this amount
			if (!isset($amounts[$rounded])) {
				$amounts[$rounded] = 1;
				$aid_maps[$rounded] = array($account->id);
			}
			else {
				$amounts[$rounded]++;
				$aid_maps[$rounded][] = $account->id;
			}
		}

		$options = array();
		ksort($amounts);
		foreach ($amounts as $amount => $c) {
			list($min, $max) = $this->get_rounded_min_max($amount);
			$options[] = array($amount, util::format_dollars($min).' - '.util::format_dollars($max).' ('.$c.')');
		};
		cache::write('ql-spend-budget-options.json.cache', json_encode($options));
		foreach ($aid_maps as $amount => $map) {
			cache::write('ql-spend-'.$amount.'-aid-map.sql.cache', "'".implode("','", $map)."'");
		}
	}

	private function get_rounded_min_max($budget)
	{
		$half_round = self::SPEND_ROUND / 2;
		$min = $budget - $half_round;
		$max = $budget + $half_round - .01;
		return array($min, $max);
	}
}

?>