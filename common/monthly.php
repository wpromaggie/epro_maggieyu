<?php

define('TODAY_REMAINING', 1);
define('TODAY_TO_DATE', 2);

class Monthly
{
	public $day, $prev_date, $next_date;
	public $days_to_date, $days_remaining, $days_in_month;
	
	private $ref_time;
	
	function Monthly($day, $ref_date = 0, $flags = TODAY_REMAINING)
	{
		$this->is_valid = false;
		$this->day = $day;
		$this->ref_time = (empty($ref_date)) ? strtotime(date(util::DATE).' 12:00:00') : strtotime("$ref_date 12:00:00");
		$this->flags = $flags;
		
		$this->set_date_vars();
	}
	
	public function set_date_vars()
	{
		$today_day = date('j', $this->ref_time);
		$month = date('n', $this->ref_time);
		$year = date('y', $this->ref_time);
		
		if (!is_numeric($this->day) || $this->day < 1 || $this->day > 31) return;
		
		if ($today_day < $this->day || ($today_day == $this->day && $this->flags == TODAY_REMAINING))
		{
			// count up to next submit day
			for (
				$this->days_remaining = 0, $i = $this->ref_time;
				date('j', $i) != $this->day && date('n', $i) == $month;
				++$this->days_remaining, $i += 86400
			);
			
			// if we went to next month, subtract one day and that's as close as we'll get
			if (date('n', $i) != $month)
			{
				--$this->days_remaining;
				$i -= 86400;
			}
			
			// we've reached next submit day, set date
			$this->next_date = date(util::DATE, $i);
			
			// subtract month to get previous submit date
			$this->date_prev = self::date_subtract_month($this->next_date, $this->day);
		
			// count number of days to date from prev submit date up to yesterday (today is counted in days_remaining)
			$yesterday = date(util::DATE, $this->ref_time);
			for (
				$this->days_to_date = 0, $i = strtotime($this->date_prev);
				date(util::DATE, $i) != $yesterday;
				++$this->days_to_date, $i += 86400
			);
		}
		// past the submit day
		else
		{
			// count back to previous submit date
			for (
				$this->days_to_date = 0, $i = $this->ref_time;
				date('j', $i) != $this->day;
				++$this->days_to_date, $i -= 86400
			);
			
			// we've reached next submit day, set date
			$this->date_prev = date(util::DATE, $i);
			
			// subtract month to get previous submit date
			$this->next_date = self::date_add_month($this->date_prev, $this->day);
			
			// count up from today to next submit date
			for (
				$this->days_remaining = 0, $i = $this->ref_time;
				date(util::DATE, $i) != $this->next_date;
				++$this->days_remaining, $i += 86400
			);
			
			if ($today_day == $this->day)
			{
				++$this->days_remaining;
			}
			
		}
		
		// default is to count today as a day remaining
		if ($this->flags == TODAY_TO_DATE)
		{
			++$this->days_to_date;
			--$this->days_remaining;
		}
		
		$this->days_in_month = $this->days_to_date + $this->days_remaining;
		
		$this->is_valid = true;
	}

	public static function date_subtract_month($date, $day)
	{
		$day_no_leading_zero = self::trim_leading_zero($day);
		
		$month = substr($date, 5, 2);
		$previous_month = ($month == '01') ? '12' : str_pad(self::trim_leading_zero($month) - 1, 2, '0', STR_PAD_LEFT);
		
		$d = date(util::DATE, strtotime("$date -1 month"));
		
		// if we're still in the same month, just start subtracting days till we hit the previous month
		if (substr($d, 5, 2) == $month)
		{
			for (
				;
				substr($d, 5, 2) == $month;
				$d = date(util::DATE, strtotime("$d -1 day"))
			);
			return $d;
		}
		
		// went a little too far, find the right day
		for (
			;
			self::trim_leading_zero(substr($d, 8)) < $day_no_leading_zero;
			$d = date(util::DATE, strtotime("$d +1 day"))
			);
		
		return $d;
	}

	public static function date_add_month($date, $day)
	{
		$day_no_leading_zero = self::trim_leading_zero($day);
		
		$month = substr($date, 5, 2);
		$next_month = ($month == '12') ? '01' : str_pad(self::trim_leading_zero($month) + 1, 2, '0', STR_PAD_LEFT);
		
		$d = date(util::DATE, strtotime("$date +1 month"));
		
		// if we went too far
		if (substr($d, 5, 2) != $next_month)
		{
			for (
				;
				substr($d, 5, 2) != $next_month;
				$d = date(util::DATE, strtotime("$d -1 day"))
			);
			return $d;
		}
		
		// if we didn't go far enough
		for (
			;
			self::trim_leading_zero(substr($d, 8)) < $day_no_leading_zero;
			$d = date(util::DATE, strtotime("$d +1 day"))
			);
		
		return $d;
	}
	
	private static function trim_leading_zero($x)
	{
		return (int) (($x[0] == '0') ? $x[1] : $x);
	}

}

?>