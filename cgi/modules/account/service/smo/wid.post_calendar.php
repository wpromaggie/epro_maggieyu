<?php

class post_calendar extends wid_calendar
{
	private $mod, $aid, $now;

	public function __construct($mod, $aid)
	{
		$this->mod = $mod;
		$this->aid = $aid;
		$this->now = date(util::DATE_TIME);
	}

	protected function get_data($start_time, $end_time)
	{
		$posts = post::get_all(array(
			'select' => array(
				"post" => array("id", "account_id", "scheduled", "substring(scheduled, 1, 10) as date"),
				"post_media" => array("id as mid"),
				"network_post" => array("id as nid", "network")
			),
			'left_join' => array("post_media" => "post.id = post_media.post_id"),
			'join_many' => array("network_post" => "post.id = network_post.post_id"),
			'where' => "
				post.account_id = :aid &&
				post.scheduled between :start and :end
			",
			'data' => array(
				"aid" => $this->aid,
				"start" => date(util::DATE, $start_time),
				"end" => date(util::DATE, $end_time)
			),
			'key_col' => "date",
			'key_grouped' => true,
			'order_by' => "scheduled asc, network asc"
		));

		return $posts->to_array(1);
	}
	
	protected function is_off(&$d)
	{
		return ($d->scheduled < $this->now);
	}
	
	protected function get_href(&$d)
	{
		return (cgi::href('account/service/smo/swapp/post?aid='.$d->account_id.'&pid='.$d->id));
	}
	
	protected function display_data(&$d)
	{
		list($hour, $minutes) = explode(':', substr($d->scheduled, 11, 5));
		if ($hour < '12') {
			if ($hour == '00') {
				$hour = '12';
			}
			$time_str = ltrim($hour, '0').':'.$minutes.'am';
		}
		else {
			$time_str = ltrim((($hour - 1) % 12) + 1, '0').':'.$minutes.'pm';
		}

		$networks_str = '';
		foreach ($d->network_post as $npost) {
			$networks_str .= $this->mod->ml_network_image($npost->network, 'tiny').' ';
		}
		return $time_str.' '.$networks_str;
	}
}

?>