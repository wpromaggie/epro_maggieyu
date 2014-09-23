<?php

class mod_product_calendars extends mod_product
{
	public function pre_output()
	{
		parent::pre_output();
		cgi::add_js_var('g_date_options', array(array('clear', 'Clear')));
		$this->display_default = 'cancels';
	}
	
	public function get_page_menu()
	{
		return array(
			array('cancels', 'Cancels')
		);
	}
	
	public function head()
	{
		$pages = array_slice(g::$pages, 1);
		if (empty(g::$p2))
		{
			$pages[] = 'cancels';
		}
		echo '
			<h1>
				'.implode(' :: ', array_map(array('util', 'display_text'), $pages)).'
			</h1>
			'.$this->page_menu($this->get_page_menu(), 'product/calendars/').'
		';
	}
	
	public function display_cancels()
	{
		$cal = new cancels_calendar($this);
		$cal->output();
	}
}

abstract class product_calendar extends wid_calendar
{
	protected $mod;
	
	public function __construct($mod)
	{
		$this->mod = $mod;
	}
	
	protected function get_href(&$d)
	{
		return (cgi::href('account/product/'.$d['dept'].'?aid='.$d['id']));
	}
	
	protected function display_data(&$d)
	{
		return $d['dept'].': '.sbs_lib::shorten_url($d['url'], 24);
	}
}

class cancels_calendar extends product_calendar
{
	protected function get_data($start_time, $end_time)
	{
		$accounts = db::select("
			select id, dept, url, status, de_activation_date
			from eac.account
			where
				de_activation_date between '".date(util::DATE, $start_time)."' and '".date(util::DATE, $end_time)."'
		", 'ASSOC', array('de_activation_date', true));
		
		return $accounts;
	}
	
	protected function is_off(&$d)
	{
		return ($d['status'] == 'Cancelled');
	}
}


?>