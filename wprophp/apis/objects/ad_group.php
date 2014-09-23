<?php

/*
 * ad group
 */

class base_Ad_Group
{
	// standardized variables
	public $id, $text, $max_cpc, $max_content_cpc, $status;
	
	// parent
	public $campaign_id;
	
	function __construct($campaign_id = null, $id = null, $text = null, $max_cpc = null, $max_content_cpc = null, $status = null)
	{
		$this->campaign_id = $campaign_id;
		$this->id = $id;
		$this->text = $text;
		$this->max_cpc = $max_cpc;
		$this->max_content_cpc = $max_content_cpc;
		$this->status = $status;
	}
	
	public function db_set($market, $cl_id, $ac_id = null, $ca_id = null)
	{
		$updates = array(
			'id' => $this->id,
			'mod_date' => date(util::DATE)
		);
		
		if (isset($ac_id))                  $updates['account_id'] = $ac_id;
		
		if (isset($ca_id))                  $updates['campaign_id'] = $ca_id;
		else if (isset($this->campaign_id)) $updates['campaign_id'] = $this->campaign_id;
		
		if (isset($this->text))             $updates['text'] = $this->text;
		if (isset($this->max_cpc))          $updates['max_cpc'] = $this->max_cpc;
		if (isset($this->max_content_cpc))  $updates['max_content_cpc'] = $this->max_content_cpc;
		if (isset($this->status))           $updates['status'] = $this->status;
		
		db::insert_update("{$market}_objects.ad_group_{$cl_id}", array('id'), $updates);
	}
}

?>