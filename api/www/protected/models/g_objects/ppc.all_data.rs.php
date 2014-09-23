<?php

/*
 * not generally used like typical rs class
 * db library called directly, normal php array returned
 */

// todo: option to separate markets

util::load_lib('data_cache');

class all_data extends rs_object
{
	public static $db, $cols, $primary_key, $uniques, $indexes;

	public static $object_key, $test_key;

	const NUM_DATA_TESTS = 4;
	public static $data_tests = array(
		'multi_column_pk',
		'derived_string_pk',
		'auto_inc_with_unique',
		'auto_inc_with_clobber'
	);
	
	public static function set_table_definition()
	{
		if (empty(self::$object_key)) {
			die('no data object key');
		}
		$ok_number = preg_replace("/[^\d]/", '', self::$object_key);
		if (!is_numeric($ok_number)) {
			die('bad object key: '.self::$object_key);
		}
		$test_num = $ok_number % self::NUM_DATA_TESTS;
		self::$test_key = self::$data_tests[$test_num];
		return call_user_func(array('all_data', 'define_'.self::$test_key));
		// self::$cols = self::init_cols(
		// 	new rs_col('job_id'     ,'char'     ,24  ,''          ,rs::NOT_NULL),
		// 	new rs_col('account_id' ,'char'     ,32  ,''          ,rs::NOT_NULL),
		// 	new rs_col('campaign_id','char'     ,32  ,''          ,rs::NOT_NULL),
		// 	new rs_col('ad_group_id','char'     ,32  ,''          ,rs::NOT_NULL),
		// 	new rs_col('ad_id'      ,'char'     ,32  ,''          ,rs::NOT_NULL),
		// 	new rs_col('keyword_id' ,'char'     ,32  ,''          ,rs::NOT_NULL),
		// 	new rs_col('device'     ,'char'     ,32  ,''          ,rs::NOT_NULL),
		// 	new rs_col('data_date'  ,'date'     ,null,'0000-00-00',rs::NOT_NULL),
		// 	new rs_col('imps'       ,'int'      ,10  ,'0'         ,rs::UNSIGNED | rs::NOT_NULL),
		// 	new rs_col('clicks'     ,'mediumint',8   ,'0'         ,rs::UNSIGNED | rs::NOT_NULL),
		// 	new rs_col('convs'      ,'smallint' ,5   ,'0'         ,rs::UNSIGNED | rs::NOT_NULL),
		// 	new rs_col('cost'       ,'double'   ,null,'0'         ,rs::UNSIGNED | rs::NOT_NULL),
		// 	new rs_col('pos_sum'    ,'int'      ,10  ,'0'         ,rs::UNSIGNED | rs::NOT_NULL),
		// 	new rs_col('revenue'    ,'double'   ,null,'0'         ,rs::NOT_NULL),
		// 	new rs_col('mpc_convs'  ,'smallint' ,5   ,'0'         ,rs::UNSIGNED | rs::NOT_NULL),
		// 	new rs_col('vt_convs'   ,'smallint' ,5   ,'0'         ,rs::UNSIGNED | rs::NOT_NULL)
		// );
	}

	private static function define_multi_column_pk()
	{
		self::$primary_key = array('data_date','ad_group_id','ad_id','keyword_id','device');
		self::$indexes = array(
			array('data_date', 'campaign_id')
		);
		self::$cols = call_user_func_array(array('all_data', 'init_cols'), array_merge(array(
			new rs_col('job_id','char'  ,24 ,''   ,rs::NOT_NULL),
		), self::define_common_cols()));
	}

	private static function define_derived_string_pk()
	{
		self::$primary_key = array('id');
		self::$indexes = array(
			array('data_date', 'campaign_id'),
			array('data_date', 'ad_group_id')
		);
		self::$cols = call_user_func_array(array('all_data', 'init_cols'), array_merge(array(
			new rs_col('id'    ,'char'  ,200,''   ,rs::READ_ONLY),
			new rs_col('job_id','char'  ,24 ,''   ,rs::NOT_NULL),
		), self::define_common_cols()));
	}

	private static function define_auto_inc_with_unique()
	{
		self::$primary_key = array('id');
		self::$uniques = array(
			array('data_date','ad_group_id','ad_id','keyword_id','device')
		);
		self::$indexes = array(
			array('data_date', 'campaign_id')
		);
		self::$cols = call_user_func_array(array('all_data', 'init_cols'), array_merge(array(
			new rs_col('id'         ,'bigint'  ,20  ,null   ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY | rs::AUTO_INCREMENT),
			new rs_col('job_id'     ,'char'    ,24  ,''     ,rs::NOT_NULL),
		), self::define_common_cols()));
	}

	private static function define_auto_inc_with_clobber()
	{
		self::$primary_key = array('id');
		self::$indexes = array(
			array('data_date', 'campaign_id'),
			array('data_date', 'ad_group_id')
		);
		self::$cols = call_user_func_array(array('all_data', 'init_cols'), array_merge(array(
			new rs_col('id'         ,'bigint'  ,20  ,null   ,rs::UNSIGNED | rs::NOT_NULL | rs::READ_ONLY | rs::AUTO_INCREMENT),
		), self::define_common_cols()));
	}

	public static function define_common_cols()
	{
		return array(
			new rs_col('account_id' ,'char'     ,32  ,''    ,rs::NOT_NULL),
			new rs_col('campaign_id','char'     ,32  ,''    ,rs::NOT_NULL),
			new rs_col('ad_group_id','char'     ,32  ,''    ,rs::NOT_NULL),
			new rs_col('ad_id'      ,'char'     ,32  ,''    ,rs::NOT_NULL),
			new rs_col('keyword_id' ,'char'     ,32  ,''    ,rs::NOT_NULL),
			new rs_col('device'     ,'char'     ,32  ,''    ,rs::NOT_NULL),
			new rs_col('data_date'  ,'date'     ,null,rs::DD,rs::NOT_NULL),
			new rs_col('imps'       ,'int'      ,10  ,'0'   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('clicks'     ,'mediumint',8   ,'0'   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('convs'      ,'smallint' ,5   ,'0'   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('cost'       ,'double'   ,null,'0'   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('pos_sum'    ,'int'      ,10  ,'0'   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('revenue'    ,'double'   ,null,'0'   ,rs::NOT_NULL),
			new rs_col('mpc_convs'  ,'smallint' ,5   ,'0'   ,rs::UNSIGNED | rs::NOT_NULL),
			new rs_col('vt_convs'   ,'smallint' ,5   ,'0'   ,rs::UNSIGNED | rs::NOT_NULL)
		);
	}

	// called before we begin importing data
	public static function pre_import($data_table, $refresh_dates)
	{
		if (empty($refresh_dates)) {
			return;
		}
		switch (self::$test_key) {
			case ('auto_inc_with_clobber'):
				db::delete(
					$data_table,
					"data_date in (:refresh_dates)",
					array('refresh_dates' => array_keys($refresh_dates))
				);
				break;
		}
	}

	// called for each row of data when processing market party reports
	public static function get_import_info($eac_id, $job_id, $data_date, $ag_id, $ad_id, $kw_id, $device)
	{
		switch (self::$test_key) {
			case ('multi_column_pk'):
				return array(
					self::$primary_key,
					array('job_id' => $job_id)
				);

			case ('derived_string_pk'):
				return array(
					self::$primary_key,
					array(
						'id' => "{$data_date}_{$ag_id}_{$ad_id}_{$kw_id}_{$device}",
						'job_id' => $job_id
					)
				);
			
			case ('auto_inc_with_unique'):
				return array(
					self::$uniques[0],
					array('job_id' => $job_id)
				);
			
			case ('auto_inc_with_clobber'):
				return array(
					array(),
					array()
				);
		}
	}

	public static function set_object_key($ok)
	{
		self::$object_key = $ok;
	}
	
	public $account, $aid, $tmp_data, $job, $table, $id_field, $id_field_select;
	public $market, $start_date, $end_date, $custom_dates, $time_period, $detail, $fields, $detail_fields, $conv_type_fields, $sort, $sort_dir, $total_type, $ext_type, $limit;
	public $campaigns, $ad_groups;
	public $data, $totals, $filter_totals, $time_period_totals;
	public $filter_info_query, $filter_id_query, $separate_totals;
	public $include_id, $is_vars_set, $include_bid, $do_group_keywords, $do_total, $do_time_period_total, $do_update_cache, $do_compute_metrics, $do_calc_percents, $include_msn_deleted, $is_map;
	public $content_only, $search_only;
	
	private static $all_base_data_fields = array('imps', 'clicks', 'cost', 'convs', 'pos', 'revenue');
	
	public function __construct($vars = array())
	{
		// defaults
		$this->include_id = true;
		$this->include_bid = true;
		$this->include_msn_deleted = true;
		$this->do_compute_metrics = true;
		$this->do_calc_percents = true;
		$this->do_total = true;
		$this->separate_totals = true;
		
		// set some default fields if we don't have any
		if (empty($vars['fields'])) {
			$vars['fields'] = array_combine(self::$all_base_data_fields, array_fill(0, count(self::$all_base_data_fields), 1));
		}
		$this->set_vars($vars);

		// initialize totals array with base fields
		if ($this->do_total) {
			$this->totals = $this->init_totals_array();
			$this->filter_totals = $this->init_totals_array();
		}
	}
	
	public static function get_data($vars = array())
	{
		$instance = new all_data($vars);
		$instance->do_get_data();
		return $instance;
	}

	// it is left up to the caller to determine if they would like to call get_data above
	// or schedule a job
	public static function schedule_reduce($vars = array(), $source)
	{
		$instance = new all_data($vars);
		if (!($instance->detail == 'keyword' || $instance->detail == 'ad')) {
			return false;
		}

		util::load_lib('delly');

		$spec_data = array('data_opts' => json_encode($vars), 'source' => $source);
		$job_data = array('account_id' => $instance->aid);
		return spec_reduce_market_data::schedule($spec_data, $job_data, $instance->job);
	}

	public static function schedule_map($vars, $job)
	{
		util::load_lib('delly');

		$instance = new all_data($vars);
		$instance->job = $job;
		$instance->create_map_jobs();
	}

	private function create_map_jobs()
	{
		$markets = $this->get_markets();
		
		// apply info filter at this point to limit the amount of data we're dealing with
		$this->set_filter_info_query($this->id_field);
		foreach ($markets as $market) {
			$ad_groups = db::select("
				select id
				from {$market}_objects.ad_group_{$this->obj_key}
				".$this->get_filter_id_where("ad_group_{$this->obj_key}", $market, 'where')."
			");
			foreach ($ad_groups as $ag_id) {
				spec_map_market_data::schedule(array(
					'parent_job_id' => $this->job->id,
					'market' => $market,
					'ad_group_id' => $ag_id
				), array('account_id' => $this->aid), $this->job);
			}
		}
	}

	public static function reduce($vars, $data, $job)
	{
		$instance = new all_data($vars);
		$instance->job = $job;
		$instance->data = $data;

		$instance->do_reduce();
		return $instance;
	}

	private function do_reduce()
	{
		// handles grouping keywords if needed
		$this->post_flatten_work();
		// if we had to group keywords, need to run filter again
		if ($this->do_group_keywords) {
			$this->filter_sort_slice_and_add_totals();
		}
		// don't need to run filter again unless we are grouping by keyword (which mapping cannot do)
		else {
			$this->sort_slice_and_add_totals();
		}
	}

	public static function load_filter_results($vars, $job_id)
	{
		$instance = new all_data($vars);
		$instance->do_load_filter_results($job_id);
		return $instance;
	}

	private function do_load_filter_results($job_id)
	{
		$results = reduced_market_data::get_all(array(
			'where' => "job_id = :jid",
			'data' => array("jid" => $job_id)
		));
		$this->data = array();
		foreach ($results as $r) {
			$this->data = array_merge($this->data, array(json_decode($r->data, true)));
		}
	}

	private function do_get_data()
	{
		$markets = $this->get_markets();
		
		// apply info filter at this point to limit the amount of data we're dealing with
		$this->set_filter_info_query($this->id_field);
		
		if ($this->account) {
			$g_mpc_convs = $this->account->google_mpc_tracking;
		}
		else {
			$g_mpc_convs = db::select_one("
				select google_mpc_tracking
				from eac.as_ppc
				where id = :aid
			", array('id' => $this->aid));
		}
		
		// if time period is "all", we don't need data date
		// saves a lot of time and effort for bidding and filtering
		$date_select = ($this->time_period == 'all') ? '' : 'data_date,';
		$date_where = $this->get_data_dates_where();
		$data_table = $this->get_data_table_type()."_data_{$this->obj_key}";

		$this->tmp_data = array();
		foreach ($markets as $market) {
			$q_market_id_filter = $this->get_filter_id_where($data_table, $market);
			// if any id filters are set but none are set for this market
			// don't get any data for this market
			if (empty($q_market_id_filter) && !empty($this->filter_ids)) {
				continue;
			}
			// because bing does not properly attribute delayed conversions, it
			// is possible for conversions to show up without any impressions
			$q_imps = ($market == 'g') ? "&& imps > 0" : '';
			$q_conv_field = ($market == 'g' && $g_mpc_convs) ? 'mpc_convs' : 'convs';
			
			$search_or_content_query = $this->get_search_or_content_query($market);
			$q = "
				select {$date_select} concat('{$market}_', {$this->id_field_select}) uid, sum(imps) imps, sum(clicks) clicks, sum(cost) cost, sum({$q_conv_field}) convs, sum(pos_sum) pos, sum(revenue) revenue
				from {$market}_objects.{$data_table}
				where
					{$date_where}
					{$q_imps}
					{$q_market_id_filter}
					".(($search_or_content_query) ? " && {$search_or_content_query} " : "")."
					".(($this->ext_query) ? " && {$this->ext_query} " : "")."
				group by {$date_select} uid
				order by data_date asc
			";
			
			// run query, aggregate by time period/id. special case for all
			if ($this->time_period == 'all') {
				$tmp_data = db::select($q, 'ASSOC', 'uid');
				$this->tmp_data[$market]['Summary'] = $tmp_data;
			}
			else {
				$tmp_data = db::select($q, 'ASSOC');
				$this->aggregate_by_time_period($this->tmp_data[$market], $tmp_data, $market);
			}
		}
		
		$this->combine_market_data();

		// flatten (make top level array non-associative so we can sort it), add info/other metrics
		$this->flatten_and_add_info();

		// see m.php api file for why we are doing this
		// if we filter before msn keyword check, we get to potentially run check 
		// on less ad groups, however, when we are done we would have to filter/sort/etc
		// all over again
		$this->msn_keyword_check();

		// sort and slice
		$this->filter_sort_slice_and_add_totals();
	}

	private function get_data_table_type()
	{
		// if we are looking at account or campaign level, use campaign_data table
		if ($this->detail == 'account' || $this->detail == 'campaign') {
			return 'campaign';
		}
		// extension data
		else if ($this->detail == 'extension') {
			return 'extension';
		}
		// anything more fine grained (ad groups, ads, keywords), use all_data table
		else {
			return 'all';
		}
	}
	
	private function init_totals_array()
	{
		return array_combine($this->base_data_fields, array_fill(0, count($this->base_data_fields), 0));
	}

	private function get_search_or_content_query($market)
	{
		if ($this->detail == 'keyword') {
			if ($this->search_only) {
				switch ($market) {
					case ('g'): return ("keyword_id != '".util::G_CONTENT_ID."'");
					case ('y'): return ("keyword_Id != '".util::Y_CONTENT_ID."'");
					case ('m'): return ("keyword_id not like 'C%'");
				}
			}
			else if ($this->content_only) {
				switch ($market) {
					case ('g'): return ("keyword_id = '".util::G_CONTENT_ID."'");
					case ('y'): return ("keyword_id = '".util::Y_CONTENT_ID."'");
					case ('m'): return ("keyword_id like 'C%'");
				}
			}
		}
		return '';
	}
	
	public function set_vars($vars = array())
	{
		if (!$vars) {
			return;
		}
		$this->is_vars_set = 1;
		
		// prioritize the order we set vars in
		$var_levels = array(
			array(
				'account', 'aid',
				'fields'
			),
		);
		
		$vars_set = array();
		for ($i = 0, $ci = count($var_levels); $i < $ci; ++$i) {
			$var_level = $var_levels[$i];
			foreach ($var_level as $k) {
				if (isset($vars[$k])) {
					$this->set_var($k, $vars[$k]);
					$vars_set[$k] = 1;
				}
			}
		}
		// run all the rest
		foreach ($vars as $k => $v) {
			if (!array_key_exists($k, $vars_set)) {
				$this->set_var($k, $v);
			}
		}
	}
	
	private function set_var($k, $v)
	{
		$func = 'set_'.$k;
		if (method_exists($this, $func)) {
			$this->$func($v);
		}
		else {
			$this->$k = $v;
		}
	}

	private function get_data_dates_where()
	{
		if (empty($this->custom_dates)) {
			return "data_date between '{$this->start_date}' and '{$this->end_date}'";
		}
		else {
			$q = '';
			foreach ($this->custom_dates as $dates) {
				if ($q) {
					$q .= " || ";
				}
				$q .= "data_date between '{$dates['start']}' and '{$dates['end']}'";
			}
			return "($q)";
		}
	}
	
	// convert market to array
	private function get_markets()
	{
		if ($this->market == 'all') return util::get_ppc_markets('SIMPLE', $this->account);
		return ((is_array($this->market)) ? $this->market : array($this->market));
	}
	
	private function set_table()
	{
		$this->table = "data_{$this->obj_key}";
	}
	
	public function set_do_update_cache($b)
	{
		$this->do_update_cache = $b;
		
		$this->cache_update_ids = array();
		$this->cache_update_parent_map = array();
		$this->cache_fresh_data = array();
	}
	
	private function field_depended_on($field)
	{
		switch ($field) {
			case('imps'):    $depends = array('ctr', 'pos'); break;
			case('clicks'):  $depends = array('ctr', 'cpc', 'conv_rate', 'mpc_conv_rate'); break;
			case('cost'):    $depends = array('cpc', 'cost_conv', 'mpc_cost_conv', 'roas'); break;
			case('convs'):   $depends = array('conv_rate', 'cost_conv', 'rev_ave'); break;
			case('revenue'): $depends = array('rev_click', 'rev_ave', 'roas'); break;
			// some other column
			default: return false;
		}
		$intersection = array_intersect(array_keys($this->fields), $depends);
		return (!empty($intersection));
	}

	public function set_fields($fields)
	{
		global $g_report_cols;

		$this->fields = $fields;

		// set the base data fields
		$this->base_data_fields = array();
		foreach (self::$all_base_data_fields as $base_data_field) {
			if (array_key_exists($base_data_field, $this->fields) || $this->field_depended_on($base_data_field)) {
				$this->base_data_fields[] = $base_data_field;
			}
		}
		// todo: col dependencies, only set base data col if another col selected depends on it
		if (empty($this->base_data_fields)) {
			$this->base_data_fields = self::$all_base_data_fields;
		}

		// init conv type fields
		$this->conv_type_fields = array();
		foreach ($this->fields as $field_name => $ph) {
			if (!empty($g_report_cols[$field_name]['is_conv_type'])) {
				$this->conv_type_fields[] = $field_name;
				// add to base data fields
				$this->base_data_fields[] = $field_name;
			}
		}
	}
	
	public function set_market($market)
	{
		$this->market = $market;
	}
	
	public function set_account($account)
	{
		$this->account = $account;
		if (empty($this->aid) && !empty($account->id)) {
			$this->set_aid($account->id);
		}
	}

	public function set_aid($aid)
	{
		$this->aid = $aid;
		if (empty($this->account)) {
			$this->account = new as_ppc(array('id' => $this->aid));
		}
		$this->obj_key = util::get_account_object_key($this->account);
	}

	public function set_ext_type($ext_type)
	{
		$this->ext_type = $ext_type;
		$this->ext_query = "type = '".db::escape($ext_type)."'";
	}

	public function set_time_period($time_period)
	{
		$this->time_period = $time_period;
	}
	
	public function set_time_period_weekly($time_period_weekly)
	{
		$this->time_period_weekly = $time_period_weekly;
	}
	
	public function set_time_period_monthly($time_period_monthly)
	{
		$this->time_period_monthly = $time_period_monthly;
	}
	
	public function set_detail($detail)
	{
		global $g_detail_cols;

		if (empty($g_detail_cols)) {
			util::init_report_cols();
		}
		$this->detail = $detail;
		if ($this->detail == 'market' || $this->detail == 'all') $this->detail = 'account';

		// set detail fields
		$this->detail_fields = array();
		foreach ($g_detail_cols as $field => $field_info) {
			if (array_key_exists($field, $this->fields)) {
				// can't combine keywords and ads
				if (empty($field_info['is_leaf']) || $field == $this->detail) {
					$this->detail_fields[] = $field;
				}
			}
		}
		
		$this->id_field = $detail.'_id';
		switch ($this->detail)
		{
			case ('account'):
				$this->id_field_select = "'{$this->aid}'";
				break;
			
			case ('campaign'):
			case ('ad_group'):
				$this->id_field_select = $detail.'_id';
				break;
			
			case ('ad'):
			case ('keyword'):
				$this->id_field_select = "concat(ad_group_id, '_', {$detail}_id)";
				break;

			// enums id depends on what detail columns are selected
			// enums do not have id suffix
			case ('device'):
			case ('extension'):
				if (in_array('ad_group', $this->detail_fields)) {
					$this->id_field_select = "concat(ad_group_id, '_', {$detail})";
				}
				else if (in_array('campaign', $this->detail_fields)) {
					$this->id_field_select = "concat(campaign_id, '_', {$detail})";
				}
				else {
					$this->id_field_select = $detail;
				}
				break;
		}
		$this->set_table();
	}

	private function is_enum_detail($detail = false)
	{
		if ($detail === false) {
			$detail = $this->detail;
		}
		switch ($detail) {
			case ('device'):
			case ('extension'):
				return true;
			default:
				return false;
		}
	}
	
	public function set_campaigns($campaigns)
	{
		if ($campaigns && $campaigns != 'null') {
			$this->campaigns = $campaigns;
			if ($this->id_field == 'account_id') {
				$prev_detail = $this->detail;
				$this->set_detail('campaign');
				
				// so we group by market!
				if ($this->time_period == 'all' || $prev_detail == 'all') {
					$this->id_field = 'account_id';
					$this->id_field_select = 'account_id';
				}
			}
		}
	}

	public function set_ad_groups($ad_groups)
	{
		if ($ad_groups && $ad_groups != 'null') {
			$this->ad_groups = $ad_groups;
		}
	}
	
	public function set_separate_totals($ph)
	{
		$this->separate_totals = 1;
	}
	
	public function set_sort($sort)
	{
		$this->sort = (!is_array($sort)) ? array($sort) : $sort;
	}
	
	public function set_keyword_style($style)
	{
		if ($this->detail != 'keyword') return;
		switch ($style)
		{
			case ('grouped'): $this->do_group_keywords = true; break;
			case ('match_type'): $this->do_show_match_type = true; break;
		}
	}
	
	// the info query can be used in multiple places, so we use the [table] placeholder
	// so that the table the query it is being run against can be inserted later

	// info query and id query should not contain the same checks
	private function set_filter_info_query($id_field = '')
	{
		$info_fields = array('account_id' => 0, 'campaign_id' => 1, 'ad_group_id' => 2, 'keyword_id' => 3);
		$this->filter_info_query = '';
		
		for ($i = 0, $count_i = count($this->filter); $i < $count_i; ++$i) {
			$ands = &$this->filter[$i];

			$q_ands = array();
			$q_and_ids = array();
			for ($j = 0, $count_j = count($ands); $j < $count_j; ++$j) {
				$and = &$ands[$j];
				$field = $and['col'];
				if ($this->is_info_filter_field($field)) {
					// if field is more detailed than the table, something is wrong
					if (!empty($id_field) && $info_fields[$field] > $info_fields[$id_field]) continue;
					
					switch ($field) {
						case ('headline'):
							$q_ands[] = $this->get_info_filter_sql_expression('text', $and);
							break;
						
						case ('description'):
							$q_ands[] = '('.$this->get_info_filter_sql_expression('desc_1', $and).' || '.$this->get_info_filter_sql_expression('desc_2', $and).')';
							break;
						
						default:
							if (!$this->is_info_filter_id_field($field)) {
								$q_ands[] = $this->get_info_filter_sql_expression($field, $and);
							}
							break;
					}
				}
			}
			$this->append_ands_to_ors($this->filter_info_query, $q_ands);
		}
		
		// indexed by market
		$this->filter_id_type = false;
		$this->filter_ids = array();
		// check most specific first
		$id_keys = array('ad_group', 'campaign');
		foreach ($id_keys as $key) {
			$obj_ids_key = "{$key}s";
			if (isset($this->$obj_ids_key) && $this->$obj_ids_key != 'null') {
				$ids = (is_array($this->$obj_ids_key)) ? $this->$obj_ids_key : explode(',', $this->$obj_ids_key);
				foreach ($ids as $tmp_id) {
					if (preg_match("/^(.)_(\d+)$/", $tmp_id, $matches)) {
						list($ph, $market, $id) = $matches;
					}
					else {
						// market not part of id but all is selected.. what market are ids for?
						if ($this->market == 'all') {
							return;
						}
						$market = $this->market;
						$id = $tmp_id;
					}
					$this->filter_ids[$market][] = $id;
				}
				// found ids, done
				$this->filter_id_type = $key;
				break;
			}
		}
	}

	private function get_filter_id_where($table, $market, $query_prefix = '&&')
	{
		if (!$this->filter_id_type) {
			return '';
		}
		else {
			if (empty($this->filter_ids[$market])) {
				return '';
			}
			else {
				return (($query_prefix) ? "{$query_prefix} " : "")."{$table}.{$this->filter_id_type}_id in ('".implode("','", $this->filter_ids[$market])."')";
			}
		}
	}
	
	private function in_query($x)
	{
		if (is_array($x)) {
			return "'".implode("','", $x)."'";
		}
		// string
		else {
			// already has quotes
			if (strpos($x, "','") !== false) {
				// also check for enclosing quotes
				return (strpos($x, "'") === 0) ? $x : "'{$x}'";
			}
			// no quotes
			else {
				return "'".str_replace(',', "','", $x)."'";
			}
		}
	}

	private function append_ands_to_ors(&$q, $ands)
	{
		if (!empty($ands))
		{
			if (!empty($q))
			{
				$q .= ' || ';
			}
			$q .= '('.implode(' && ', $ands).')';
		}
	}
	
	private function get_info_filter_sql_expression($field, $and)
	{
		$val = (array_key_exists('val_hidden', $and) && $and['val_hidden']) ? $and['val_hidden'] : $and['val'];
		switch ($and['cmp'])
		{
			case ('gt'):
				$cmp = '>';
				$val = "'".db::escape($val)."'";
				break;
				
			case ('lt'):
				$cmp = '<';
				$val = "'".db::escape($val)."'";
				break;
				
			case ('eq'):
				$cmp = '=';
				$val = "'".db::escape($val)."'";
				break;
				
			case ('ne'):
				$cmp = '<>';
				$val = "'".db::escape($val)."'";
				break;
				
			case ('contains'):
				$cmp = 'like';
				$val = "'%".db::escape($val)."%'";
				break;
				
			case ('not_contains'):
				$cmp = 'not like';
				$val = "'%".db::escape($val)."%'";
				break;
		}
		return "[table]{$field} {$cmp} {$val}";
	}
	
	private function is_info_filter_field($field)
	{
		return (
			$this->is_info_filter_id_field($field) ||
			$field == 'headline' || $field == 'description' || $field == 'disp_url' || $field == 'dest_url'
		);
	}
	
	private function is_info_filter_id_field($field)
	{
		return ($field == 'campaign_id' || $field == 'ad_group_id');
	}
	
	private function flatten_and_add_info()
	{
		util::update_job_details($this->job, "Flatten and add info");

		$this->data = array();
		
		$conv_type_data = $this->get_conv_type_data($this->data);
		$num_markets = count($this->tmp_data);
		$i_market = 0;
		foreach ($this->tmp_data as $market => $market_data) {
			++$i_market;
			$this->set_data_id_to_text_map($data_id_to_text, $market);
			$num_time_periods = count($market_data);
			$i_time_period = 0;
			foreach ($market_data as $time_period => $time_period_data) {
				++$i_time_period;

				if (empty($time_period_data)) {
					continue;
				}
				$data_count = count($time_period_data);
				$i_data = 0;
				foreach ($time_period_data as $data_id => $d) {
					$tmp = array();
					
					++$i_data;
					if ($i_data % 1000 == 0) {
						util::update_job_details($this->job, "Flatten market $i_market / $num_markets, period $i_time_period / $num_time_periods, data $i_data / $data_count", 1);
					}
					// check cache
					#if ($this->do_update_cache) $this->cache_check($market, $data_id);
					
					// see if we want the id
					if ($this->include_id) $tmp['uid'] = $data_id;
					
					// time period, unless all
					if ($this->time_period != 'all') {
						$tmp[$this->time_period] = $time_period;
					}
					
					// if we have an info query and we can't find detail info
					// we don't need this data, continue on
					if (!$this->add_detail_info($tmp, $data_id_to_text, $data_id) && $this->filter_info_query) {
						continue;
					}
					
					// check conv types
					foreach ($this->conv_type_fields as $conv_type_field) {
						$d[$conv_type_field] = (isset($conv_type_data[$time_period][$data_id][$conv_type_field])) ? $conv_type_data[$time_period][$data_id][$conv_type_field] : 0;
					}
					
					// totals
					if ($this->do_total && $this->total_type != 'Compare') {
						foreach ($this->totals as $k => $v) {
							if (array_key_exists($k, $d)) {
								$this->totals[$k] = $v + $d[$k];
							}
						}
					}
					if ($this->do_time_period_total) {
						if (empty($this->time_period_totals[$time_period])) {
							$this->time_period_totals[$time_period] = $this->init_totals_array();
						}
						foreach ($this->base_data_fields as $base_data_field) {
							if (array_key_exists($base_data_field, $d)) {
								$this->time_period_totals[$time_period][$base_data_field] += $v + $d[$base_data_field];
							}
						}
					}
					
					// add in data fields
					if ($this->do_compute_metrics) {
						$d['time_period'] = $time_period;
						self::compute_data_metrics($d, $this);
					}
					foreach ($this->fields as $field => $ph) {
						if (array_key_exists($field, $d)) {
							$tmp[$field] = $d[$field];
						}
					}
					
					// see if we have new info from cache
					#if ($this->do_update_cache) $this->cache_set_fresh($market, $data_id, $tmp);
					
					$this->data[] = $tmp;
				}
			}
		}
		$this->post_flatten_work();
	}

	private function post_flatten_work()
	{
		if (!empty($this->do_group_keywords)) {
			$this->group_by_keyword();
		}
		
		// compute other metrics for totals
		if ($this->do_total) {
			if ($this->total_type == 'Compare') {
				$this->compute_totals_row_comparison();
				$this->add_totals_header($this->totals, 'Change');
			}
			// default, normal totals
			else {
				self::compute_data_metrics($this->totals, $this);
				$this->add_totals_header($this->totals);
			}
		}
	}

	private function compute_totals_row_comparison()
	{
		$count = count($this->data);
		switch ($count) {
			case (0):
				break;

			case (1):
				$r1 = $this->data[0];
				$r2 = array_fill(0, count($r1), 0);
				break;

			default:
				$r1 = $this->data[$count - 2];
				$r2 = $this->data[$count - 1];
				break;
		}
		if (empty($r1)) {
			return false;
		}
		foreach ($r1 as $k => $v1) {
			$v2 = $r2[$k];
			if (is_numeric($v1) && is_numeric($v2)) {
				$v = ($v1 == 0) ? '-' : ($v2 - $v1) / $v1;
			}
			else {
				$v = '-';
			}
			$this->totals[$k] = $v;
		}
		return true;
	}
	
	private function get_conv_type_data()
	{
		if (empty($this->conv_type_fields)) {
			return array();
		}
		$cts = conv_type_count::get_all(array(
			'select' => array("conv_type_count" => array("d", "market", "concat(market, '_', {$this->id_field_select}) as data_id", "name", "amount")),
			'where' => "
				aid = :aid &&
				d between :start and :end &&
				name <> ''
			",
			'data' => array(
				"aid" => $this->aid,
				"start" => $this->start_date,
				"end" => $this->end_date
			)
		));
		$conv_type_data = array();
		if ($cts->count() > 0) {
			$conv_type_map = conv_type::get_client_market_map($this->aid);
			foreach ($cts as $ct) {
				$ct_name = (isset($conv_type_map[$ct->market][$ct->name])) ? $conv_type_map[$ct->market][$ct->name] : $ct->name;
				$conv_type_data[$this->get_data_time_period($ct->d)][$ct->data_id][$ct_name] += $ct->amount;
			}
		}
		return $conv_type_data;
	}
	
	private function add_enum_detail_to_map(&$data_id_to_text, &$map, $data_id, $detail_field)
	{
		// get next _ after market separator
		$enum_serparator = strpos($data_id, '_', 2);
		if ($enum_serparator !== false) {
			$map_key = substr($data_id, 0, $enum_serparator);
		}
		else {
			$map_key = $data_id;
		}
		if (isset($map[$map_key])) {
			$data_id_to_text[$detail_field][$data_id][$detail_field] = $map[$map_key][$detail_field];
			return true;
		}
		else {
			return false;
		}	
	}

	private function add_keyword_detail_to_map(&$data_id_to_text, &$map, $data_id, $detail_field, $market)
	{
		list($market, $ag_id, $kw_id) = explode('_', $data_id);
		$parent_info = db::select_row("
			select c.text as campaign, a.text as ad_group
			from {$market}_objects.campaign_{$this->obj_key} c, {$market}_objects.ad_group_{$this->obj_key} a
			where
				a.id = :ag_id &&
				a.campaign_id = c.id
		", array('ag_id' => $ag_id), 'ASSOC');
		if (empty($parent_info)) {
			return false;
		}
		// set empty keyword
		$parent_info['keyword'] = '';
		// also will set in our local map var since it is reference
		foreach ($parent_info as $parent_type => $parent_text) {
			$data_id_to_text[$detail_field][$data_id][$parent_type] = $parent_text;
		}	
		return true;
	}

	private function add_detail_info(&$d, &$data_id_to_text, $data_id)
	{
		// add detail (campaign, market, ad group, keyword, etc)
		foreach ($this->detail_fields as $detail_field) {
			$map = &$data_id_to_text[$detail_field];
			switch ($detail_field) {
				case ('market'):
					$d['market'] = $map;
					break;
				
				// for enums, value is part of data id
				case ('device'):
				case ('extension'):
					$parts = explode('_', $data_id);
					// just market and enum val
					if (count($parts) < 3) {
						$val = $parts[1];
					}
					else {
						// enum can have underscores
						// if part[1] doesn't look like market id of some sort
						$enum_val_start_index = (is_numeric($parts[1])) ? 2 : 1;
						$val = implode('_', array_slice($parts, $enum_val_start_index));
					}
					$d[$detail_field] = $val;
					break;

				case ('campaign'):
				case ('ad_group'):
				case ('ad'):
				case ('keyword'):
					if (!array_key_exists($data_id, $map)) {
						// enum is part of data id, but not map, so we will always
						// have to add enum details to map as we go along
						if ($this->is_enum_detail()) {
							if (!$this->add_enum_detail_to_map($data_id_to_text, $map, $data_id, $detail_field)) {
								return false;
							}
						}
						// some keywords don't return data in keyword reports
						// so we are missing them
						// if detail level is keyword, see if we can find ad group and campaign
						else if ($this->detail == 'keyword') {
							if (!$this->add_keyword_detail_to_map($data_id_to_text, $map, $data_id, $detail_field, $market)) {
								return false;
							}
						}
						else {
							return false;
						}
					}
					$info = $map[$data_id];
					foreach ($info as $k => &$v) {
						$d[$k] = $v;
					}
					break;
			}
		}
		return true;
	}
	
	private function group_by_keyword()
	{
		$grouped = array();
		foreach ($this->data as $d) {
			$kw_text = $d['keyword'];
			// gotta change pos back to a sum rather than average
			$d['pos'] *= $d['imps'];
			if (!array_key_exists($kw_text, $grouped)) {
				$grouped[$kw_text] = $d;
			}
			else {
				foreach ($this->base_data_fields as $base_data_field) {
					$grouped[$kw_text][$base_data_field] += $d[$base_data_field];
				}
			}
		}
		// now make array flat again
		$this->data = array();
		foreach ($grouped as $kw_text => $kw_data) {
			self::compute_data_metrics($kw_data, $this);
			
			$tmp = array();
			foreach ($this->fields as $field => $ph) {
				if (array_key_exists($field, $kw_data))
					$tmp[$field] = $kw_data[$field];
			}
			
			$this->data[] = $tmp;
		}
	}
	
	public static function compute_data_metrics(&$d, $mixed_opts)
	{
		$opt_keys = array('do_calc_percents', 'conv_type_fields', 'fields', 'is_map', 'do_group_keywords', 'start_date', 'end_date', 'time_period', 'is_totals');
		if (!is_array($mixed_opts)) {
			$opts = array();
			foreach ($opt_keys as $key) {
				$opts[$key] = $mixed_opts->$key;
			}
		}
		else {
			$opts = $mixed_opts;
		}

		//rn: added to ease queries
		if(isset($d['pos_sum'])){
			$d['pos'] = $d['pos_sum'];
		}

		$imps = $d['imps'];
		$clicks = $d['clicks'];
		$convs = $d['convs'];
		$cost = $d['cost'];
		$pos = $d['pos'];
		$revenue = $d['revenue'];
		$mpc_convs = isset($d['mpc_convs']) ? $d['mpc_convs'] : 0;
		
		$ctr       = ($imps > 0)   ? (($clicks / $imps) * ((!empty($opts['do_calc_percents'])) ? 100 : 1)) : 0;
		$cpc       = ($clicks > 0) ? ($cost / $clicks)          : 0;

		// if we are mapping and grouping, leave calculating
		// pos to reduce, as it overwrites itself
		if (!($opts['is_map'] && $opts['do_group_keywords'])) {
			$pos       = ($imps > 0)   ? ($pos / $imps)             : 0;
		}
		
		$conv_rate = ($clicks > 0) ? (($convs / $clicks) * ((!empty($opts['do_calc_percents'])) ? 100 : 1)) : 0;
		$cost_conv = ($convs > 0)  ? ($cost / $convs)           : 0;
		
		$mpc_conv_rate = ($clicks > 0) ? (($mpc_convs / $clicks) * ((!empty($opts['do_calc_percents'])) ? 100 : 1)) : 0;
		$mpc_cost_conv = ($mpc_convs > 0)  ? ($cost / $mpc_convs)           : 0;
		
		$d['ctr'] = $ctr;
		$d['cpc'] = $cpc;
		$d['pos'] = $pos;
		
		$d['conv_rate'] = $conv_rate;
		$d['cost_conv'] = $cost_conv;
		
		$d['mpc_conv_rate'] = $mpc_conv_rate;
		$d['mpc_cost_conv'] = $mpc_cost_conv;
		
		$d['rev_click'] = ($clicks > 0) ? ($revenue / $clicks) : 0;
		$d['rev_ave'] = ($convs > 0) ? ($revenue / $convs)     : 0;
		$d['roas'] = ($cost > 0) ? ($revenue / $cost)          : 0;


		if ($d['time_period']=='Summary'){
			$start_date = $opts['start_date'];
			$end_date = $opts['end_date'];
		} 
		else {
			$start_date = $d['time_period'];
			switch($opts['time_period']){
				case 'daily':
					$end_date = $start_date;
					break;
				case 'weekly':
					$end_date = date(util::DATE, strtotime($start_date.' +7 days'));
					break;
				case 'monthly':
					$end_date = date(util::DATE, strtotime($start_date.' +1 month'));
					break;
				case 'quarterly':
					$end_date = date(util::DATE, strtotime($start_date.' +3 months'));
					break;
			}
		}

		if ($opts['start_date']>$start_date){
			$start_date = $opts['start_date'];
		}

		if ($opts['end_date']<$end_date){
			$end_date = $opts['end_date'];
		}

		$d['num_days'] = (strtotime($end_date) - strtotime($start_date))/86400;
		$d['conv_day'] = ($d['num_days']>0) ? $convs/$d['num_days'] : 0;
		$d['rev_day'] = ($d['num_days']>0) ? $revenue/$d['num_days'] : 0;
		$d['click_day'] = ($d['num_days']>0) ? $clicks/$d['num_days'] : 0;

		if (!empty($opts['conv_type_fields'])) {
			foreach ($opts['conv_type_fields'] as $conv_type) {
				$num_conv_type = $d[$conv_type];
				if (isset($opts['fields'][$conv_type.'_rate'])) {
					$d[$conv_type.'_rate'] = ($clicks > 0) ? (($num_conv_type / $clicks) * ((!empty($opts['do_calc_percents'])) ? 100 : 1)) : 0;
				}
				if (isset($opts['fields']['cost_'.$conv_type])) {
					$d['cost_'.$conv_type] = ($num_conv_type > 0) ? ($cost / $num_conv_type) : 0;
				}
			}
		}
		
		// php bug: http://bugs.php.net/bug.php?id=43053
		// "solution" - if we see scientific notation, we are close enough to zero to call it zero
		// only check for values this is known to happen for
		if (strpos($d['rev_click'], 'E-') !== false) $d['rev_click'] = 0;
	}
	
	private function add_totals_header(&$totals, $total_header = 'Totals')
	{
		$did_add_totals = false;
		// if there is a time period, use that column for "Totals"
		if ($this->time_period != 'all') {
			$totals[$this->time_period] = $total_header;
			$did_add_totals = true;
		}
		// loop over detail fields,
		// add "Totals" if we haven't
		// add dash otherwise
		foreach ($this->detail_fields as $detail_field) {
			if ($detail_field == 'ad') continue;
			
			if ($did_add_totals) {
				$totals[$detail_field] = '-';
			}
			else {
				$totals[$detail_field] = $total_header;
				$did_add_totals = true;
			}
		}
		
		// columns that don't make sense for totals
		$nonsensical = array('pos', 'bid', 'cbid', 'headline', 'desc_1', 'desc_2', 'description', 'disp_url', 'dest_url');
		// these are actually ok if we are comparing rather than totalling
		$ok_for_compare = array('pos');
		foreach ($nonsensical as $field) {
			if (array_key_exists($field, $this->fields) && !($this->total_type == 'Compare' && in_array($field, $ok_for_compare))) {
				$totals[$field] = '-';
			}
		}
	}
	
	// totals has to be in the same order as everything else
	private function set_totals_order(&$totals)
	{
		$totals_in_order = array();
		foreach ($this->fields as $field => $ph)
		{
			if ($field != 'ad')
			{
				$totals_in_order[$field] = $totals[$field];
			}
		}
		$totals = $totals_in_order;
	}
	
	private function set_filter_totals()
	{
		if (!$this->do_total) {
			return;
		}
		for ($i = 0, $count = count($this->data); $i < $count; ++$i) {
			$d = &$this->data[$i];
			
			foreach ($this->filter_totals as $k => $v) {
				if (array_key_exists($k, $d)) {
					$this->filter_totals[$k] = $v + $d[$k];
				}
			}
		}
		// compute other metrics for totals
		if ($this->do_compute_metrics) self::compute_data_metrics($this->filter_totals, $this);
		
		$this->add_totals_header($this->filter_totals, 'Filter');
		$this->set_totals_order($this->filter_totals);
	}
	
	// todo: device is currently the only enum, but could be others in the future
	// generalize these
	private function enum_to_campaign_map(&$map, $market)
	{
		if (in_array('ad_group', $this->detail_fields)) {
			$this->ad_group_to_campaign_map($map, $market);
		}
		else {
			$this->campaign_to_campaign_map($map, $market);
		}
	}
	
	private function enum_to_ad_group_map(&$map, $market)
	{
		$this->ad_group_to_ad_group_map($map, $market);
	}
	
	// nothing to do
	private function enum_to_enum_map(&$map, $market)
	{
		$map = true;
	}
	
	private function keyword_to_campaign_map(&$map, $market)
	{
		$this->data_id_to_parent_text($map, $market, 'keyword', 'campaign', "concat(detail_table.ad_group_id, '_', detail_table.id)");
	}
	
	private function keyword_to_ad_group_map(&$map, $market)
	{
		$this->data_id_to_parent_text($map, $market, 'keyword', 'ad_group', "concat(detail_table.ad_group_id, '_', detail_table.id)");
	}
	
	private function keyword_to_keyword_map(&$map, $market)
	{
		$this->data_id_to_text($map, $market, 'keyword');
	}
	
	private function ad_to_campaign_map(&$map, $market)
	{
		$this->data_id_to_parent_text($map, $market, 'ad', 'campaign', "concat(detail_table.ad_group_id, '_', detail_table.id)");
	}
	
	private function ad_to_ad_group_map(&$map, $market)
	{
		$this->data_id_to_parent_text($map, $market, 'ad', 'ad_group', "concat(detail_table.ad_group_id, '_', detail_table.id)");
	}
	
	private function ad_to_ad_map(&$map, $market)
	{
		$this->data_id_to_text($map, $market, 'ad');
	}
	
	private function ad_group_to_campaign_map(&$map, $market)
	{
		$this->data_id_to_parent_text($map, $market, 'ad_group', 'campaign', "detail_table.id");
	}
	
	private function ad_group_to_ad_group_map(&$map, $market)
	{
		$this->data_id_to_text($map, $market, 'ad_group');
	}
	
	private function campaign_to_campaign_map(&$map, $market)
	{
		$this->data_id_to_text($map, $market, 'campaign');
	}
	
	private function data_id_to_parent_text(&$map, $market, $detail, $parent, $id_select)
	{
		$map = db::select("
			select concat('{$market}_', $id_select) uid, parent_table.text $parent
			from
				{$market}_objects.{$parent}_{$this->obj_key} parent_table,
				{$market}_objects.{$detail}_{$this->obj_key} detail_table
			where
				parent_table.id = detail_table.{$parent}_id
				".((empty($this->filter_info_query)) ? '' : (' && '.str_replace('[table]', 'detail_table.', $this->filter_info_query)))."
				".$this->get_filter_id_where('detail_table', $market)."
		", 'ASSOC', 'uid');
	}
	
	// if detail column is the detail we're getting data for
	private function data_id_to_text(&$map, $market, $detail)
	{
		// nothing to do
		if ($this->is_enum_detail($detail)) {
			return;
		}
		if ($detail == 'ad') {
			$other_selects = array();
			if ($this->fields['headline']) $other_selects[] = "text headline";
			if ($this->fields['description']) $other_selects[] = "if (desc_2 = '', desc_1, concat(desc_1, ' ', desc_2)) description";
			if ($this->fields['disp_url']) $other_selects[] = "disp_url";
			if ($this->fields['dest_url']) $other_selects[] = "dest_url";
			$other_select = implode(', ', $other_selects);
			
			// ad is not actually a column, we use other select to build ad map
			$text_select = "";
		}
		else {
			// if we're getting keywords and grouping, we also need the type
			$type_select = ($detail == 'keyword' && $this->do_show_match_type) ? ', type' : '';
			$other_select = '';
			if ($this->include_bid) {
				switch ($detail) {
					case ('ad_group'): $other_select = ', status, max_cpc bid, max_content_cpc cbid'; break;
					case ('keyword'):  $other_select = ', status, max_cpc bid'; break;
				}
			}
			$text_select = "text {$detail}";
		}

		$q_where = array();
		if (!empty($this->filter_info_query)) {
			$q_where[] = str_replace('[table]', 'detail_table.', $this->filter_info_query);
		}
		if (!empty($this->filter_id_query) && $this->filter_id_query != $this->filter_info_query) {
			$q_where[] = $this->get_filter_id_where('detail_table', $market, '');
		}

		// if we are looking at same level, convert to id
		foreach ($q_where as $k => $q) {
			$id_test = "detail_table.{$this->detail}_id";
			// position 0 is opening (
			if (strpos($q, $id_test) === 1) {
				$q_where[$k] = str_replace($id_test, "detail_table.id", $q);
			}
		}

		// enums do not have tables of their own
		// so use target $detail table
		if ($this->is_enum_detail()) {
			$table_base = $detail;
			$id_select = 'id';
		}
		else {
			$table_base = $this->detail;
			// eg replace keyword_id with id
			$id_select = str_replace("{$this->detail}_id", 'id', $this->id_field_select);
		}
		$map = db::select("
			select concat('{$market}_', $id_select) as uid, {$text_select}{$type_select}{$other_select}
			from {$market}_objects.{$table_base}_{$this->obj_key} detail_table
			".(empty($q_where) ? '' : "where ".implode(" && ", $q_where))."
		", 'ASSOC', 'uid');
		
		// if we're showing match type, go through and update keyword text
		if (!empty($type_select)) {
			foreach ($map as $k => &$k_info) {
				switch ($k_info['type']) {
					case ('Phrase'): $map[$k]['keyword'] = '"'.$k_info['keyword'].'"'; break;
					case ('Exact'): $map[$k]['keyword'] = '['.$k_info['keyword'].']'; break;
				}
				unset($k_info['type']);
			}
		}
	}
	
	private function set_data_id_to_text_map(&$map, $market)
	{
		$map = array();
		foreach ($this->detail_fields as $detail_field) {
			// there is no "market" info table
			if ($detail_field == 'market') continue;
			
			// no such thing as keyword_to_ad
			if ($this->detail == 'keyword' && $detail_field == 'ad') continue;
			
			// enums all have same mapping functions
			$to = $detail_field;
			if ($this->is_enum_detail($this->detail)) {
				$from = 'enum';
				// don't want to have to bother creating new map
				// function for each new enum that comes along
				// as there is nothing to do anyway
				if ($detail_field == $this->detail) {
					$to = 'enum';
				}
			}
			else {
				$from = $this->detail;
			}
			$func = $from.'_to_'.$to.'_map';
			$this->$func($map[$detail_field], $market);
		}
		
		// add in market text
		$markets_text = util::get_ppc_markets('ASSOC', $this->account);
		if (array_key_exists('m', $markets_text)) {
			$markets_text['m'] = 'Bing';
		}
		if ($this->aggregate_markets) {
			$markets_text['*'] = 'Aggregate';
		}
		$map['market'] = $markets_text[$market];
	}
	
	private function aggregate_by_time_period(&$time_period_data, &$tmp_data, $market)
	{
		util::update_job_details($this->job, "Aggregate for $market");

		// aggregate by time period
		$time_period_data = array();

		// if using custom dates, init time period data with custom date buckets
		// so time period data is ordered same as custom dates
		if (!empty($this->custom_dates)) {
			foreach ($this->custom_dates as $dates) {
				$time_period = util::get_date_time_period($this->time_period, $dates['start'], array(
					'include_day_of_month' => true,
					'custom_dates' => $this->custom_dates
				));
				$time_period_data[$time_period] = array();
			}
		}

		// -2 for data date and id, which we remove from array to aggregate
		$col_count = ((isset($tmp_data[0])) ? count($tmp_data[0]) : 0) - 2;
		for ($i = 0, $count = count($tmp_data); $i < $count; ++$i)
		{
			if ($i % 1000 == 0) {
				util::update_job_details($this->job, "Aggregate $i / $count", 1);
			}
			$d = $tmp_data[$i];
			$data_date = $d['data_date'];
			$data_id = $d['uid'];
			$time_period = $this->get_data_time_period($data_date);
			if ($time_period === false) {
				continue;
			}
			
			if (!isset($time_period_data[$time_period])) {
				$time_period_data[$time_period] = array();
			}
			if (!isset($time_period_data[$time_period][$data_id])) {
				$time_period_data[$time_period][$data_id] = array_combine($this->base_data_fields, array_fill(0, count($this->base_data_fields), 0));
			}
			
			// what was this for?
			#if (!@array_key_exists($data_id, $this->unique_data_ids[$market])) $this->unique_data_ids[$market][$data_id] = 1;
			
			foreach ($this->base_data_fields as $base_data_field)
			{
				if (!isset($time_period_data[$time_period][$data_id][$base_data_field])) {
					$time_period_data[$time_period][$data_id][$base_data_field] = $d[$base_data_field];
				}
				else {
					$time_period_data[$time_period][$data_id][$base_data_field] += $d[$base_data_field];
				}
			}
		}
	}
	
	private function get_data_time_period($data_date)
	{
		return util::get_date_time_period($this->time_period, $data_date, array(
			'include_day_of_month' => true,
			'month_start' => (isset($this->time_period_monthly)) ? $this->time_period_monthly : 1,
			'week_start' => (isset($this->time_period_weekly)) ? $this->time_period_weekly : 1,
			'start_date' => (isset($this->start_date)) ? $this->start_date : null,
			'custom_dates' => (!empty($this->custom_dates)) ? $this->custom_dates : false
		));
	}
	
	private function filter_cmp_gt($val, $compare)           { return ($val > $compare); }
	private function filter_cmp_lt($val, $compare)           { return ($val < $compare); }
	private function filter_cmp_eq($val, $compare)           { return ($val == $compare); }
	private function filter_cmp_ne($val, $compare)           { return ($val != $compare); }
	private function filter_cmp_contains($val, $compare)     { return (stripos($val, $compare) !== false); }
	private function filter_cmp_not_contains($val, $compare) { return (stripos($val, $compare) === false); }

	private function apply_filter()
	{
		// filter
		if (!is_array($this->filter) || count($this->filter) == 0) {
			return;
		}
		// maps cannot apply filters if we are grouping keywords
		// as another map may have keywords which belong to same group
		// resulting in applying filter to incomplete data
		if ($this->is_map && $this->do_group_keywords) {
			return;
		}
		util::update_job_details($this->job, "Filter", 1);
		
		$temp_data = array();
		for ($i = 0, $count_data = count($this->data); $i < $count_data; ++$i)
		{
			$d = &$this->data[$i];
			for ($j = 0, $count_ors = count($this->filter); $j < $count_ors; ++$j)
			{
				$ands = &$this->filter[$j];
				for ($k = 0, $count_ands = count($ands); $k < $count_ands; ++$k)
				{
					$and = &$ands[$k];
					$field = $and['col'];
					
					// we've already applied info filters, so only check for non-info filters here
					if ($this->is_info_filter_field($field)) continue;
					
					$cmp = $and['cmp'];
					$val = (array_key_exists('val_hidden', $and) && $and['val_hidden'] != '') ? $and['val_hidden'] : $and['val'];
					
					// test and short circuit if failed
					$cmp_func = 'filter_cmp_'.$cmp;
					if (!$this->$cmp_func($d[$field], $val)) break;
				}
				// see if we made it through all the and's
				// if we did, we're done, short circuit the rest of the or's
				if ($k == $count_ands) break;
			}
			// if we made it all the way though, that means we failed all or's, data is no good
			// so if it's less, we ARE good, add it to array
			if ($j < $count_ors)
			{
				$temp_data[] = $d;
				if ($this->filter_limit && count($temp_data) == $this->filter_limit)
				{
					if (class_exists('feedback'))
					{
						feedback::add_error_msg('Reached the filter limit of '.$this->filter_limit);
					}
					break;
				}
			}
		}
		$this->data = $temp_data;
	}
	
	public function filter_sort_slice_and_add_totals()
	{
		$this->apply_filter();
		$this->sort_slice_and_add_totals();
	}

	private function sort_slice_and_add_totals()
	{
		util::update_job_details($this->job, "Sort, Slice and Total");

		// sort
		// if time period is NOT all, we always sort by time period,
		// so only sort here if time period IS all
		if ($this->time_period == 'all') usort($this->data, array('all_data', 'cmp'));
		
		// slice
		if (!empty($this->limit) && $this->limit < count($this->data)) {
			$is_slice = true;
			$this->data = array_slice($this->data, 0, $this->limit);
		}
		
		// totals
		// if we filtered or sliced, set filter totals
		if (!empty($is_filter) || !empty($is_slice)) {
			$this->set_filter_totals($this->data);
			if (!$this->separate_totals) {
				$this->data[] = $this->filter_totals;
			}
		}
		if ($this->do_total) {
			$this->set_totals_order($this->totals);
			if (!(!empty($is_filter) || !empty($is_slice)) && !$this->separate_totals) {
				$this->data[] = $this->totals;
			}
		}
		if ($this->do_time_period_total && $this->time_period_totals) {
			ksort($this->time_period_totals);
			if ($this->do_compute_metrics) {
				foreach ($this->time_period_totals as $time_period => &$tp_totals) {
					self::compute_data_metrics($tp_totals, $this);
				}
			}
		}
	}
	
	public function cmp(&$x, &$y)
	{
		if (empty($this->sort)) {
			return 0;
		}
		foreach ($this->sort as $key) {
			$x_val = $x[$key];
			$y_val = $y[$key];
			if ($x_val == $y_val) continue;
			if ($this->sort_dir == 'asc') return ($x_val > $y_val);
			return ($x_val < $y_val);
		}
		return 0;
	}
	
	private function cache_check($market, $data_id)
	{
		$cache_update_func = 'update_'.$this->detail.'s';
		if ($this->detail == 'ad_group') {
			$parent_field = "campaign";
			$id_field = "concat('{$market}_', ad_group)";
			$info_fields = "max_cpc, max_content_cpc, status";
		}
		else if ($this->detail == 'keyword') {
			$parent_field = "ad_group";
			$id_field = "concat('{$market}_', ad_group, '_', keyword)";
			$info_fields = "max_cpc, status";
		}
		// don't need to do campaign or market
		// cache refresh ad status????
		else {
			return;
		}
		
		if (!@array_key_exists($data_id, $this->cache_update_parent_map)) {
			$tmp_result = db::select("
				select {$parent_field}, $id_field
				from {$market}_objects.{$this->detail}_{$this->obj_key}
			", 'NUM', 1);
			
			if (empty($this->cache_update_parent_map)) {
				$this->cache_update_parent_map = $tmp_result;
			}
			else {
				$this->cache_update_parent_map = array_merge($this->cache_update_parent_map, $tmp_result);
			}
		}
		$parent_id = $this->cache_update_parent_map[$data_id];
		if (!empty($parent_id) && !@array_key_exists($parent_id, $this->cache_update_ids[$market])) {
			$this->cache_update_ids[$market][$parent_id] = 1;
			list($did_update_cache) = data_cache::$cache_update_func($this->aid, $market, $parent_id);
			
			if ($did_update_cache) {
				$tmp_result = db::select("
					select {$id_field}, {$info_fields}
					from {$market}_objects.{$this->detail}_{$this->obj_key}
					where {$parent_field} = '$parent_id'
				", 'NUM', 0);
				
				if (empty($this->cache_fresh_data)) {
					$this->cache_fresh_data = $tmp_result;
				}
				else {
					$this->cache_fresh_data = array_merge($this->cache_fresh_data, $tmp_result);
				}
			}
		}
	}
	
	private function cache_set_fresh($market, $data_id, &$tmp)
	{
		if (@array_key_exists($data_id, $this->cache_fresh_data)) {
			if ($this->detail == 'ad_group') {
				list($tmp['bid'], $tmp['cbid'], $tmp['status']) = $this->cache_fresh_data[$data_id];
			}
			else if ($this->detail == 'keyword') {
				list($tmp['bid'], $tmp['status']) = $this->cache_fresh_data[$data_id];
			}
		}
	}
	
	private function combine_market_data()
	{
		if (!$this->aggregate_markets) {
			return;
		}
		
		foreach ($this->tmp_data as $market => $market_data) {
			foreach ($market_data as $time_period => $time_period_data) {
				foreach ($time_period_data as $data_id => $d) {
					foreach ($d as $k => $v) {
						$this->tmp_data['*'][$time_period]['*'][$k] += $v;
					}
				}
			}
			unset($this->tmp_data[$market]);
		}
	}

	private function msn_keyword_check()
	{
		if ($this->detail != 'keyword' || !in_array('m', $this->get_markets()) || empty($this->aid) || $this->include_msn_deleted) {
			return false;
		}
		util::update_job_details($this->job, "MSN Keyword Check");

		$tmp_data = array();
		$msn_keywords = array();
		$msn_ad_groups = array();
		for ($i = 0, $ci = count($this->data); $i < $ci; ++$i) {
			$d = &$this->data[$i];
			list($market, $ag_id, $kw_id) = explode('_', $d['uid']);
			// could be another market if we are looking at more than 1
			if ($market !== 'm') {
				$tmp_data[] = $d;
			}
			else {
				$msn_keywords[] = $d;
				$msn_ad_groups[$ag_id] = 1;
			}
		}

		// no msn keywords, nothing to do
		if (empty($msn_keywords)) {
			return false;
		}

		/*
		 * update our cache of kw info if needed
		 */
		$last_ds_refresh = db::select_one("
			select j.started
			from delly.job j
			left join eppctwo.ppc_data_source_refresh r on
				r.id = j.fid &&
				r.market = 'm'
			where
				j.type = 'PPC DATA SOURCE REFRESH' &&
				j.account_id = '".db::escape($this->aid)."' &&
				j.started >= '".date(util::DATE_TIME, time() - util::DATA_CACHE_EXPIRE)."'
			order by j.started desc
		");
		foreach ($msn_ad_groups as $ag_id => $ph) {
			if (!$last_ds_refresh) {
				$do_force_update = true;
			}
			else {
				$kw_info_mod_time = db::select_one("
					select kw_info_mod_time
					from m_objects.ad_group_{$this->obj_key}
					where id = '$ag_id'
				");
				$do_force_update = ($last_ds_refresh > $kw_info_mod_time);
			}
			data_cache::update_keywords($this->aid, 'm', $ag_id, array('do_set_client' => true, 'force_update' => $do_force_update));
		}
		// from most restrictive to least
		$i_to_match = array('Exact', 'Phrase', 'Broad');
		$match_to_i = array_flip($i_to_match);
		// get our cache fresh keywords
		$updated_keywords = db::select("
			select ad_group_id, id, text, type as type_index, type as type, status
			from m_objects.keyword_{$this->obj_key}
			where ad_group_id in ('".implode("','", array_keys($msn_ad_groups))."')
		", 'ASSOC', array('ad_group'), array('text'), 'type_index');

		/*
		 * loop over all our keywords, add to tmp data
		 */
		$kw_to_new_index = array();
		$num_fixed = 0;
		for ($i = 0, $ci = count($msn_keywords); $i < $ci; ++$i) {
			$d = &$msn_keywords[$i];
			list($market, $ag_id, $kw_id) = explode('_', $d['uid']);
			list($text, $match_type) = util::get_keyword_text_and_match_type($d['keyword']);

			// keyword not around anymore, do not include it. should not happen
			if (empty($updated_keywords[$ag_id][$text])) {
				// nothing to do
			}
			// keyword text exists
			else {
				$best_kw = false;
				$kws = $updated_keywords[$ag_id][$text];
				// not in our updated database with this match type?
				if (empty($kws[$match_type])) {
					$starti = 0;
				}
				// exists with match type, check status
				else {
					$kw_info = $kws[$match_type];
					// deleted, find active match type
					if ($kw_info['status'] == 'Deleted') {
						$starti = $match_to_i[$match_type];
					}
					// keyword is legit
					else {
						$starti = false;
						$best_kw = $kw_info;
					}
				}
				// we need to look for a non-deleted match type
				if ($starti !== false) {
					for ($j = $starti, $cj = count($i_to_match); $j < $cj; ++$j) {
						$test_match_type = $i_to_match[$j];
						if (isset($kws[$test_match_type]) && $kws[$test_match_type]['status'] !== 'Deleted') {
							$num_fixed++;
							$best_kw = $kws[$test_match_type];
							break;
						}
					}
					// should never happen?!
					if ($j == $cj) {
					}
				}
				// should always happen?!
				if ($best_kw !== false) {
					// calculating position depends on itself, have to reverse any calcs we previously did
					if ($this->do_compute_metrics) {
						$d['pos'] = $d['pos'] * $d['imps'];
					}
					$best_type = $best_kw['type'];
					// we've already added this match type to our results, aggregate
					if (isset($kw_to_new_index[$ag_id][$text][$best_type])) {
						$kw_index = $kw_to_new_index[$ag_id][$text][$best_type];
						foreach ($this->base_data_fields as $base_data_field) {
							$tmp_data[$kw_index][$base_data_field] += $d[$base_data_field];
						}
					}
					// first time we are seeing this text/match type
					// conditionally update to reflect best match type, note index, and push to tmp data
					else {
						// best kw wasn't the one we started with:
						// update id, keyword text, and status to reflect the actual keyword that we found
						$best_full_id = 'm_'.$ag_id.'_'.$best_kw['keyword'];
						if ($best_full_id != $d['uid']) {
							$d['uid'] = $best_full_id;
							$d['status'] = $best_kw['status'];
							$d['keyword'] = util::get_keyword_display($text, $best_type);
						}
						$kw_to_new_index[$ag_id][$text][$best_type] = count($tmp_data);
						$tmp_data[] = $d;
					}
				}
			}
		}
		if ($num_fixed == 0) {
			return false;
		}
		else {
			$this->data = $tmp_data;
			// recompute metrics for msn keywords
			if ($this->do_compute_metrics) {
				for ($i = 0, $ci = count($this->data); $i < $ci; ++$i) {
					if ($this->data[$i]['uid'][0] == 'm') {
						self::compute_data_metrics($this->data[$i], $this);
					}
				}
			}
			return true;
		}
	}
}




?>