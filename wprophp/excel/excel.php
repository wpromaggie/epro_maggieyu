<?php
//set_include_path(get_include_path() . PATH_SEPARATOR . \epro\WPROPHP_PATH);
require('PHPExcel/PHPExcel.php');
require('PHPExcel/PHPExcel/Writer/Excel5.php');
require('PHPExcel/PHPExcel/Writer/Excel2007.php');

/*
headers for xlsx
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="My Excel File.xlsx"');

headers for xls
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="My Excel File.xls"');
*/

define('EXCEL_BOLD', 'tmp');
define('EXCEL_N0', '#,##0');
define('EXCEL_N1', '#,##0.0_-');
define('EXCEL_N2', '#,##0.00_-');
// custom percent
// define('EXCEL_PERCENT', '#,##0.00"%"');

// excel's default percent formatter: will multiply by 100
define('EXCEL_PERCENT', '0.00%');

class excel
{
	// for unique chart names
	private $chart_count;
	
	const CHART_SHEET_NAME = 'ChartData';
	const CHART_HEIGHT = 15;
	
	function write(&$sheets, $base_name, $title, $opts = array())
	{
		util::set_opt_defaults($opts, array(
			'report_path' => '',
			'auto_width' => false,
			'selected_cell' => 'A1'
		));

		// make excel file
		$xls = new PHPExcel();
		$xls->getDefaultStyle()->getFont()->setSize(10);
		$xls->getProperties()->setCreator('Wpromote');
		$xls->getProperties()->setLastModifiedBy('Wpromote');
		$xls->getProperties()->setTitle($title);
		
		// convert first sheet to data sheet for charts
		$xls->setActiveSheetIndex(0);
		
		$sheet_count = 0;
		$max_col = -1;
		$max_row = -1;
		foreach ($sheets as $sheet_name => $sheet_info)
		{
			if ($sheet_count > 0)
			{
				$xls->createSheet();
				$xls->setActiveSheetIndex($sheet_count);
			}
			
			$sheet = &$xls->getActiveSheet();
			$sheet->setTitle($sheet_name);
			if (isset($sheet_info['default_formatting'])) {
				$sheet->getDefaultStyle()->applyFromArray($sheet_info['default_formatting']);	
			}
			if (isset($sheet_info['default_row_height'])) {
				$sheet->getDefaultRowDimension()->setRowHeight($sheet_info['default_row_height']);
			}

			if (array_key_exists('props', $sheet_info))
			{
				foreach ($sheet_info['props'] as $prop_key => $prop_val)
				{
					$prop_func = 'set'.$prop_key;
					$sheet->$prop_func($prop_val);
				}
			}
			
			/*
			 * data
			 */
			$data = &$sheet_info['data'];
			// row must be numeric
			// foreach so that a sparse array can be used
			foreach ($data as $row => $row_data) {
				if (empty($row_data)) continue;
				// rows don't have to be in order, set max
				if ($row > $max_row) {
					$max_row = $row;
				}
				// row data can be associative
				// keep our own col index
				$col_count = 0;
				foreach ($row_data as $key => $value) {
					$col = (is_numeric($key)) ? $key : $col_count;
					$sheet->setCellValueByColumnAndRow($col, $row, $value);
					++$col_count;
					if ($col > $max_col) {
						$max_col = $col;
					}
				}
			}
			
			/*
			 * formatting
			 */
			if (array_key_exists('formatting', $sheet_info))
			{
				$formatting = &$sheet_info['formatting'];
				foreach ($formatting as $info)
				{
					list($where, $what) = util::list_assoc($info, 'where', 'what');
					list($row_start, $row_end) = $where[0];
					list($col_start, $col_end) = $where[1];
					$border_all = (array_key_exists('border_all', $info)) ? $info['border_all'] : true;
					
					$col_start = PHPExcel_Cell::stringFromColumnIndex($col_start);
					$col_end = PHPExcel_Cell::stringFromColumnIndex($col_end);
					
					$sheet->duplicateStyleArray($this->get_style($what), $col_start.$row_start.':'.$col_end.$row_end, $border_all);
				}
			}
			
			/*
			 * charts and graphs
			 */
			if (array_key_exists('charts', $sheet_info))
			{
				$charts = &$sheet_info['charts'];
				foreach ($charts as &$chart)
				{
					$this->add_chart($sheet, $chart);
				}
			}
			
			/*
			 * images
			 */
			if (array_key_exists('images', $sheet_info))
			{
				$images = $sheet_info['images'];
				foreach ($images as $info)
				{					
					$img = new PHPExcel_Worksheet_Drawing();
					$img->setName($info['name']);
					$img->setDescription($info['name']);
					$img->setPath($info['path']);
					$img->setCoordinates($info['where']);
					$img->setWorksheet($sheet);					
				}
			}
			// duplicate default style out a little in case of background color
			if (isset($sheet_info['default_formatting'])) {
				$max_col_str = PHPExcel_Cell::stringFromColumnIndex($max_col + 1);
				$sheet->duplicateStyleArray($sheet_info['default_formatting'], $max_col_str.'1:AP'.($max_row + 100));
			}

			if ($opts['auto_width']) {
				$opts['max_col'] = $max_col;
				// it's callable, see what it evals to
				if (is_callable($opts['auto_width'])) {
					if (call_user_func($opts['auto_width'], $sheet_name, $sheet_info)) {
						$this->auto_width($sheet, $sheet_info, $opts);
					}
				}
				// eval'ed to true above and was not callable
				else {
					$this->auto_width($sheet, $sheet_info, $opts);
				}
			}
			// check for fixed height rows
			$this->set_row_heights($sheet, $sheet_info);
			$sheet->setSelectedCell($opts['selected_cell']);
			++$sheet_count;
		}
		
		// make first sheet active
		$xls->setActiveSheetIndex(0);
		$file_name = $base_name.'.xlsx';
		
		// if there is no path passed in, assume we are sending file to browser
		if (!isset($opts['report_path']))
		{
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="'.$file_name.'"');
			
			$writer = new PHPExcel_Writer_Excel2007($xls);
			$writer->setIncludeCharts(TRUE);
			#$writer = new PHPExcel_Writer_Excel5($xls);
			$writer->save('php://output');
		}
		else
		{
			$writer = new PHPExcel_Writer_Excel2007($xls);
			$writer->setIncludeCharts(TRUE);
			#$writer = new PHPExcel_Writer_Excel5($xls);
			$writer->save($opts['report_path'].$file_name);
		}
	}
	
	private function set_row_heights($sheet, $sheet_info)
	{
		if (!empty($sheet_info['fixed_height'])) {
			foreach ($sheet_info['fixed_height'] as $height_params) {
				list($row, $height) = $height_params;
				$sheet->getRowDimension($row)->setRowHeight($height);
			}
		}
	}

	private function auto_width($sheet, $sheet_info, $opts = array())
	{
		$fixed_width = (empty($sheet_info['fixed_width'])) ? array() : $sheet_info['fixed_width'];
		// $ci = PHPExcel_Cell::columnIndexFromString($sheet->getColumnDimension($sheet->getHighestColumn())->getColumnIndex());
		if (!empty($opts['max_col'])) {
			$ci = $opts['max_col'] + 1;
		}
		else {
			$ci = PHPExcel_Cell::columnIndexFromString($sheet->getColumnDimension($sheet->getHighestColumn())->getColumnIndex());
		}
		// empty any cells we are ignoring
		if (isset($sheet_info['auto_width_ignore_cells'])) {
			$ignore_vals = array();
			foreach ($sheet_info['auto_width_ignore_cells'] as $i => $where) {
				$cell = $sheet->getCellByColumnAndRow($where[0], $where[1]);
				$ignore_vals[$i] = $cell->getValue();
				$cell->setValue('');
			}
		}
		// auto size columns
		for ($i = 0; $i < $ci; ++$i) {
			$sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
		}
		// have sheet calc new col widths
		$sheet->calculateColumnWidths();
		// auto size doesn't do a very good job, add some padding
		for ($i = 0; $i < $ci; ++$i) {
			$coldim = $sheet->getColumnDimensionByColumn($i);
			$coldim->setAutoSize(false);
			if (isset($fixed_width[$i])) {
				$coldim->setWidth($fixed_width[$i]);
			}
			else {
				// seems like a good padding base
				$padding_base = 1.05;
				// width range is usually about 6 - 50+, we'll call it a range of 50
				$cur_width = $coldim->getWidth();
				// map it
				$mapped = ($cur_width / 50) * .3;
				// flip it, we want bigger factor for smaller widths
				$mapped = .3 - $mapped;
				// no negatives
				$mapped = max(0, $mapped);
				$coldim->setWidth($coldim->getWidth() * ($padding_base + $mapped));
			}
		}
		// restore values we ignored
		if (isset($sheet_info['auto_width_ignore_cells'])) {
			foreach ($sheet_info['auto_width_ignore_cells'] as $i => $where) {
				$cell = $sheet->getCellByColumnAndRow($where[0], $where[1]);
				$cell->setValue($ignore_vals[$i]);
			}
		}
		// check for cells to center
		if (isset($sheet_info['center_cells'])) {
			// calculate center column
			$widths = array();
			for ($i = 0; $i < $ci; ++$i) {
				$coldim = $sheet->getColumnDimensionByColumn($i);
				$colwidth = $coldim->getWidth();
				if ($i == 0) {
					$widths[] = $colwidth;
				}
				else {
					$widths[] = $colwidth + $widths[$i - 1];
				}
			}
			$total_width = $widths[$i - 1];
			// find half width
			$half_width = 0;
			for ($center_col = 0; $center_col < $ci; ++$center_col) {
				$coldim = $sheet->getColumnDimensionByColumn($center_col);
				$colwidth = $coldim->getWidth();
				$half_width += $colwidth;
				if ($half_width > ($total_width / 2)) {
					break;
				}
			}
			$bold_char_per_width = 1.4;
			foreach ($sheet_info['center_cells'] as $i => $where) {
				// estimate width of column we are centering
				$old_cell = $sheet->getCellByColumnAndRow($where[0], $where[1]);
				// there is some problem with getCellByColumnAndRow and coordinates and.. caching?
				// get old coord right away so it won't get messed up later
				$old_coord = $old_cell->getCoordinate();
				$old_style = $sheet->getStyle($old_coord);
				$old_val = $old_cell->getValue();
				$est_width = strlen($old_val) / $bold_char_per_width;
				$half_minus_text = $half_width - $est_width;

				for ($center_col_for_cell = $center_col - 1; $widths[$center_col_for_cell] > $half_minus_text; $center_col_for_cell--);

				// check that we actually have to move cell
				if ($where[0] != $center_col_for_cell) {
					$old_cell->setValue('');
					$new_cell = $sheet->getCellByColumnAndRow($center_col_for_cell, $where[1]);
					// echo "bold?".$old_style->getFont()->getBold()."\n";
					// $sheet->duplicateStyle($old_style, $new_cell->getCoordinate());
					// echo "old coord: ".$old_cell->getCoordinate()."\n";
					// $style_array = $this->convert_excel_style_to_array($sheet->getStyle($old_cell->getCoordinate()));
					// // e($style_array);
					// $sheet->duplicateStyleArray($style_array, $new_cell->getCoordinate().':'.$new_cell->getCoordinate());

					// $sheet->duplicateStyleArray($this->get_style($what), $col_start.$row_start.':'.$col_end.$row_end, $border_all);
					// $old_cell->setValue('');
					$new_cell->setValue($old_val);
					// $sheet->duplicateStyleArray
					// i give up trying to figure out a way around getting/copying/moving cell values/styles
					// only thing we are using this for for now, this is the style we want
					$sheet->duplicateStyleArray(array('font' => array('bold' => true, 'size' => 12)), $new_cell->getCoordinate().':'.$new_cell->getCoordinate());
				}
			}
		}
	}

	private function convert_excel_style_to_array($style)
	{
		// $not_style = array()
		// $r = new ReflectionClass($style);
		// $props = $r->getProperties();
		// $methods = $r->getMethods(ReflectionProperty::IS_PUBLIC);
		// foreach ($methods as $method) {
		// 	$mname = strtolower($method->name);
		// 	if (strpos($mname, 'get') === 0) {
		// 		$prop_test = '_'.substr($mname, 3);
		// 		foreach ($props as $prop) {
		// 			$pname = strtolower($prop->name);
		// 			if ($pname == $prop_test) {
		// 				echo "got one: $pname\n";
		// 			}
		// 		}
		// 	}
		// }
		return $this->obj_to_arr($style, array(
			'fill' => array(
				'fillType',
				'rotation',
				'startColor' => array('rGB'),
				'endColor' => array('rGB')
			),
			'font' => array(
				'name',
				'size',
				'bold',
				'italic',
				'superScript',
				'subScript',
				'underline',
				'strikethrough',
				'color' => array('rGB')
			),
			'borders' => array(
				'left' => array('borderStyle','color' => array('rGB')),
				'right' => array('borderStyle','color' => array('rGB')),
				'top' => array('borderStyle','color' => array('rGB')),
				'bottom' => array('borderStyle','color' => array('rGB')),
				'diagonal' => array('borderStyle','color' => array('rGB')),
				'diagonalDirection' => array('borderStyle','color' => array('rGB'))
				# psuedo borders
				// 'allBorders' => array('borderStyle','color' => array('rGB')),
				// 'outline' => array('borderStyle','color' => array('rGB')),
				// 'inside' => array('borderStyle','color' => array('rGB')),
				// 'vertical' => array('borderStyle','color' => array('rGB')),
				// 'horizontal' => array('borderStyle','color' => array('rGB'))
			),
			'alignment' => array(
				'horizontal',
				'vertical',
				'textRotation',
				'wrapText',
				'shrinkToFit',
				'indent'
			),
			'numberFormat' => array(
				'formatCode',
				'builtInFormatCode'
			),
			'protection' => array(
				'locked',
				'hidden'
			)
		));
	}

	private function obj_to_arr($obj, $keys)
	{
		$a = array();
		foreach ($keys as $key => $sub_keys) {
			// support "normal" arrays
			if (!is_array($sub_keys)) {
				$key = $sub_keys;
			}
			// special cases
			switch ($key) {
				case ('rGB'): $akey = strtolower($key); break;
				default: $akey = $key; break;
			}
			$v = $obj->{'get'.ucfirst($key)}();
			if (is_object($v)) {
				if (!is_array($sub_keys)) {
					throw new Exception('need key map for '.get_class($v));
				}
				$a[$akey] = $this->obj_to_arr($v, $sub_keys);
			}
			else {
				$a[$akey] = $v;
			}
		}
		return $a;
	}

	function get_style(&$wpro_style)
	{
		$phpexcel_style = array();
		foreach ($wpro_style as $key => $value)
		{
			switch ($key)
			{
				case ('alignment'):
					switch ($value)
					{
						case ('center'):
							$phpexcel_style['alignment'] = array(
								'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
							);
							break;
						case ('right'):
							$phpexcel_style['alignment'] = array(
								'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT
							);
							break;
						case ('left'):
							$phpexcel_style['alignment'] = array(
								'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT
							);
							break;
					}
					break;
				
				case ('border'):
					foreach ($value as $border_location => $border_info)
					{
						$border_style = $this->excel_get_border_style($border_info['style']);
						$phpexcel_style['borders'][$border_location] = array(
							'style'  => $border_style,
							'color' => array('rgb' => $border_info['color'])
						);
					}
					break;
				
				case ('fill'):
					$phpexcel_style['fill'] = array(
						'type'  => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array('rgb' => $value)
					);
					break;
				
				case ('font'):
					// force value to be an array
					if (!is_array($value)) $value = array($value => 1);
					foreach ($value as $font_key => $data)
					{
						$excel_value = $this->excel_get_font_value($font_key, $data);
						$phpexcel_style['font'][$font_key] = $excel_value;
					}
					break;
				
				case ('number_format'):
					switch ($value)
					{
						case ('format_dollars'):
							$phpexcel_style['numberformat'] = array(
								'code' => PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE
							);
							break;
						
						case ('format_percent'):
							$phpexcel_style['numberformat'] = array(
								'code' => EXCEL_PERCENT
							);
							break;
							
						case ('n0'):
							$phpexcel_style['numberformat'] = array(
								'code' => EXCEL_N0
							);
							break;
							
						case ('n1'):
							$phpexcel_style['numberformat'] = array(
								'code' => EXCEL_N1
							);
							break;
							
						case ('n2'):
							$phpexcel_style['numberformat'] = array(
								'code' => EXCEL_N2
							);
							break;
					}
					break;
			}
		}
		
		return $phpexcel_style;
	}
	
	function excel_get_font_value($key, $value)
	{
		switch ($key)
		{
			case ('bold'): return 1;
			case ('italic'): return 1;
			case ('color'): return array('rgb' => $value);
		}
		return $value;
	}
	
	function excel_get_border_style($style)
	{
		switch ($style)
		{
			case ('thin'): return PHPExcel_Style_Border::BORDER_THIN;
			case ('hair'): return PHPExcel_Style_Border::BORDER_HAIR;
		}
		return $style;
	}  
	
	private function add_chart(&$sheet, &$chart_info)
	{
		list($x0, $y0) = $chart_info['data_top_left'];
		list($x1, $y1) = $chart_info['data_bottom_right'];
		
		if ($x0 >= $x1 || $y0 >= $y1)
		{
			return false;
		}
		
		$sheet_name = $chart_info['data_sheet_name'];
		// set columns, 1st col is empty
		$dataseriesLabels = array();
		for ($col = $x0 + 1; $col <= $x1; ++$col)
		{
			$xls_col = PHPExcel_Cell::stringFromColumnIndex($col);
			$cell = $sheet_name.'!$'.$xls_col.'$'.$y0;
			$dataseriesLabels[] = new PHPExcel_Chart_DataSeriesValues('String', $cell, null, 1);
		}
		
		//	Set the X-Axis Labels
		//		Datatype
		//		Cell reference for data
		//		Format Code
		//		Number of datapoints in series
		//		Data values
		//		Data Marker
		$label_col = PHPExcel_Cell::stringFromColumnIndex($x0);
		$cell = $sheet_name.'!$'.$label_col.'$'.($y0 + 1).':$'.$label_col.'$'.$y1;
		$num_rows = $y1 - $y0;
		$xAxisTickValues = array(
			new PHPExcel_Chart_DataSeriesValues('String', $cell, null, $num_rows)
		);
		//	Set the Data values for each data series we want to plot
		//		Datatype
		//		Cell reference for data
		//		Format Code
		//		Number of datapoints in series
		//		Data values
		//		Data Marker
		$dataSeriesValues = array();
		for ($col = $x0 + 1; $col <= $x1; ++$col)
		{
			$xls_col = PHPExcel_Cell::stringFromColumnIndex($col);
			$cell = $sheet_name.'!$'.$xls_col.'$'.($y0 + 1).':$'.$xls_col.'$'.$y1;
			$dataSeriesValues[] = new PHPExcel_Chart_DataSeriesValues('Number', $cell, null, $num_rows);
		}

		//	Build the dataseries
		$series = new PHPExcel_Chart_DataSeries(
			$chart_info['type'],
			PHPExcel_Chart_DataSeries::GROUPING_CLUSTERED,
			range(0, count($dataSeriesValues)-1),
			$dataseriesLabels,
			$xAxisTickValues,
			$dataSeriesValues
		);
		//	Set additional dataseries parameters
		//		Make it a vertical column rather than a horizontal bar graph
		$series->setPlotDirection(PHPExcel_Chart_DataSeries::DIRECTION_COL);

		//	Set the series in the plot area
		$plotarea = new PHPExcel_Chart_PlotArea(null, array($series));
		//	Set the chart legend
		$legend = new PHPExcel_Chart_Legend(PHPExcel_Chart_Legend::POSITION_RIGHT, null, false);
		
		$name = 'Chart'.($this->chart_count++);
		$title = new PHPExcel_Chart_Title((isset($chart_info['title'])) ? $chart_info['title'] : '');
		$plot_visible_only = true;
		$display_blanks_as = 0;
		$x_axis_label = null;
		$y_axis_label = new PHPExcel_Chart_Title(($chart_info['y_axis_label']) ? $chart_info['y_axis_label'] : null);


		//	Create the chart
		$chart = new PHPExcel_Chart(
			$name,
			$title,
			$legend,
			$plotarea,
			$plot_visible_only,
			$display_blanks_as,
			$x_axis_label,
			$y_axis_label
		);
		
		//	Set the position where the chart should appear in the worksheet
		$chart->setTopLeftPosition('B'.$chart_info['chart_start_row']);
		$chart->setBottomRightPosition(PHPExcel_Cell::stringFromColumnIndex($chart_info['width'] + 1).($chart_info['chart_start_row'] + $chart_info['height']));
		
		//	Add the chart to the worksheet
		$sheet->addChart($chart);
	}
}

?>