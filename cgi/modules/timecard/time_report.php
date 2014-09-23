<?php
/**
 * Follows a Factory/Singleton like pattern. You shouldn't be able to instantiate
 * an instance of a TimeReport subclass directly, but should use the obtainReport
 * method to retrieve the singleton instance of your desired report.
 */
abstract class TimeReport
{
	const from_clause =  ' FROM users AS u, time_temp AS t';
	const where_clause_start = ' WHERE u.id = t.user_id';

	private static $report_types_array = array(
		array('breakdown', 'Breakdown by type of hour'),
		array('total_hours', 'Total hours worked'),
		array('daily_hours', 'Hours worked each day'),
		array('clock_hours', 'Each Clock-In Clock-Out'),
		array('no_out'     , 'Days without a clockout'),
		array('lunch_taken', 'Did you eat lunch?')
	);
	private static $user_types_array = array(
		array('all'   , 'All Employees'),
		array('exempt', 'Exempt Employees'),
		array('nonex' , 'Non-Exempt Employees'),
		array('indiv' , 'Individual Employee')
	);

	//Factory method should be used to obtain reports
	private final function __construct(){}
	private final function __clone(){}
	public static function obtainReport($report_id)
	{
		switch ($report_id)
		{
			case 'breakdown':
				return new BreakdownReport();
			case 'total_hours':
				return new TotalHoursReport();
			case 'daily_hours':
				return new DailyHoursReport();
			case 'clock_hours':
				return new ClockInOutReport();
			case 'no_out':
				return new ForgotClockOutReport();
			case 'lunch_taken':
				return new LunchTakenReport();
			default:
				//ERROR
				return FALSE;
		}
	}

	//Methods to find out what your options are
	public static function getReportTypes()
	{
		return self::$report_types_array;
	}
	public static function getUserTypes()
	{
		return self::$user_types_array;
	}

	//Build the query (Called from child object)
	public function buildQuery($start_date, $end_date, $user_type, $user_id = '-1')
	{
		$report_query =
			$this->getSelect() .
			self::from_clause .
			self::where_clause_start .
			$this->getWhere() .
			$this->buildUserClause($user_type, $user_id) .
			$this->buildDatesClause($start_date, $end_date) .
			$this->getOrderBy();

		return $report_query;
	}

	//You can also override and do extra processing in a child
	public function mlResult($report_result)
	{
		$headers = '';
		for ($i = 0, $ci = count($report_result); $i < $ci; ++$i) {
			$report_row = $report_result[$i];
			$ml .= "<tr>\n";
			$do_set_headers = (empty($headers));
			foreach ($report_row as $field => $report_item) {
				if ($do_set_headers) {
					$headeres .= '<th>'.$field.'</th>';
				}
				$ml .= "<td>".$report_item."</td>";
			}
			$ml .= "</tr>\n";
		}
		return "
			<table>
				<tr>
					$headers
				</tr>
				$ml
			</table>
		";
	}
	
	public function runQuery($q)
	{
		$data = db::select($q, 'ASSOC');
		if (!$data)
		{
			die('Error running report:<br/>'.$q.'<br/>'.db::last_error());
		}
		return $data;
	}
	
	private final function buildDatesClause($start_date, $end_date)
	{
		$dates_clause = "
			AND t.date >= '$start_date'
			AND t.date <= '$end_date'
			";
		return $dates_clause;
	}
	private final function buildUserClause($user_type, $user_id)
	{
		switch ($user_type)
		{
			case 'all':
				return "";
			case 'exempt':
				return " AND u.exempt";
			case 'nonex':
				return " AND NOT u.exempt";
			case 'indiv':
				return " AND u.id = '$user_id'";
			default:
				return "AND u.id = -1";
		}
	}

	//implemented by specific reports
	protected abstract function getSelect();
	protected abstract function getWhere();
	protected abstract function getOrderBy();
}

class BreakdownReport extends TimeReport
{
	protected $start_date, $end_date, $days;
	
	// 8 hours
	const HOURS_IN_WORKDAY = 8;
	
	const SUNDAY = 1;
	const SATURDAY = 2;
	const WEEKDAY = 3;
	
	public function buildQuery($start_date, $end_date, $user_type, $user_id = '-1')
	{
		$this->start_date = $start_date;
		$this->end_date = $end_date;
		return parent::buildQuery($start_date, $end_date, $user_type, $user_id);
	}
	
	protected function getSelect()
	{
		return "SELECT u.realname name, t.date, t.clock_in `in`, t.clock_out `out`, ((UNIX_TIMESTAMP(t.clock_out) - UNIX_TIMESTAMP(t.clock_in)) / 3600) t";
	}
	
	protected function getWhere()
	{
		return "";
	}
	
	protected function getOrderBy()
	{
		return " ORDER BY u.realname, t.date, t.clock_in";
	}
	
	public function runQuery($q)
	{
		$data = db::select($q, 'ASSOC', array('name'), array('date'));
		return $data;
	}
	
	//You can also override and do extra processing in a child
	public function mlResult($data)
	{
		$ml_users = '';
		$this->ml_headers_row = '
			<tr>
				<th>Date</th>
				<th>Total</th>
				<th>Reg</th>
				<th>OT</th>
				<th>PTO</th>
				<th>Holiday</th>
			</tr>
		';
		$hour_types = array(
			'Total',
			'Reg',
			'OT',
			'PTO',
			'Holiday'
		);
		foreach ($data as $uname => &$udata)
		{
			$ml_dates = '';
			$totals = array(
				'week' => array(),
				'month' => array(),
				'year' => array()
			);
			for ($date = $this->start_date; $date <= $this->end_date; $date = date(util::DATE, strtotime("{$date} +1 day")))
			{
				if ($this->is_weekday($date))
				{
					if (!array_key_exists($date, $udata))
					{
						$vals = array_combine($hour_types, array_fill(0, count($hour_types), ''));
					}
					else
					{
						$ddata = $udata[$date];
						// must be in order!
						$vals['Total'] = 0;
						foreach ($ddata as $d)
						{
							$vals['Total'] += (double) $d['t'];
						}
						if ($vals['Total'] > self::HOURS_IN_WORKDAY)
						{
							$vals['Reg'] = self::HOURS_IN_WORKDAY;
							$vals['OT'] = $vals['Total'] - self::HOURS_IN_WORKDAY;
						}
						else
						{
							$vals['Reg'] = $vals['Total'];
							$vals['OT'] = 0;
						}
						$vals['PTO'] = 0;
						$vals['Holiday'] = 0;
						
						// set totals
						foreach ($totals as $time_period => &$dtot)
						{
							$date_time_period = util::get_date_time_period($time_period, $date);
							foreach ($vals as $k => $v)
							{
								$dtot[$date_time_period][$k] += $v;
							}
						}
					}
					$ml_dates .= '
						<tr>
							<td>'.$date.' ('.util::get_date_time_period('month', $date).')</td>
							<td>'.implode('</td><td>', array_map(array($this, 'format_hrs'), array_values($vals))).'</td>
						</tr>
					';
				}
				// separator between weeks
				else if ($this->is_sunday($date) && $date != $this->start_date)
				{
					$ml_dates .= '
						<tr>
							<td colspan="10"><hr /></td>
						</tr>
					';
				}
			}
			$ml_agg = '';
			foreach ($totals as $time_period => &$dtot)
			{
				$ml_agg .= $this->mlAgg($time_period, $dtot);
			}
			$ml_users .= '
				<div>
					<h2 class="uheader">'.$uname.'</h2>
					<div>
						<table class="breakdown_table">
							<thead>
								<tr><td colspan=10><h3>Day</h3></td></tr>
								'.$this->ml_headers_row.'
							</thead>
							<tbody>
								'.$ml_dates.'
							</tbody>
						</table>
						'.$ml_agg.'
						<div class="clr"></div>
					</div>
				</div>
			';
		}
		return $ml_users;
	}
	
	private function mlAgg($time_period, &$dtot)
	{
		$ml = '';
		foreach ($dtot as $date => &$d)
		{
			$ml .= '
				<tr>
					<td>'.$date.'</td>
					<td>'.implode('</td><td>', array_map(array($this, 'format_hrs'), array_values($d))).'</td>
				</tr>
			';
		}
		return '
			<table class="breakdown_table">
				<thead>
					<tr><td colspan=10><h3>'.ucwords($time_period).'</h3></td></tr>
					'.$this->ml_headers_row.'
				</thead>
				<tbody>
					'.$ml.'
				</tbody>
			</table>
		';
	}
	
	private function format_hrs($h)
	{
		if ($h == 0)
		{
			return '';
		}
		else
		{
			return util::n2($h);
		}
	}
	
	private function set_day($date)
	{
		if (!$this->days)
		{
			$this->days = array();
		}
		if (!array_key_exists($date, $this->days))
		{
			$dow = date('w', strtotime($date));
			switch ($dow)
			{
				case (0):
					$this->days[$date] = self::SUNDAY;
					break;
					
				case (6):
					$this->days[$date] = self::SATURDAY;
					break;
					
				default:
					$this->days[$date] = self::WEEKDAY;
					break;
			}
		}
	}
	
	private function is_sunday($date)
	{
		$this->set_day($date);
		return ($this->days[$date] == self::SUNDAY);
	}
	
	private function is_weekday($date)
	{
		$this->set_day($date);
		return ($this->days[$date] == self::WEEKDAY);
	}
}

class ClockInOutReport extends TimeReport
{
	protected function getSelect()
	{
		return "SELECT u.realname, t.date, t.clock_in, t.clock_out, (UNIX_TIMESTAMP(t.clock_out) - UNIX_TIMESTAMP(t.clock_in))/3600 AS hours";
	}
	protected function getWhere()
	{
		return "";
	}
	protected function getOrderBy()
	{
		return " ORDER BY u.realname, t.date, t.clock_in";
	}
}

class DailyHoursReport extends TimeReport
{
	protected function getSelect()
	{
		return "SELECT u.realname, t.date,
			SUM(t.clock_out <=> NULL) AS missing_clock_outs,
			SUM((UNIX_TIMESTAMP(t.clock_out) - UNIX_TIMESTAMP(t.clock_in))/3600) AS hours";
	}
	protected function getWhere()
	{
		return "";
	}
	protected function getOrderBy()
	{
		return " GROUP BY u.realname, t.date ORDER BY u.realname, t.date";
	}
}

class TotalHoursReport extends TimeReport
{
	protected function getSelect()
	{
		return "SELECT u.realname, SUM(t.clock_out <=> NULL) AS missing_clock_outs, SUM((UNIX_TIMESTAMP(t.clock_out) - UNIX_TIMESTAMP(t.clock_in))/3600) AS total_hours";
	}
	protected function getWhere()
	{
		return "";
	}
	protected function getOrderBy()
	{
		return " GROUP BY u.realname ORDER BY u.realname";
	}
}

class ForgotClockOutReport extends TimeReport
{
	protected function getSelect()
	{
		return "SELECT u.realname, t.date";
	}
	protected function getWhere()
	{
		return " AND t.clock_out <=> NULL ";
	}
	protected function getOrderBy()
	{
		return " ORDER BY u.realname, t.date";
	}
}

class LunchTakenReport extends TimeReport
{
	protected function getSelect()
	{
		return "SELECT u.realname, t.date, t.clock_in, t.clock_out, (UNIX_TIMESTAMP(t.clock_out) - UNIX_TIMESTAMP(t.clock_in))/3600 AS hours";
	}
	protected function getWhere()
	{
		return "";
	}
	protected function getOrderBy()
	{
		return " ORDER BY u.realname, t.date, t.clock_in";
	}

	public function mlResult($report_result)
	{
		$result_array = array();

		//Stick results in arrayz
		for ($i = 0, $ci = count($report_result); $i < $ci; ++$i) {
			$rrow = $report_result[$i];
			$result_array[$rrow['realname']][$rrow['date']][] = array(
					'cin' => $rrow['clock_in'],
					'cout' => $rrow['clock_out'],
					'hours' => $rrow['hours']
				);
		}

		//Format it
		$ml = '';
		foreach ($result_array as $name => $person)
		{

			//setup/clear person data
			$person_hours = 0;
			$person_overtime = 0;
			$person_missing_outs = 0;
			$person_bad_breaks = 0;

			$person_output = '';

			foreach ($person as $date => $day) {

				//setup/clear day data
				$day_hours = 0;
				$day_break_time = 0;
				$day_has_long_break = FALSE;
				$day_missing_outs = 0;
				
				$day_rows = count($day) + 3;
				$day_output = "<tr><td rowspan='$day_rows'>$date</td>";

				//reset previous inout info (since it's a new day)
				$prev_in = null;
				$prev_out = null;
				$prev_hours = null;

				foreach ($day as $inout) {
					//get info
					$in = DateTime::createFromFormat('Y-m-d H:i:s', $inout['cin']);
					$out = DateTime::createFromFormat('Y-m-d H:i:s', $inout['cout']);
					$hours = $inout['hours'];

					//Check for break times
					if (!is_null($prev_out)) {
						$break_time = ($in->getTimestamp() - $prev_out->getTimestamp())/3600;
						$day_break_time += $break_time;
						if ($break_time >= 0.5) {
							$day_has_long_break = TRUE;
						}

						$day_output .= "<td>".sprintf('%.2f', $break_time)."</td></tr>\n";
					}

					//Check for missing clockout
					if ($out === FALSE) {
						$day_missing_outs++;
					}

					//format the in-out record
					$day_output .= '<td>'.$in->format('g:i a').'</td>';
					if ($out === FALSE) {
						$day_output .= "<td class='problem'>Missing</td>";
					} else {
						$day_output .= '<td>'.$out->format('g:i a').'</td>';
					}
					$day_output .= '<td>'.$hours.'</td>';

					//add to day data
					$day_hours += $hours;
					
					//set current to previous
					$prev_in = $in;
					$prev_out = $out;
					$prev_hours = $hours;
				}

				//deal with day data
				$day_overtime = 0;
				if ($day_hours > 8.0) {
					$day_overtime = $day_hours - 8.0;
				}

				$day_break_status = "ok";
				if ($day_hours >= 5.0 && !$day_has_long_break) {
					$day_break_status = "<p class='problem'>BAD<p>";
					$person_bad_breaks++;
				}

				//format the day
				$day_output .= "<td>-</td></tr>\n";

				$day_output .= "<tr><td colspan='3'>Total Hours</td><td>$day_hours</td></tr>";
				$day_output .= "<tr><td colspan='3'>Daily Overtime</td><td>$day_overtime</td></tr>";
				$day_output .= "<tr><td colspan='3'>Break Status</td><td>$day_break_status</td></tr>";

				//add to person data
				$person_hours += $day_hours;
				$person_overtime += $day_overtime;
				$person_missing_outs += $day_missing_outs;

				$person_output .= $day_output;
			}

			//deal with person totals

			//print the person
			$ml .= "<table>\n";
			$ml .= "<tr><td colspan='5'>$name</td></tr>\n";
			$ml .= "<tr><td>Total Hours</td><td>$person_hours</td></tr>\n";
			$ml .= "<tr><td>Daily Overtime</td><td>$person_overtime</td></tr>\n";
			$ml .= "<tr><td>Missing Outs</td><td>$person_missing_outs</td></tr>\n";
			$ml .= "<tr><td>Break Issues</td><td>$person_bad_breaks</td></tr>\n";
			$ml .= "</table><br />\n";

			$ml .= "<table>\n";
			$ml .= "<tr><td colspan='5'>$name</td></tr>\n";
			$ml .= "<tr><td>Date</td><td>In</td><td>Out</td><td>Hours</td><td>Break</td></tr>\n";
			$ml .= $person_output;
			$ml .= "</table><br />\n";
		}
		return $ml;
	}
}
?>
