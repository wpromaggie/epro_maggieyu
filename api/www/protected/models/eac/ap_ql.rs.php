<?php

class mod_eac_ap_ql extends mod_eac_product
{
	public static $db, $cols, $primary_key;
	
	public static $plan_options = array('Basic','Bo1','Bo2','Bo3','Bo4','Core','Core_297','Gold','GoldQL','LAL','LAL1','LALgold','LALplatinum','LALsilver','Plat','PlatQL','Plus','Premier','SMOplat','Starter','Starter_149');
	
	public static function set_table_definition()
	{
		self::$db = 'eac';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'              ,'char'    ,16  ,''    ,rs::READ_ONLY),
			new rs_col('alt_num_keywords','smallint',5   ,0     ,rs::UNSIGNED),
			new rs_col('is_3_day_done'   ,'bool'    ,null,0     ),
			new rs_col('is_7_day_done'   ,'bool'    ,null,0     )
		);
	}
	
	public function load_from_post($prod_num)
	{
		parent::load_from_post($prod_num, array('who_creates', 'title', 'desc1', 'desc2'));
		for ($i = 0; ($keybase = 'kw'.$i) && ($key = $keybase.'_'.$prod_num) && array_key_exists($key, $_POST); ++$i)
		{
			$this->$keybase = $_POST[$key];
		}
	}

	public function get_data_sources()
	{
		return ql_data_source::get_all(array(
			'select' => "market, account, campaign, ad_group",
			'where' => "account_id = :aid",
			'key_col' => "market",
			'data' => array("aid" => $this->id)
		));
	}

	public static $plan_budgets = false;

	public static function get_plan_budget($plan)
	{
		if (empty(self::$plan_budgets)) {
			self::$plan_budgets = db::select("
				select name, budget
				from eppctwo.ql_plans
			", 'NUM', 0);
		}
		return (array_key_exists($plan, self::$plan_budgets)) ? self::$plan_budgets[$plan] : false;
	}

	public function update_data($data_sources = false, $eac_to_mac = false)
	{
		// set data sources if not passed in
		if (!$data_sources) {
			$tmp = db::select("
				select ds.market, ds.account_id, ds.account, ds.campaign, ds.ad_group
				from eac.ap_ql q, eac.account a, eppctwo.ql_data_source ds
				where
					a.id = :aid &&
					q.id = a.id &&
					q.id = ds.account_id
			", array('aid' => $this->id));
			$eac_to_mac = array();
			$data_sources = array();
			for ($i = 0; list($market, $cl_id, $ac_id, $ca_id, $ag_id) = $tmp[$i]; ++$i) {
				if (!isset($eac_to_mac[$market][$cl_id])) $eac_to_mac[$market][$cl_id] = $ac_id;

				if (!empty($ag_id)) $data_sources[$market][$cl_id]['ad_group_id'][] = $ag_id;
				else if (!empty($ca_id)) $data_sources[$market][$cl_id]['campaign_id'][] = $ca_id;
				else $data_sources[$market][$cl_id]['account_id'][] = $ac_id;
			}
			// also clear data for this account
			db::delete("eppctwo.ql_spend", "account_id = :aid", array('aid' => $this->id));
		}

		if ($this->alt_recur_amount) {
			$budget = $this->alt_recur_amount;
			$pay_option_months = $this->prepay_paid_months;
			if ($pay_option_months > 1) {
				$budget /= $pay_option_months;
			}
		}
		else {
			$budget = self::get_plan_budget($this->plan);
		}
		if (!is_numeric($budget) || $budget < 0) {
			$msg = "Error getting account budget: $this->id, $this->url, $this->plan";
			if (class_exists('feedback')) {
				feedback::add_error_msg($msg);
			}
			else {
				echo "$msg\n";
			}
			return false;
		}
		
		if (!is_numeric($this->bill_day) || $this->bill_day < 1 || $this->bill_day > 31) {
			$msg = "Error updating data, bad bill day: $this->id, $this->url, $this->bill_day";
			if (class_exists('feedback')) {
				feedback::add_error_msg($msg);
			}
			else {
				echo "$msg\n";
			}
			return false;
		}
		
		// must have last bill date set
		if (!preg_match("/^\d\d\d\d-\d\d-\d\d$/", $this->prev_bill_date)) {
			$msg = "Error updating data, previous bill date: $this->id, $this->url, $this->prev_bill_date";
			if (class_exists('feedback')) {
				feedback::add_error_msg($msg);
			}
			else {
				echo "$msg\n";
			}
			return false;
		}
		// even for multi-month plans, we just want to look at one month at a time
		$today = date(util::DATE);
		for (
			$month_start = $this->prev_bill_date, $month_end = util::delta_month($month_start, 1, $this->bill_day);
			$month_end < $today;
			$month_start = $month_end, $month_end = util::delta_month($month_start, 1, $this->bill_day)
		);
		$month_end = date(util::DATE, strtotime("$month_end -1 day"));
		
		// if next bill date is over 1 month away
		$days_to_date = $days_remaining = $days_in_month = 0;
		for ($date = $month_start; $date <= $month_end; $date = date(util::DATE, strtotime("$date +1 day"))) {
			if ($date < $today) {
				$days_to_date++;
			}
			else {
				$days_remaining++;
			}
		}
		$days_in_month = $days_to_date + $days_remaining;
		
		$prev_month_start = util::delta_month($month_start, -1, $this->bill_day);
		$prev_month_end = date(util::DATE, strtotime("$month_start -1 day"));

		$cols = array('imps', 'clicks', 'cost');
		$q_select = array();
		foreach ($cols as $col) {
			$q_select[] = "sum($col) as $col";
		}
		$ac_data = array_combine($cols, array_fill(0, count($cols), 0));
		$yesterday_data = array_combine($cols, array_fill(0, count($cols), 0));
		$spend_prev_month = 0;
		foreach (ql_lib::$markets as $market) {
			foreach ($cols as $col) {
				$yesterday_data["{$market}_{$col}"] = 0;
			}
			if (isset($data_sources[$market][$this->id])) {
				$market_ac_id = $eac_to_mac[$market][$this->id];

				// build query of ids that belong to this account
				$q_id_where = array();
				foreach ($data_sources[$market][$this->id] as $db_key => $db_vals) {
					$q_id_where[] = "{$db_key} in (:{$db_key})";
				}
				// data this fiscal month
				$d = db::select_row("
					select ".implode(", ", $q_select)."
					from {$market}_objects.all_data_Q{$market_ac_id}
					where
						data_date between '$month_start' and '$month_end' &&
						".implode(" && ", $q_id_where)."
				", $data_sources[$market][$this->id], 'ASSOC');
				// no data tables for this account
				if ($d === false) {
					continue;
				}
				foreach ($d as $k => $v) {
					$ac_data[$k] += $v;
				}
				// data from yesterday
				$d = db::select_row("
					select ".implode(", ", $q_select)."
					from {$market}_objects.all_data_Q{$market_ac_id}
					where
						data_date = '".date(util::DATE, \epro\NOW - 86400)."' &&
						".implode(" && ", $q_id_where)."
				", $data_sources[$market][$this->id], 'ASSOC');

				foreach ($d as $k => $v) {
					$yesterday_data[$k] += $v;
					$yesterday_data["{$market}_{$k}"] += $v;
				}
				
				// spend previous fiscal month
				$tmp = db::select_one("
					select sum(cost)
					from {$market}_objects.all_data_Q{$market_ac_id}
					where
						data_date between '$prev_month_start' and '$prev_month_end' &&
						".implode(" && ", $q_id_where)."
				", $data_sources[$market][$this->id]);
				if (!empty($tmp)) $spend_prev_month += $tmp;
			}
		}
		
		$spend_remaining = $budget - $ac_data['cost'];
		$daily_to_date = ($days_to_date > 0) ? ($ac_data['cost'] / $days_to_date) : 0;
		$daily_remaining = ($days_remaining > 0 && $spend_remaining > 0) ? ($spend_remaining / $days_remaining) : 0;
		
		$ql_spend_data = array_merge($yesterday_data, array(
			'account_id' => $this->id,
			'days_to_date' => $days_to_date,
			'days_remaining' => $days_remaining,
			'days_in_month' => $days_in_month,
			'imps_to_date' => $ac_data['imps'],
			'spend_to_date' => $ac_data['cost'],
			'spend_remaining' => $spend_remaining,
			'spend_prev_month' => $spend_prev_month,
			'daily_to_date' => $daily_to_date,
			'daily_remaining' => $daily_remaining
		));

		db::insert("eppctwo.ql_spend", $ql_spend_data);
	}
}
?>
