<?php


class ppc_report_chart 
{
	/*
	 * Convert raw data into pChart datapoints. 
	 * Format: Array of
	 *	    [cpc] => Array
	 *	        (
	 *	            [info] => Array
	 *	                (
	 *	                    [group] => performance
	 *	                    [display] => CPC
	 *	                    [align] => r
	 *	                    [format] => format_dollars
	 *	                    [axis] => vertical
	 *	                )
	 *	
	 *	            [data] => Array
	 *	                (
	 *	                    [0] => 0.28033422459893
	 *	                    [1] => 0.2735103926097
	 *	                    [2] => 0.29223846153846
	 *	                )
	 *	
	 *	        )
	 * 
	 * 
	 */
	public static function build_datapoints($info, $data, $col_meta)
	{
		$keys = array_keys($col_meta);
		
		$data_points = array();
		foreach (array_keys($info['cols']) as $col)
		{
			$data_points[$col]['info'] = $col_meta[$col];
			
			for ($i=0; $i< count($data); $i++)
			{
				$data_points[$col]['data'][$i] = $data[$i][$col];
			}
			if ($info['time_period'] == $col)
			{
				$data_points[$col]['info']['axis'] = 'horizontal';
				$data_points[$col]['info']['display'] = $col;
				
			}
			else
			{
				$data_points[$col]['info']['axis'] = 'vertical';
				$data_points[$col]['info']['is_secondary_axis'] = ($col == $info['secondary_axis']);
			}
		}
		return $data_points;
	}


	public static function draw_chart($filename, $info, $data, $col_meta) 
	{
		$data_points = self::build_datapoints($info, $data, $col_meta);
		/*
		 * Depends on the display_type (line / bar) to pick the apporriate chart class and render the graph
		 * Probably: change this back to class_exists to utilize function factory?
		 */
		switch ($info['display_type']) {
			case 'line_chart':
				$chart_params = array(
								'path' 			=>			sys_get_temp_dir() . $filename,
								'height'		=>			400,
								'width'			=>			900,
								'data_points'	=>			$data_points,
								'sub_header'	=>			'WPromote Report Graph',
								'main_header'	=>			''//'Company name goes here'
						);				
				$chart = new line_chart($chart_params);
				break;
			case 'bar_chart':
				$chart_params = array(
								'path' 			=>			sys_get_temp_dir() . $filename,
								'height'		=>			400,
								'width'			=>			900,
								'data_points'	=>			$data_points,
								'sub_header'	=>			'WPromote Report Graph',
								'main_header'	=>			''//'Company name goes here'
						);								
				$chart = new bar_chart($chart_params);
				break;
			case 'pie_chart':
				$chart_params = array(
								'path' 			=>			sys_get_temp_dir() . $filename,
								'height'		=>			400,
								'width'			=>			700,
								'data_points'	=>			$data_points,
								'sub_header'	=>			'WPromote Report Graph',
								'main_header'	=>			'Cost/Conv Pie Chart'//'Company name goes here'
						);				
				$chart = new pie_chart($chart_params);
				
				break;				
			default:
				break;

		}
		$chart->render();
		return $chart;
	}
	
}

?>