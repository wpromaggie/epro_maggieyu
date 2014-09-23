<?php


// an abstract widget?!?
abstract class wid_calendar extends widget_base
{
	/*
	 * get_data must return a 2d array as [date, n]
	 * data should either have a field called 'text' or override display_data
	 */
	abstract protected function get_data($start_time, $end_time);
	
	/*
	 * override to have data display itself, otherwise the 'text' field is used
	 */
	protected function display_data(&$d)
	{
		return $d['text'];
	}
	
	/*
	 * override to return href for calendar items
	 */
	protected function get_href(&$d)
	{
		return false;
	}
	
	/*
	 * override and return true if data should be given the "off" class
	 */
	protected function is_off(&$d)
	{
		return false;
	}
	
	public function output()
	{
		$ym = $_GET['ym'];
		$time = strtotime($ym.'-01');
		if (empty($time)) {
			$time = time();
			$ym = date('Y-m');
		}
		
		$month = date('m', $time);
		$first_of_month = date('Y', $time).'-'.$month.'-01';
		$dates = array();
		
		// dates before 1st of the month to fill out the first week
		for ($i = date('Y-m-d', strtotime("$first_of_month -1 day")); date('w', strtotime($i)) != 6; $i = date('Y-m-d', strtotime("$i -1 day"))) {
			array_unshift($dates, $i);
		}
		$prev_month = date('Y-m-d', strtotime("$i -1 day"));
		
		// metrics
		for ($i = $first_of_month; date('m', strtotime($i)) == $month; $i = date('Y-m-d', strtotime("$i +1 day"))) {
			$dates[] = $i;
		}
		// dates after the last of the month to fill out the last week
		for (; date('w', strtotime($i)) != 0; $i = date('Y-m-d', strtotime("$i +1 day"))) {
			$dates[] = $i;
		}
		$next_month = date('Y-m-d', strtotime("$i +1 day"));
		
		$start_time = strtotime($dates[0]);
		$end_time = strtotime($dates[count($dates) - 1]) + 86400;
		
		$data = $this->get_data($start_time, $end_time);
		
		$ml_rows;
		$num_weeks = count($dates) / 7;
		for ($i = 0; $i < $num_weeks; ++$i) {
			$ml_headers = $ml_data = '';
			for ($j = 0; $j < 7; ++$j) {
				$date = $dates[($i * 7) + $j];
				$ml_headers .= '<td'.((date('m', strtotime($date)) == $month) ? '' : ' class="dif_month"').'>'.date('M jS', strtotime($date)).'</td>';
				
				$ml_date = '';
				$data_on_date = @$data[$date];
				$ck = ($data_on_date) ? count($data_on_date) : 0;
				for ($k = 0; $k < $ck; ++$k) {
					$d = $data_on_date[$k];
					$ml_class = ($this->is_off($d)) ? ' class="off"' : '';
					$href = $this->get_href($d);
					if ($href !== false) {
						$ml_item = '
							<a href="'.$href.'" target="_blank"'.$ml_class.'>
								'.$this->display_data($d).'
							</a>
						';
					}
					else {
						$ml_item = '
							<span'.$ml_class.'>
								'.$this->display_data($d).'
							</span>
						';
					}
					$ml_date .= '
						<p>
							'.$ml_item.'
						</p>
					';
				}
				
				$ml_data .= '<td>'.$ml_date.'</td>';
			}
			$ml_rows .= '
				<tr class="headers">'.$ml_headers.'</tr>
				<tr class="data">'.$ml_data.'</tr>
			';
		}
		
		echo '
			<table id="cal_table" ejo>
				<thead id="cal_month_thead">
					<tr id="cal_month_tr">
						<td id="prev_month_td">
							<a class="nav_link" href="" ym="'.substr($prev_month, 0, 7).'">
								&lt; '.date('F', strtotime($prev_month)).'
							</a>
						</td>
						<td id="next_month_td">
							<a class="nav_link" href="" ym="'.substr($next_month, 0, 7).'">
								'.date('F', strtotime($next_month)).' &gt;
							</a>
						</td>
						<th colspan=3 id="cur_month">&bull; '.date('F Y', $time).' &bull;</th>
						<td></td>
						<td></td>
					</tr>
					<tr><td class="spacer" colspan=20></td></tr>
				</thead>
				<thead id="cal_days">
					<tr>
						<th>Sunday</th><th>Monday</th><th>Tuesday</th><th>Wednesday</th><th>Thursday</th><th>Friday</th><th>Saturday</th>
					</tr>
				</thead>
				<tbody id="cal_tbody">
					'.$ml_rows.'
				</tbody>
			</table>
			<input type="hidden" name="ym" id="ym" value="'.$ym.'" />
		';
	}
}

?>