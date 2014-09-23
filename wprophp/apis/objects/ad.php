<?php

/*
 * an ad..
 * "base_Ad" and not just "Ad" because Ad was defined somewhere else.. nbd
 */

class base_Ad
{
	// standardized variables
	public $id, $text, $desc_1, $desc_2, $disp_url, $dest_url, $status;
	
	// parent
	public $ad_group_id;
	
	// google specific
	public $g_approvalStatus = null;
	public $g_disapprovalReasons = null;
	public static $g_approvalStatus_vals = array('APPROVED', 'FAMILY_SAFE', 'NON_FAMILY_SAFE', 'PORN', 'UNCHECKED', 'DISAPPROVED');
	
	// yahoo specific
	public $y_name = null;
	
	// "enums"
	public static $status_vals = array('On', 'Off', 'Deleted');
	
	function __construct($ad_group_id = null, $id = null, $text = null, $desc_1 = null, $desc_2 = null, $disp_url = null, $dest_url = null, $status = null)
	{
		$this->ad_group_id = $ad_group_id;
		$this->id = $id;
		$this->text = $text;
		$this->desc_1 = $desc_1;
		$this->desc_2 = $desc_2;
		$this->disp_url = $disp_url;
		$this->dest_url = $dest_url;
		$this->status = $status;
	}
	
	public function db_set($market, $cl_id, $ac_id = null, $ca_id = null, $ag_id = null)
	{
		if (empty($ag_id)) {
			if (empty($this->ad_group_id)) {
				return false;
			}
			$ag_id = $this->ad_group_id;
		}
		$updates = array(
			'ad_group_id' => $ag_id,
			'id' => $this->id,
			'mod_date' => date(util::DATE)
		);
		
		if (isset($ac_id))          $updates['account_id'] = $ac_id;
		if (isset($ca_id))          $updates['campaign_id'] = $ca_id;
		if (isset($this->text))     $updates['text'] = $this->text;
		if (isset($this->desc_1))   $updates['desc_1'] = $this->desc_1;
		if (isset($this->desc_2))   $updates['desc_2'] = $this->desc_2;
		if (isset($this->disp_url)) $updates['disp_url'] = $this->disp_url;
		if (isset($this->dest_url)) $updates['dest_url'] = $this->dest_url;
		if (isset($this->status))   $updates['status'] = $this->status;
		
		db::insert_update("{$market}_objects.ad_{$cl_id}", array('ad_group_id', 'id'), $updates);
		return true;
	}
}

?>