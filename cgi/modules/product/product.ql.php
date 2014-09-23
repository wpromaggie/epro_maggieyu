<?php

define('SPEND_MAX_RESULTS_PER_PAGE', 50);
define('SPEND_DEFAULT_PLAN', 'Starter_149');

define('SPEND_DEFAULT_BUDGET', 100);

class mod_product_ql extends mod_product
{
	private $cols;
	
	private static $grouped_plans = array(
		'Starter / Plat / PlatQL' => array('Plat', 'PlatQL', 'Starter')
	);
	
	private static $obsolete_plans = array(
		'Basic',
		'Bo1',
		'Bo2',
		'Bo3',
		'Bo4',
		'GoldQL',
		'Plus',
		'LAL',
		'LALgold',
		'LALsilver',
		'LALplatinum',
		'test'
	);
	
	public function pre_output()
	{
		util::load_lib('ql', 'cache');
		parent::pre_output();
	}
	
	protected function no_url()
	{
		return (!$this->url || is_null($this->url->plan));
	}
	
	public function head()
	{
		echo '<h1>QL Spend</h1>';
	}
	
	public function display_spend()
	{
		$this->init_cols();
		
		$zero_imps = (array_key_exists('zero_imps', $_POST));
		$data_cols = array('imps', 'clicks', 'cost');
		
		$budget = util::unempty($_POST['budget'], SPEND_DEFAULT_BUDGET);
		$bill_day = $_POST['bill_day'];
		list($next_bill_date_start, $next_bill_date_end) = util::list_assoc($_POST, 'next_bill_date_start', 'next_bill_date_end');
		
		// check status unless we have an id
		$where_status = "(status in ('Active', 'NonRenewing'))";
		
		$where = array();
		if ($_POST['a0'] == 'url_search') {
			// URL searches can be any plan
			$where[] = "url like '%".db::escape($_POST['url_search'])."%'";
		}
		else if (is_numeric($bill_day)) {
			$where[] = "bill_day = $bill_day";
		}
		else if (array_key_exists('url_id', $_GET)) {
			$where[] = "account.id = '".db::escape($_GET['url_id'])."'";
		}
		else if ($next_bill_date_start) {
			$where[] = "next_bill_date between '$next_bill_date_start' and '$next_bill_date_end'";
		}
		else {
			$where[] = "account.id in (".cache::read('ql-spend-'.$budget.'-aid-map.sql.cache').")";

			$signup_cutoff = $_POST['signup_cutoff'];
			if ($signup_cutoff == '0000-00-00') $signup_cutoff = '';
			if (!empty($signup_cutoff)) {
				$where[] = "signup_date >= '$signup_cutoff'";
			}
		}
		$accounts = ap_ql::get_all(array(
			"select" => array("account" => array("id", "url", "plan", "prepay_paid_months", "prepay_free_months", "signup_dt", "bill_day", "de_activation_date")),
			"where" => implode(" && ", $where)
		));
		
		// build our metrics query
		$q_data_select = '';
		foreach ($data_cols as $col) {
			$q_data_select .= ", {$col}";
		}
		foreach (ql_lib::$markets as $market) {
			foreach ($data_cols as $col) {
				$q_data_select .= ", {$market}_{$col}";
			}
		}
		// get spend metrics
		$url_spend_metrics = db::select("
			select account_id, days_to_date dtd, days_remaining dr, days_in_month, spend_to_date std, spend_remaining sr, spend_prev_month spm, daily_to_date dstd, daily_remaining dsr{$q_data_select}
			from eppctwo.ql_spend
		", 'ASSOC', 'account_id');
		
		// for calc'ing #months
		$now = time();
		
		// build our array of data
		// loop over clients, merge client info and current spend data
		$a = array();
		foreach ($accounts as $account) {
			$account_array = $account->to_array();
			$aid = $account_array['id'];
			$d = (array_key_exists($aid, $url_spend_metrics)) ? array_merge($account_array, $url_spend_metrics[$aid]) : $account_array;
			$d['id'] = $aid;
			$d['months'] = floor(($now - strtotime($d['signup_dt'])) / 2628000);
			$d['mo'] = ($d['prepay_paid_months']) ? $d['prepay_paid_months'] : 1;
			$d['signup'] = substr($d['signup_dt'], 0, 10);
			$d['de_act'] = $d['de_activation_date'];
			
			$a[] = $d;
		}
		
		$sort = util::unempty($_POST['sort'], 'cost');
		$sort_dir = util::unempty($_POST['sort_dir'], 'desc');
		util::sort2d($a, $sort, $sort_dir);
		
		$ml_budgets = $this->ml_budget_select($budget);
		$ml_slice = $this->format_table_slice($start_index, $end_index, count($a));
		$ml_headers = $this->format_table_headers();
		$ml_body = $this->format_table_body($a, $start_index, $end_index, $sort);
		?>

		
		<div>
			<p>* Overall and market spend data are from yesterday</p>
		</div>
		<table cellpadding=0 cellspacing=0>
			<thead>
				<tr><td class="r" colspan="100"><?php echo $ml_budgets.$ml_slice; ?></td></tr>
				<?php echo $ml_headers; ?>
			</thead>
			<tbody id="spend_tbody">
				<?php echo $ml_body; ?>
			</tbody>
		</table>
		<input type="hidden" name="sort" id="sort" value="<?php echo $sort; ?>" />
		<input type="hidden" name="sort_dir" id="sort_dir" value="<?php echo $sort_dir; ?>" />
		<?php
		cgi::to_js('g_date_options', array(
			array('clear' , 'Clear'),
			array('yesterday' , 'Yesterday'),
			array('this_week', 'This Week'),
			array('this_month', 'This Month'),
			array('last_week', 'Last Week'),
			array('last_month', 'Last Month'),
			array('last_7', 'Last 7 Days'),
			array('last_30', 'Last 30 Days')
		));
	}
	
	private function init_cols()
	{
		// already inited
		if ($this->cols) return;
		
		$this->cols = array();
		$this->cols['url']        = array('', 'URL' , 'format_url');
		$this->cols['plan']       = array('', 'Plan', '');
		$this->cols['months']     = array('', 'Mo', '');
		
		if ($_POST['zero_imps'])
		{
			$this->cols['signup']   = array('', 'Signup', '');
		}
		
		$this->cols['dtd']      = array('Metrics', 'DTD', '');
		$this->cols['dr']       = array('Metrics', 'DR', '');
		$this->cols['std']      = array('Metrics', 'STD', 'format_dollars');
		$this->cols['sr']       = array('Metrics', 'SR', 'format_dollars');
		$this->cols['dstd']     = array('Metrics', 'DSTD', 'format_dollars');
		$this->cols['dsr']      = array('Metrics', 'DSR', 'format_dollars');
		$this->cols['spm']      = array('Metrics', 'SPM', 'format_dollars');
		
		$this->cols['clicks']   = array('Overall', 'Clicks', '');
		$this->cols['imps']     = array('Overall', 'Imps', '');
		$this->cols['cost']     = array('Overall', 'Cost', 'format_dollars');
		
		$this->cols['g_clicks'] = array('Google', 'Clicks', '');
		$this->cols['g_imps']   = array('Google', 'Imps', '');
		$this->cols['g_cost']   = array('Google', 'Cost', 'format_dollars');
		
		$this->cols['m_clicks'] = array('MSN', 'Clicks', '');
		$this->cols['m_imps']   = array('MSN', 'Imps', '');
		$this->cols['m_cost']   = array('MSN', 'Cost', 'format_dollars');
	}
	
	private function format_table_body(&$data, $start_index, $end_index, $sort)
	{
		$ml = '';
		for ($i = $start_index; $i < $end_index; ++$i)
		{
			$d = &$data[$i];
			$ml_cells = '';
			foreach ($this->cols as $col => $col_info)
			{
				list($col_group, $col_display, $col_output_func) = $col_info;
				$v = $d[$col];
				if (!empty($col_output_func))
				{
					if      (method_exists($this, $col_output_func))     $v = $this->$col_output_func($d);
					else if (method_exists('util', $col_output_func))    $v = util::$col_output_func($v);
					else if (method_exists('ql_lib', $col_output_func))  $v = ql_lib::$col_output_func($v);
					else if (method_exists('sbs_lib', $col_output_func)) $v = sbs_lib::$col_output_func($v);
					else                                                 $v = $col_output_func($v);
				}
				$ml_class = ($col == $sort) ? ' class="sort_cell"' : '';
				$ml_cells .= '<td'.$ml_class.'>'.$v.'</td>';
			}
			
			$row_class = (util::empty_date($d['de_act'])) ? '' : ' class="cancelled"';
			$ml .= '
				<tr url_id="'.$d['id'].'" g_ag_id="'.$d['g_ag_id'].'" m_ag_id="'.$d['m_ag_id'].'"'.$row_class.'>
					<td class="multi_button">'.($i + 1).'</td>
					'.$ml_cells.'
				</tr>
			';
		}
		return $ml;
	}

	private function format_url(&$d)
	{
		return '<a href="'.cgi::href('account/product/ql/spend?aid='.$d['id']).'" target="_blank">'.sbs_lib::shorten_url($d['url']).'</a>';
	}

	private function ml_budget_select($selected_budget)
	{
		return cgi::html_select('budget', cache::read('ql-spend-budget-options.json.cache', 'json'), $selected_budget);
	}
	
	private function format_table_slice(&$start_index, &$end_index, $count)
	{
		$start_index = $_POST['data_start'];
		if (empty($start_index)) $start_index = 0;
		$index_options = array();
		for ($i = 0; $i < $count; $i += SPEND_MAX_RESULTS_PER_PAGE)
		{
			$slice_start = $i + 1;
			$slice_end = min($i + SPEND_MAX_RESULTS_PER_PAGE, $count);
			$index_options[] = array($i, "$slice_start - $slice_end");
		}
		$end_index = min($start_index + SPEND_MAX_RESULTS_PER_PAGE, $count);
		
		return cgi::html_select('data_start', $index_options, $start_index);
	}
	
	private function format_table_headers()
	{
		/*
		 * col groups
		 */
		// loop over cols first to get counts
		$col_groups = array();
		foreach ($this->cols as $col => $col_info)
		{
			list($col_group, $col_display, $col_output_func) = $col_info;
			if (!array_key_exists($col_group, $col_groups)) $col_groups[$col_group] = 1;
			else $col_groups[$col_group]++;
		}
		$ml_col_groups = '<tr id="spend_headers_groups"><th></th>';
		foreach ($col_groups as $col_group => $count)
		{
			$ml_col_groups .= '<th colspan="'.$count.'">'.$col_group.'</th>';
		}
		$ml_col_groups .= "</tr>\n";
		
		/*
		 * cols
		 */
		$ml_cols = '<tr id="spend_headers_row"><th></th>';
		$prev_col_group = '';
		foreach ($this->cols as $col => $col_info)
		{
			list($col_group, $col_display, $col_output_func) = $col_info;
			$ml_cols .= '<th'.(($col_group != $prev_col_group) ? ' class="border_header"' : '').'><a href="" col="'.$col.'">'.$col_display.'</a></th>';
			
			$prev_col_group = $col_group;
		}
		$ml_cols .= "</tr>\n";
		
		return ($ml_col_groups.$ml_cols);
	}
}

?>