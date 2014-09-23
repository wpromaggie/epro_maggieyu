<?php

/*
 * campaign
 */

class base_Campaign
{
	// standardized variables
	public $id, $text, $budget, $status;
	
	// parent
	public $account_id;
	
	function __construct($account_id = null, $id = null, $text = null, $budget = null, $status = null)
	{
		$this->account_id = $account_id;
		$this->id = $id;
		$this->text = $text;
		$this->budget = $budget;
		$this->status = $status;
	}
	
	public function db_set($market, $cl_id, $ac_id)
	{
		$updates = array(
			'id' => $this->id,
			'mod_date' => date(util::DATE)
		);
		
		if (isset($ac_id))            $updates['account_id'] = $ac_id;
		else if (isset($this->ac_id)) $updates['account_id'] = $this->account_id;
		
		if (isset($this->text))       $updates['text'] = $this->text;
		if (isset($this->status))     $updates['status'] = $this->status;
		
		db::insert_update("{$market}_objects.campaign_{$cl_id}", array('id'), $updates);
	}
}

?>