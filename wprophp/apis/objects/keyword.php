<?php

/*
 * keyword
 */

class base_Keyword
{
	// standardized variables
	public $id, $text, $match_type, $max_cpc, $dest_url, $status, $use;
	
	// parent
	public $ad_group_id;
	
	// google specific
	public $g_qualityScore, $g_firstPageCpc = null;
	
	// "enums"
	public static $status_vals = array('On', 'Off', 'Deleted');
	
	function __construct($ad_group_id = null, $id = null, $text = null, $match_type = null, $max_cpc = null, $dest_url = null, $status = null, $use = null)
	{
		$this->ad_group_id = $ad_group_id;
		$this->id = $id;
		$this->text = $text;
		$this->match_type = $match_type;
		$this->max_cpc = $max_cpc;
		$this->dest_url = $dest_url;
		$this->status = $status;
		$this->use = $use;
	}
	
	public function db_set($market, $cl_id, $ac_id = null, $ca_id = null, $ag_id = null)
	{
		$updates = array(
			'ad_group_id' => (is_null($ag_id)) ? $this->ad_group_id : $ag_id,
			'id' => $this->id,
			'mod_date' => date(util::DATE)
		);
		
		if (isset($ac_id))            $updates['account_id'] = $ac_id;
		if (isset($ca_id))            $updates['campaign_id'] = $ca_id;
		if (isset($this->text))       $updates['text'] = $this->text;
		if (isset($this->match_type)) $updates['type'] = $this->match_type;
		if (isset($this->max_cpc))    $updates['max_cpc'] = $this->max_cpc;
		if (isset($this->dest_url))   $updates['dest_url'] = $this->dest_url;
		if (isset($this->status))     $updates['status'] = $this->status;
		
		$market_info = $this->get_market_info($market);
		if (isset($market_info)) {
			$updates = array_merge($updates, $market_info);
		}
		
		db::insert_update("{$market}_objects.keyword_{$cl_id}", array('ad_group_id', 'id'), $updates);
	}
	
	public function get_market_info($market)
	{
		$info = array();
		switch ($market)
		{
			case ('g'):
				if (isset($this->g_firstPageCpc)) $info['first_page_cpc'] = $this->g_firstPageCpc;
				if (isset($this->g_qualityScore)) $info['quality_score'] = $this->g_qualityScore;
				break;
			
			case ('m'):
				break;
		}
		return $info;
	}
}

?>