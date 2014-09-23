<?php
util::load_lib('ppc', 'data_cache');

require_once(\epro\WPROPHP_PATH.'excel/excel.php');

// more power
ini_set('memory_limit', '2048M');

class worker_ppc_report extends worker
{
	const TOTALS_AT_TOP_CUTOFF = 10;
	const PCHART_IMAGE_NUM_ROWS = 23;
	const LOGO_COL_WIDTH = 3;
	const SHEET_TITLE_ROW = 3;
	const PIXELS_PER_ROW = 15;
	const DEFAULT_ROW_HEIGHT = 15;
	const HEADER_ROW_HEIGHT = 20;
	const DATA_COL_FIXED_WIDTH = 13.86;

	// fixed: manager must provide a logo that fits
	const CL_ROW = 2;
	const CL_LOGO_NUM_ROWS = 5;

	// we auto size text cols, other cols are fixed width
	private static $text_cols = array('market', 'campaign', 'ad_group', 'keyword');

	public static $report_cols;
	
	// a sheet object representing chart data
	public $chart_data;
	
	private $aid, $sheets, $ad_markets_updated;

	// track table types on each sheet
	private $table_types;
	
	function __construct($job)
	{
		global $g_report_cols;
		parent::__construct($job);
		$this->aid = $this->job->account_id;
		$this->account = new as_ppc(array('id' => $this->aid), array(
			'select' => array(
				"account" => array("client_id", "name as account_name"),
				"contacts" => array("name as contacts_name"),
				"client_media" => array("data", "w", "h")
			),
			'left_join' => array(
				"client_media" => "client_media.account_id = :id && client_media.account_id = account.id",
				"client_media_use" => "client_media_use.use = 'PPC Report Logo' && client_media_use.client_media_id = client_media.id"
			),
			'left_join_many' => array(
				"contacts" => "account.client_id = contacts.client_id"
			),
			'de_alias' => true
		));
		
		$this->sheets = ppc_report_sheet::get_all(array(
			'select' => array(
				"ppc_report_sheet" => array("id", "name", "position as spos"),
				"ppc_report_table" => array("position as tpos", "definition")
			),
			'join_many' => array(
				"ppc_report_table" => "ppc_report_sheet.id = ppc_report_table.sheet_id"
			),
			'where' => "ppc_report_sheet.report_id = :rep_id",
			'data' => array("rep_id" => $this->job->fid),
			'order_by' => "spos asc, tpos asc"
		));
		$this->ad_markets_updated = array();
		
		util::init_report_cols($this->aid, array('do_include_extensions' => true));
		self::$report_cols = $g_report_cols;
		$this->chart_data = null;
		$this->sheet_test = (class_exists('cli') && !empty(cli::$args['s'])) ? cli::$args['s'] : false;
	}
	
	public function run()
	{
		if (isset($this->account->client_media->data)) {
			$is_cl_logo = true;
			$logo_path = $this->rowwrite_client_logo_tmp_file($this->account->client_media->data);
		}
		else {
			$is_cl_logo = false;
		}
		$sheet_headers = $this->get_sheet_header($is_cl_logo);
		$this->refresh_ads();
		
		if (class_exists('dbg') && dbg::is_on() && array_key_exists('dbg_browser_output', $_POST)) db::dbg();
		
		// init xls info
		// first sheet for chart data, hidden
		$xls_info = array();
		
		// to hold any data for charts
		$this->chart_data = new sheet();
		
		for ($i = 0, $ci = $this->sheets->count(); $i < $ci; ++$i) {
			$sheet = $this->sheets->a[$i];
			if ($this->sheet_test && $sheet->name != $this->sheet_test) {
				continue;
			}
			$s = new sheet(array('col_pad' => 8));
			
			if ($is_cl_logo) {
				$s->images[] = array(
					'name' => 'Client Logo',
					'path' => $logo_path,
					'where' => PHPExcel_Cell::stringFromColumnIndex($s->col_start).self::CL_ROW
				);
				// move up row count based on height of client logo
				$s->row_count = self::CL_ROW + self::CL_LOGO_NUM_ROWS;
			}
			$sheet_header_row_start = $s->row_count;

			// sheet header data
			foreach ($sheet_headers as $header_row)
			{
				$s->add_row($header_row);
			}
			// get some more spacing if no cl logo
			if (!$is_cl_logo) {
				$s->row_count += 2;
			}
			$sheet_header_row_end = $s->row_count - 1;
			// sheet header formatting
			$s->formatting[] = array(
				'where' => array(array($sheet_header_row_start, $sheet_header_row_end), array(1, 1)),
				'what' => array(
					'font' => 'bold',
					'alignment' => 'left'
				)
			);
			
			$max_text_cols = 0;
			$max_cols = 0;
			for ($j = 0, $cj = $sheet->ppc_report_table->count(); $j < $cj; ++$j) {
				// update job status
				$this->job->update_details('Sheet '.($i + 1).' of '.$ci.', Table '.($j + 1).' of '.$cj);

				$table_info = json_decode($sheet->ppc_report_table->a[$j]->definition, true);
				$table_info['aid'] = $this->aid;
				
				// backwards compatible
				if (!array_key_exists('display_type', $table_info) || !$table_info['display_type'] || $table_info['display_type'] == 'null')
				{
					$table_info['display_type'] = 'table';
				}
				$this->table_types[$sheet->name][] = $table_info['display_type'];

				// add time period to front if it is not all
				if ($table_info['time_period'] != 'all')
				{
					$table_info['cols'] = array_merge(array($table_info['time_period'] => 1), $table_info['cols']);
				}
				$table_info['num_cols'] = count($table_info['cols']);
				
				// not actually columns, but need them to be passed to data gate
				if (array_key_exists('ad', $table_info['cols']))
				{
					$table_info['num_cols']--;
				}

				// check the number of text cols
				$num_text_cols = count(array_intersect(self::$text_cols, array_keys($table_info['cols'])));
				if ($num_text_cols > $max_text_cols) {
					$max_text_cols = $num_text_cols;
				}
				// max cols
				$num_cols = count($table_info['cols']);
				if ($num_cols > $max_cols) {
					$max_cols = $num_cols;
				}

				if ($this->dbg || (class_exists('dbg') && dbg::is_on() && array_key_exists('dbg_show_queries', $_POST))) {
					e($table_info);
					e($table_cols);
				}
				/*
				 * data
				 */
				// table data
				$table_info['fields'] = $table_info['cols']; // fields is key used by data
				$table_info['job'] = $this->job;
				$table_info['set_separate_totals'] = 1;
				$table_info['include_id'] = false;
				$table_info['include_bid'] = false;
				$table_info['do_calc_percents'] = false;

				$data = $this->job->sub_task(array('all_data', 'get_data'), array($table_info));
				
				$table_info['data'] = &$data->data;
				$table_info['data_totals'] = &$data->totals;
				
				// add some space before each table
				$s->row_count++;
				
				// table header
				$table_row_start = $s->row_count;
				$s->add_row($this->get_table_header($table_info));
				
				$formatter = format_data_for_excel::get_formatter($table_info['display_type'], $this->chart_data);
				$formatter->format_data($s, $table_info);
				
				$table_row_data_end = $s->row_count - 1;
				
				$s->formatting[] = array(
					'where' => array(array($table_row_start, $table_row_start), array($s->col_start, $s->col_start+1)),
					'what' => array(
						'font' => array(
							'italic' => 1,
							'bold' => 1
						),
						'alignment' => 'left'
					)
				);
				$s->fixed_height[] = array($table_row_start, worker_ppc_report::HEADER_ROW_HEIGHT);

				$s->formatting[] = array(
					'where' => array(array($table_row_start, $table_row_start), array($table_info['num_cols'], $table_info['num_cols'])),
					'what' => array(
						'font' => array(
							'italic' => 1,
							'bold' => 1,
							'color' => 'DD0000'
						),
						'alignment' => 'right'
					)
				);

				// ignore table header when computing width of columns
				$s->auto_width_ignore_cells[] = array($s->col_start, $table_row_start);

				// first table in sheet
				if ($j === 0) {
					$first_table_num_cols = $table_info['num_cols'];
					// add our logo, right aligned with topmost table
					$s->images[] = array(
						'name' => 'Wpromote Logo',
						'path' => \epro\CLI_PATH.'logo.jpg',
						'where' => PHPExcel_Cell::stringFromColumnIndex($first_table_num_cols - self::LOGO_COL_WIDTH + $s->col_start).'2'
					);

					// title cell, estimate center, excel will do a better job later
					$title_cell_col = intval(floor($first_table_num_cols / 2));
					$row = (isset($s->data[self::SHEET_TITLE_ROW])) ? $s->data[self::SHEET_TITLE_ROW] : array();
					$row[$title_cell_col] = strtoupper($sheet->name);
					$s->data[self::SHEET_TITLE_ROW] = $row;
					// ignore for computing widths
					$s->auto_width_ignore_cells[] = array($title_cell_col, self::SHEET_TITLE_ROW);
					// have excel center it for us
					$s->center_cells[] = array($title_cell_col, self::SHEET_TITLE_ROW);
					$s->formatting[] = array(
						'where' => array(array(self::SHEET_TITLE_ROW, self::SHEET_TITLE_ROW), array($title_cell_col, $title_cell_col)),
						'what' => array(
							'font' => array(
								'bold' => 1,
								'size' => 12
							),
						)
					);
				}
				
			}

			// set first column (index 0) to width 3 for a little padding along the left
			$fixed_widths = array_fill($max_text_cols + 1, $max_cols - $max_text_cols, self::DATA_COL_FIXED_WIDTH);
			$fixed_widths[0] = 3;
			ksort($fixed_widths);
			// todo: just use s instead of converting s to array
			// add sheet info to xl info
			$xls_info[$sheet->name] = array(
				'data' => $s->data,
				'formatting' => $s->formatting,
				'images' => $s->images,
				'charts' => $s->charts,
				'default_formatting' => array(
					'font' => array(
						'name' => 'Arial',
						'size' => 10
					),
					'alignment' => array(
						'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
						'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
					),
					'fill' => array(
						'type'  => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array('rgb' => 'FFFFFF')
					)
				),
				'default_row_height' => self::DEFAULT_ROW_HEIGHT,
				'fixed_height' => $s->fixed_height,
				// other columns are auto fitted
				'fixed_width' => $fixed_widths,
				'auto_width_ignore_cells' => $s->auto_width_ignore_cells,
				'center_cells' => $s->center_cells
			);
		}
		
		// see if we actually have any chart data
		if ($this->chart_data->data)
		{
			$xls_info['ChartData'] = array(
				'data' => $this->chart_data->data,
				'props' => array(
					'SheetState' => PHPExcel_Worksheet::SHEETSTATE_HIDDEN
				)
			);
		}
		
		if (class_exists('dbg') && dbg::is_on() && array_key_exists('dbg_browser_output', $_POST)) {
			e($xls_info);
			return;
		}
		if (class_exists('dbg') && dbg::is_on() && array_key_exists('dbg_show_queries', $_POST)) {
			return;
		}
		// can't have slashes in file name, messes with directory level
		$excel_base_name = str_replace(array('/', '\\'), '_', $this->account->name.'_'.date(util::DATE));
		if (empty($this->job)) {
			$report_path = '';
		}
		else {
			$excel_base_name = $this->job->id.'_'.$excel_base_name;
			$report_path = \epro\REPORTS_PATH.'ppc_report/';
		}
		
		$this->job->update_details('Writing to Excel');
		$xls = new excel();
		$xls->write($xls_info, $excel_base_name, 'Wpromote Performance Report', array(
			'report_path' => $report_path,
			'auto_width' => array($this, 'do_auto_width_sheet'),
			// add empty values to the right of our data so the sheet background color extends out
			'pad_right' => 35
		));
		
		// append extension to base name
		$filename = $excel_base_name.'.xlsx';
		$full_path = $report_path.$filename;
		chmod($full_path, 0644);
		$this->job->update_details($filename);
		db::update(
			"eppctwo.reports",
			array('last_run' => date(util::DATE_TIME)),
			"id = :fid",
			array('fid' => $this->job->fid)
		);
	}
	
	private function rowwrite_client_logo_tmp_file(&$logo_data)
	{
		$path = tempnam(sys_get_temp_dir(), 'rep_cl_logo');
		file_put_contents($path, $logo_data);
		return $path;
	}

	public function do_auto_width_sheet($sheet_name, $sheet_info)
	{
		// might not be set for hidden sheets we add on ourselves
		if (!isset($this->table_types[$sheet_name])) {
			return false;
		}
		// we have table types
		else {
			// if none of them are 'table', only graphics, we don't have anything to auto-width
			foreach ($this->table_types[$sheet_name] as $table_type) {
				if ($table_type == 'table') {
					return true;
				}
			}
			return false;
		}
	}

	private function get_sheet_header($is_cl_logo)
	{
		$headers = array();

		if (!$is_cl_logo) {
			$headers[] = array('Client: '.$this->account->name);
		}
		if ($this->account->contacts->count() > 0) {
			$headers[] = array('Prepared For: '.$this->account->contacts->a[0]->name);
		}
		$headers[] = array('Date: '.date(util::US_DATE));
		
		return $headers;
	}
	
	private function get_market_long_description($market)
	{
		switch ($market)
		{
			case ('g'): return 'Google AdWords';
			case ('m'): return 'Bing Ads';
		}
		// should not be here
		return 'Summary';
	}
	
	private function get_table_header(&$table_info)
	{
		// date range
		// doesn't make sense if custom date range selected
		if (!empty($table_info['custom_dates'])) {
			$min_date = $table_info['custom_dates'][0]['start'];
			$max_date = $table_info['custom_dates'][0]['end'];
			foreach ($table_info['custom_dates'] as $date_range){
				if ($date_range['start']<$min_date){
					$min_date = $date_range['start'];
				}
				if ($date_range['end']>$max_date){
					$max_date = $date_range['end'];
				}
			}
			$min_date = date(util::US_DATE, strtotime($min_date));
			$max_date = date(util::US_DATE, strtotime($max_date));		}
		else {
			//$date_range = date(util::US_DATE, strtotime($table_info['start_date'])).' - '.date(util::US_DATE, strtotime($table_info['end_date'])).' -- ';
			$min_date = date(util::US_DATE, strtotime($table_info['start_date']));
			$max_date = date(util::US_DATE, strtotime($table_info['end_date']));
		}

		// user entered table meta desc
		// custom title
		if (!empty($table_info['meta_desc'])) {
			$desc = $table_info['meta_desc'];
		} else {
			// market
			$markets = util::get_ppc_markets('ASSOC');
			$tmp = $table_info['market'];
			$market = ($tmp == 'all') ? 'Summary' : $this->get_market_long_description($tmp);
			
			// time period
			$tmp = $table_info['time_period'];
			$time_period = ($tmp == 'all') ? 'Summary' : util::display_text($tmp);
			
			// detail
			$tmp = $table_info['detail'];
			$detail = ($tmp == 'all') ? 'Summary' : util::display_text($tmp);
			if ($tmp == 'extension') {
				$detail = $table_info['ext_type'].' '.$detail;
			}

			$desc = 'Market: '.$market.', '.
			'Time Period: '.$time_period.', '.
			'Detail: '.$detail;
		}
		
		
		return array_merge(
			array($min_date, $max_date), 
			array_fill(0, $table_info['num_cols']-3, ""),
			array($desc)
		);
	}
	
	// check all tables in report
	// get max and min dates for each market
	// refresh if needed
	private function refresh_ads()
	{
		$ad_refresh_dates = array();
		for ($i = 0, $ci = $this->sheets->count(); $i < $ci; ++$i) {
			$sheet = $this->sheets->a[$i];
			if ($this->sheet_test && $sheet->name != $this->sheet_test) {
				continue;
			}
			for ($j = 0, $cj = $sheet->ppc_report_table->count(); $j < $cj; ++$j) {
				$table_info = json_decode($sheet->ppc_report_table->a[$j]->definition, true);
				$table_info['aid'] = $this->aid;
				if ($table_info['detail'] == 'ad') {
					$market = $table_info['market'];
					
					if ($market == 'all') {
						$markets = util::get_ppc_markets('SIMPLE', $this->account);
					}
					else {
						$markets = array($market);
					}
					foreach ($markets as $market) {
						if (!$ad_refresh_dates[$market]) {
							$ad_refresh_dates[$market] = array('start_date' => '9999-99-99', 'end_date' => '0000-00-00');
						}
						if ($table_info['start_date'] < $ad_refresh_dates[$market]['start_date']) {
							$ad_refresh_dates[$market]['start_date'] = $table_info['start_date'];
						}
						if ($table_info['end_date'] > $ad_refresh_dates[$market]['end_date']) {
							$ad_refresh_dates[$market]['end_date'] = $table_info['end_date'];
						}
					}
				}
			}
		}
		
		$today = date(util::DATE);
		$uid = false;
		
		foreach ($ad_refresh_dates as $market => $market_dates) {
			list($start_date, $end_date) = util::list_assoc($market_dates, 'start_date', 'end_date');
			// see if we have already run an ad refresh today that covers this data
			$ad_refresh_id = db::select_one("
				select id
				from eppctwo.ad_refresh_log
				where
					account_id = :aid &&
					market = :market &&
					process_dt between :process_start_date and :process_end_date &&
					start_date >= :target_start_date &&
					end_date <= :target_end_date
			", array(
				'aid' => $this->aid,
				'market' => $market,
				'process_start_date' => \epro\TODAY.' 00:00:00',
				'process_end_date' => \epro\TODAY.' 23:59:59',
				'target_start_date' => $start_date,
				'target_end_date' => $end_date
			));
			if (!$ad_refresh_id) {
				if (!$uid) {
					$uid = $this->job->user_id;
				}
				db::insert("eppctwo.ad_refresh_log", array(
					'account_id' => $this->aid,
					'user_id' => $uid,
					'market' => $market,
					'process_dt' => date(util::DATE_TIME),
					'start_date' => $start_date,
					'end_date' => $end_date
				));
				$this->job->update_details('Refreshing Ad Info ('.$market.')');
				$this->get_ad_info($market, $start_date, $end_date);
			}
		}
	}
	
	private function get_ad_info($market, $start_date, $end_date)
	{
		require_once(\epro\WPROPHP_PATH.'apis/apis.php');
		require_once(\epro\WPROPHP_PATH.'file_iterator.php');
		
		$market_accounts = db::select("
			select distinct account
			from eppctwo.data_sources
			where account_id = '{$this->aid}' && market = '$market'
		");
		foreach ($market_accounts as $ac_id)
		{
			$api = base_market::get_api($market, $ac_id);
			$api->run_ad_structure_report($this->aid, array(
				'start_date' => $start_date,
				'end_date' => $end_date
			));
		}
	}
}

// simple struct for sheet info
class sheet
{
	public $data, $formatting, $images, $row_count, $col_pad, $col_start, $auto_width_ignore_cells, $fixed_height;
	
	public function __construct($opts = array())
	{
		util::set_opt_defaults($opts, array(
			'col_pad' => false
		));
		$this->data = array();
		$this->formatting = array();
		$this->images = array();
		$this->charts = array();
		$this->row_count = 1;
		$this->col_pad = $opts['col_pad'];
		// col indexed from 0
		$this->col_start = ($this->col_pad) ? 1 : 0;
		$this->auto_width_ignore_cells = array();
		$this->fixed_height = array();
	}
	
	public function add_row($r)
	{
		if ($this->col_pad) {
			array_unshift($r, '');
		}
		$this->data[$this->row_count++] = $r;
	}
}


abstract class format_data_for_excel 
{
	public $chart_data;
	
	protected function __construct(&$chart_data)
	{
		$this->chart_data = $chart_data;
	}
	
	public static function get_formatter($type, &$chart_data)
	{
		$class_name = 'format_data_as_'.$type;
		if (!class_exists($class_name))
		{
			return false;
		}
		return new $class_name($chart_data);
	}
	
	/*
	 * classes that inherit from format_data_for_excel must define format_data
	 */
	abstract public function format_data(&$s, &$table_info);
}


/*
 * chart types are defined in /wprophp/excel/PHPExcel/PHPExcel/Chart/DataSeries.php
 */

abstract class format_data_simple_chart extends format_data_for_excel
{
	protected $chart_type;
	
	protected $chart_height, $chart_width;
	
	abstract protected function set_chart_params();
	
	protected function set_params()
	{
		$this->chart_height = 15;
		$this->chart_width = 16;
		
		$this->set_chart_params();
	}
	
	public function format_data(&$s, &$table_info)
	{
		$this->set_params();
		
		// row 1 is the column names,
		// slice at 1 becase row 1, col 1 is empty
		$chart_cols = array_keys($table_info['cols']);
		$chart_cols = array_slice($chart_cols, 1);
		$chart_cols = array_map(array('util', 'display_text'), $chart_cols);
		array_unshift($chart_cols, '');
		$num_cols = count($chart_cols);
		
		$start_row = $this->chart_data->row_count;
		$this->chart_data->add_row($chart_cols);
		foreach ($table_info['data'] as $d)
		{
			$this->chart_data->add_row($d);
		}
		$end_row = $this->chart_data->row_count - 1;
		
		$s->charts[] = array(
			'data_sheet_name' => 'ChartData',
			'data_top_left' => array(0, $start_row),
			'data_bottom_right' => array($num_cols - 1, $end_row),
			'chart_start_row' => $s->row_count + 1,
			'height' => $this->chart_height,
			'width' => $this->chart_width,
			'y_axis_label' => $table_info['y_axis_label'],
			'type' => $this->chart_type
		);
		$s->row_count += $this->chart_height;
	}	
}

class format_data_as_line_chart extends format_data_simple_chart 
{
	public function set_chart_params()
	{
		$this->chart_type = PHPExcel_Chart_DataSeries::TYPE_LINECHART;
	}
}

class format_data_as_pie_chart extends format_data_simple_chart 
{
	public function set_chart_params()
	{
		$this->chart_type = PHPExcel_Chart_DataSeries::TYPE_AREACHART;
	}
}

class format_data_as_bar_chart extends format_data_simple_chart 
{
	public function set_chart_params()
	{
		$this->chart_type = PHPExcel_Chart_DataSeries::TYPE_BARCHART;
	}
}

class format_data_as_table extends format_data_for_excel
{
	private function get_col_extension_header($table_info)
	{
		switch ($table_info['ext_type']) {
			case ('Call'): return 'Phone Number';
			default:       return $table_info['ext_type'];
		}
	}

	public function format_data(&$s, &$table_info)  
	{
		// "ad" is not actually a column
		if ($table_info['detail'] == 'ad') {
			unset($table_info['cols']['ad']);
		}
		// table headers
		$headers = array();
		foreach ($table_info['cols'] as $col => $ph) {
			if ($col == 'extension') {
				$header = $this->get_col_extension_header($table_info);
			}
			else if (isset(worker_ppc_report::$report_cols[$col])) {
				$col_info = worker_ppc_report::$report_cols[$col];
				$header = $col_info['display'];
			}
			else {
				$header = util::display_text($col);
			}
			$header = strtoupper($header);
			$header = preg_replace('/\bAVE\b/', 'AVG', $header);
			$headers[] = $header;
		}
		// header formatting
		$s->formatting[] = array(
			'where' => array(array($s->row_count, $s->row_count), array($s->col_start, $table_info['num_cols'] - 1 + $s->col_start)),
			'what' => array(
				'alignment' => 'center',
				'fill' => '287DA5',
				'font' => array(
					'bold' => 1,
					'color' => 'FFFFFF'
				)
			)
		);
		$s->fixed_height[] = array($s->row_count, worker_ppc_report::HEADER_ROW_HEIGHT);
		$table_row_start = $s->row_count;
		$s->add_row($headers);
		
		// if there are "a lot" of rows, also show totals at the beginning
		$do_show_totals_at_beginning = (count($table_info['data']) > worker_ppc_report::TOTALS_AT_TOP_CUTOFF);
		if ($do_show_totals_at_beginning) {
			$s->add_row($table_info['data_totals']);
		}
		$table_row_data_start = $s->row_count;
		foreach ($table_info['data'] as $d) {
			$s->add_row($d);
		}
		
		// add on totals at the end
		$s->add_row($table_info['data_totals']);
		$table_row_data_end = $s->row_count - 1;
				
		// right border, check for top totals for where to start
		$right_border_start_row = $table_row_data_start - (($do_show_totals_at_beginning) ? 1 : 0);
		
		// if we're showing top totals, add top border and bold top totals
		$totals_formatting = array(
			'font' => 'bold',
			'fill' => 'E5E5E5'
		);
		if ($do_show_totals_at_beginning) {
			// bold totals (first row)
			$s->formatting[] = array(
				'where' => array(array($table_row_data_start - 1, $table_row_data_start - 1), array($s->col_start, $table_info['num_cols'] - 1 + $s->col_start)),
				'what' => $totals_formatting
			);
		}

		// hair border along the left starting from 2nd data column
		$s->formatting[] = array(
			'border_all' => false,
			'where' => array(array($table_row_start, $table_row_data_end), array($s->col_start + 1, $table_info['num_cols'] - 1 + $s->col_start)),
			'what' => array(
				'border' => array(
					'left' => array(
						'style' => 'thin',
						'color' => '000000'
					)
				)
			)
		);

		// hair border along the top starting from 2nd data row
		$s->formatting[] = array(
			'border_all' => false,
			'where' => array(array($table_row_start + 1, $table_row_data_end), array($s->col_start, $table_info['num_cols'] - 1 + $s->col_start)),
			'what' => array(
				'border' => array(
					'top' => array(
						'style' => 'thin',
						'color' => '000000'
					)
				)
			)
		);

		// thick border around everything
		$s->formatting[] = array(
			'where' => array(array($table_row_start, $table_row_data_end), array($s->col_start, $table_info['num_cols'] - 1 + $s->col_start)),
			'what' => array(
				'border' => array(
					'top' => array(
						'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
						'color' => '000000'
					),
					'bottom' => array(
						'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
						'color' => '000000'
					),
					'right' => array(
						'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
						'color' => '000000'
					),
					'left' => array(
						'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
						'color' => '000000'
					)
				)
			)
		);

		// totals
		$s->formatting[] = array(
			'where' => array(array($table_row_data_end, $table_row_data_end), array($s->col_start, $table_info['num_cols'] - 1 + $s->col_start)),
			'what' => $totals_formatting
		);
		
		// row data formatting starts and ends on
		// can be altered depending on total rows
		$row_start = $right_border_start_row;
		$row_end = $table_row_data_end;

		// add formatting for columns
		// if totals row is for comparison, format separately
		if ($table_info['total_type'] == 'Compare') {
			list($compare_numeric_col_ranges, $compare_good_cols) = $this->get_comparison_row_col_ranges($table_info['data_totals'], $table_info['cols']);
			if (!empty($compare_numeric_col_ranges)) {
				if ($do_show_totals_at_beginning) {
					$row_start++;
					$this->add_comparison_row_formatting($s, $right_border_start_row, $compare_numeric_col_ranges, $compare_good_cols);
				}
				$row_end--;
				$this->add_comparison_row_formatting($s, $table_row_data_end, $compare_numeric_col_ranges, $compare_good_cols);
			}
		}
		// start at negative one so we can increment at the beginning of the loop
		$col_index = -1;
		foreach ($table_info['cols'] as $col => $ph)
		{
			++$col_index;
			if (isset(worker_ppc_report::$report_cols[$col])) {

				$col_info = worker_ppc_report::$report_cols[$col];
				
				// check for formatting that we have excel formatting for
				if (array_key_exists('format_excel', $col_info)) $col_format = $col_info['format_excel'];
				else if (array_key_exists('format', $col_info)) $col_format = $col_info['format'];
				
				// no formatting, move on
				else continue;
				
				$s->formatting[] = array(
					'where' => array(array($row_start, $row_end), array($col_index + $s->col_start, $col_index + $s->col_start)),
					'what' => array(
						'number_format' => $col_format
					)
				);
			}
		}
	}

	// 2d array of consecutive numeric column indexes in totals row
	private function get_comparison_row_col_ranges($data, &$cols)
	{
		global $g_report_cols;

		$col_indexes = array_keys($cols);

		$i = -1;
		$j = -1;
		$numeric_ranges = array();
		$good_cols = array();
		$streak = false;
		foreach ($data as $v) {
			$i++;

			if (is_numeric($v)) {
				$col_name = $col_indexes[$i];
				$col_info = $g_report_cols[$col_name];
				if (
					($col_info['less_is_good'] && $v < 0) ||
					(!$col_info['less_is_good'] && $v > 0)
				) {
					$good_cols[] = $i;
				}
				if ($streak === false) {
					$streak = true;
					$numeric_ranges[] = array();
					$j++;
				}
				$numeric_ranges[$j][] = $i;
			}
			else {
				$streak = false;
			}
		}
		return array($numeric_ranges, $good_cols);
	}

	private function add_comparison_row_formatting(&$s, $row, $numeric_cols, $good_cols)
	{
		foreach ($numeric_cols as $col_range) {
			$col_start = $s->col_start + $col_range[0];
			$col_end = $s->col_start + $col_range[count($col_range) - 1];

			for ($i = $col_start; $i <= $col_end; ++$i) {
				// offset is for actual location in excel
				// when we test vals, data array has no offset
				if (in_array($i - $s->col_start, $good_cols)) {
					$color = '009900';
				}
				else {
					$color = 'FF0000';
				}

				$s->formatting[] = array(
					'where' => array(array($row, $row), array($i, $i)),
					'what' => array(
						'font' => array(
							'color' => $color
						)
					)
				);
			}

			$s->formatting[] = array(
				'where' => array(array($row, $row), array($col_start, $col_end)),
				'what' => array(
					'number_format' => 'format_percent'
				)
			);
		}
	}
}


?>