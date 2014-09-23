<?php

class employee_calendar extends wid_calendar
{
	public function __construct($mod)
	{
		$this->mod = $mod;
		$this->now = date(util::DATE_TIME);
	}

	protected function get_data($start_time, $end_time)
	{
		$wpro_events = wpro_event::get_all(array(
			"select" => array("wpro_event" => array("name", "type", "date", "start_time", "end_time")),
			"where" => "
				wpro_event.date between '".date(util::DATE, $start_time)."' and '".date(util::DATE, $end_time)."'
			",
			"key_col" => "date",
			"key_grouped" => true,
			"order_by" => "date asc, name asc"
		));

		return $wpro_events->to_array();
	}
	
	protected function is_off(&$d)
	{
		return false;
	}
	
	protected function get_href(&$d)
	{
		return false;
	}
	
	protected function display_data(&$d)
	{
		if (empty($d['start_time']) || $d['start_time'] == '00:00:00') {
			$ml_time = '';
		}
		else {
			$ml_time = ' ('.$this->get_time_str($d, 'start').' - '.$this->get_time_str($d, 'end').')';
		}
		return '<span class="wpro_event '.$d['type'].'">'.$d['name'].$ml_time.'</span>';
	}

	private function get_time_str($d, $key)
	{
		return date('h:iA', strtotime($d['date'].' '.$d[$key.'_time']));
	}
}

?>